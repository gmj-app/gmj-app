<?php

namespace App\Console\Commands;

use App\Models\Recommendation;
use App\Models\UserPick;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;

class MigrateVotesToBinary extends Command
{
    protected $signature = 'votes:migrate-to-binary {--dry-run : Report without mutating} {--apply : Normalize active support rows}';

    protected $description = 'Audit or normalize active weighted votes to one binary support per Guide and Request';

    public function handle(): int
    {
        if ($this->option('dry-run') && $this->option('apply')) {
            $this->error('Choose either --dry-run or --apply.');

            return self::INVALID;
        }

        if (! Schema::hasTable('user_picks') || ! Schema::hasColumn('user_picks', 'vote_count')) {
            $this->error('The legacy user_picks.vote_count schema is not available.');

            return self::FAILURE;
        }

        $before = $this->audit();
        $this->renderAudit($before, $this->option('apply') ? 'Pre-apply audit' : 'Dry-run audit');

        if (! $this->option('apply')) {
            $this->info('Dry run only. No data was changed.');

            return self::SUCCESS;
        }

        $changed = DB::transaction(function (): int {
            UserPick::query()->whereNull('released_at')->where('vote_count', '<=', 0)->delete();
            $changed = UserPick::query()
                ->whereNull('released_at')
                ->where('vote_count', '>', 0)
                ->where('vote_count', '!=', 1)
                ->update(['vote_count' => 1, 'updated_at' => now()]);

            if (UserPick::query()->whereNull('released_at')->where('vote_count', '>', 0)->where('vote_count', '!=', 1)->exists()) {
                throw new RuntimeException('Active vote normalization validation failed.');
            }

            return $changed;
        });

        $after = $this->audit();
        $this->newLine();
        $this->renderAudit($after, 'Post-apply validation');
        $this->info("Applied binary normalization to {$changed} active rows. Historical released quantities and frozen close totals were preserved.");

        return self::SUCCESS;
    }

    /** @return array<string, int> */
    private function audit(): array
    {
        $active = UserPick::query()->whereNull('released_at')->where('vote_count', '>', 0);
        $historical = UserPick::query()->whereNotNull('released_at')->where('vote_count', '>', 0);
        $duplicatePairs = DB::query()->fromSub(
            UserPick::query()->selectRaw('user_id, recommendation_id, COUNT(*) AS pair_count')->groupBy('user_id', 'recommendation_id')->havingRaw('COUNT(*) > 1'),
            'duplicate_pairs'
        )->count();

        $weightedOrder = $this->rankingPositions(true);
        $binaryOrder = $this->rankingPositions(false);
        $rankingChanges = collect($weightedOrder)->filter(
            fn (int $position, int $requestId): bool => ($binaryOrder[$requestId] ?? $position) !== $position
        )->count();

        return [
            'total_vote_rows' => UserPick::query()->count(),
            'active_vote_rows' => (clone $active)->count(),
            'unique_active_supporter_pairs' => (clone $active)->distinct()->count('recommendation_id'),
            'multi_vote_active_rows' => (clone $active)->where('vote_count', '>', 1)->count(),
            'zero_or_negative_active_rows' => UserPick::query()->whereNull('released_at')->where('vote_count', '<=', 0)->count(),
            'weighted_active_votes_before' => (int) (clone $active)->sum('vote_count'),
            'unique_active_supports_after' => (clone $active)->count(),
            'duplicate_user_request_pairs' => $duplicatePairs,
            'historical_vote_rows_preserved' => (clone $historical)->count(),
            'historical_weighted_votes_preserved' => (int) (clone $historical)->sum('vote_count'),
            'frozen_closed_totals_preserved' => Recommendation::query()->whereNotNull('vote_total_at_close')->count(),
            'requests_changing_rank_position' => $rankingChanges,
        ];
    }

    /** @return array<int, int> */
    private function rankingPositions(bool $weighted): array
    {
        $aggregate = $weighted ? 'SUM(user_picks.vote_count)' : 'COUNT(user_picks.id)';
        $rows = DB::table('recommendations')
            ->leftJoin('user_picks', function ($join): void {
                $join->on('user_picks.recommendation_id', '=', 'recommendations.id')->whereNull('user_picks.released_at');
            })
            ->whereIn('recommendations.status', Recommendation::votableStatuses())
            ->selectRaw("recommendations.id, recommendations.creator_id, {$aggregate} AS support_total")
            ->groupBy('recommendations.id', 'recommendations.creator_id', 'recommendations.created_at')
            ->orderBy('recommendations.creator_id')->orderByDesc('support_total')->orderBy('recommendations.created_at')->orderBy('recommendations.id')
            ->get();

        $positions = [];
        foreach ($rows->groupBy('creator_id') as $creatorRows) {
            foreach ($creatorRows->values() as $position => $row) {
                $positions[(int) $row->id] = $position + 1;
            }
        }

        return $positions;
    }

    /** @param array<string, int> $audit */
    private function renderAudit(array $audit, string $title): void
    {
        $this->info($title);
        $this->table(['Metric', 'Count'], collect($audit)->map(fn (int $count, string $metric): array => [$metric, $count])->values()->all());
        $this->line('Historical policy: existing released quantities and vote_total_at_close values are not rewritten.');
        $this->line('Accolade implication: distinct supported Request evaluators already ignore vote quantity; earned awards are retained.');
    }
}
