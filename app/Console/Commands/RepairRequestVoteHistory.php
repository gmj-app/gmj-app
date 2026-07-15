<?php

namespace App\Console\Commands;

use App\Models\Recommendation;
use App\Services\RequestCacheInvalidator;
use App\Services\RequestSupportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairRequestVoteHistory extends Command
{
    protected $signature = 'requests:repair-vote-history
        {--request= : Request ID}
        {--creator= : Creator ID}
        {--status= : Closed-voting status to inspect}
        {--all-closed-voting : Inspect every closed-voting status}
        {--dry-run : Report without changing data}
        {--apply : Apply reconstructable snapshot repairs}
        {--limit= : Maximum requests to inspect}
        {--chunk=100 : Query chunk size}';

    protected $description = 'Safely reconstruct missing voting-close snapshots from preserved vote rows';

    public function handle(RequestCacheInvalidator $cache, RequestSupportService $support): int
    {
        if ($this->option('apply') && $this->option('dry-run')) {
            $this->error('Choose either --dry-run or --apply.');

            return self::INVALID;
        }

        $apply = (bool) $this->option('apply');
        $statuses = ['coming_soon', 'scheduled', 'recorded', 'published', 'passed', 'already_seen'];
        $status = $this->option('status');
        if ($status && ! in_array($status, $statuses, true)) {
            $this->error('Status must be a public closed-voting status: '.implode(', ', $statuses));

            return self::INVALID;
        }
        if (! $status && ! $this->option('all-closed-voting') && ! $this->option('request')) {
            $this->error('Choose --status, --all-closed-voting, or --request.');

            return self::INVALID;
        }
        $query = Recommendation::query()->withTrashed()->with('allUserPicks')->orderBy('id');
        $query->when($this->option('request'), fn ($q, $id) => $q->whereKey($id));
        $query->when($this->option('creator'), fn ($q, $id) => $q->where('creator_id', $id));
        $query->when($status, fn ($q) => $q->where('status', $status));
        $query->when($this->option('all-closed-voting'), fn ($q) => $q->whereIn('status', $statuses));
        $query->when($this->option('limit'), fn ($q, $limit) => $q->limit(max(1, (int) $limit)));
        $found = 0;
        $mismatches = 0;
        $queryOnly = 0;
        $mutations = [];
        $irrecoverable = [];

        $query->each(function (Recommendation $request) use ($apply, $cache, $support, &$found, &$mismatches, &$queryOnly, &$mutations, &$irrecoverable): void {
            $found++;
            $activeVotes = $support->activeVoteQuantity($request);
            $activeSupporters = $support->activeSupporterCount($request);
            $votes = $support->historicalVoteQuantity($request);
            $supporters = $support->historicalSupporterCount($request);
            $closedAt = $request->voting_closed_at
                ?? $request->allUserPicks->pluck('released_at')->filter()->sort()->first();

            $this->newLine();
            $this->line("Request #{$request->id}");
            $this->line('Title: '.$request->displayTitle());
            $this->line('Status: '.$request->statusLabel());
            $this->line("Active quantity/supporters: {$activeVotes} / {$activeSupporters}");
            $this->line("Historical released quantity: {$votes}");
            $this->line("Historical distinct supporters: {$supporters}");
            $this->line('Displayed votes/supporters: '.$support->displayVoteQuantity($request).' / '.$support->displaySupporterCount($request));
            $this->line('Stored close snapshot: '.($request->vote_total_at_close ?? 'null').' / '.($request->supporter_count_at_close ?? 'null'));
            if ($this->option('verbose')) {
                $reasons = $request->allUserPicks->pluck('release_reason')->map(fn ($reason) => $reason ?: 'active')->countBy();
                $this->line('Release reasons: '.($reasons->isEmpty() ? 'none' : $reasons->map(fn ($count, $reason) => "{$reason}={$count}")->implode(', ')));
                $this->line('Vote rows retained: '.$request->allUserPicks->count().'; deleted_at column: not present');
            }

            if ($request->allUserPicks->isEmpty() && (int) $request->vote_total_at_close > 0) {
                $this->warn('Cannot reconstruct: no vote rows remain. Inspect audit logs or backups; no values were guessed.');
                $irrecoverable[] = $request->id;

                return;
            }
            if ($request->allUserPicks->isEmpty() && $request->vote_total_at_close === null) {
                $queryOnly++;
                $this->info('No historical vote evidence; no data mutation proposed.');

                return;
            }

            if (($support->displayVoteQuantity($request) > 0 && $supporters === 0)
                || ($supporters > 0 && $support->displayVoteQuantity($request) === 0)
                || ($request->vote_total_at_close !== null && $request->vote_total_at_close !== $votes)
                || ($request->supporter_count_at_close !== null && $request->supporter_count_at_close !== $supporters)) {
                $mismatches++;
                $this->warn('Historical support mismatch detected; existing non-null snapshots were not overwritten.');
            }

            $changes = array_filter([
                'vote_total_at_close' => $request->vote_total_at_close === null ? $votes : null,
                'supporter_count_at_close' => $request->supporter_count_at_close === null ? $supporters : null,
                'voting_closed_at' => $request->voting_closed_at === null ? $closedAt : null,
            ], fn ($value) => $value !== null);
            if ($changes === []) {
                $queryOnly++;
                $this->info('No data mutation required; display/query repair only.');

                return;
            }
            $mutations[] = $request->id;
            foreach ($changes as $field => $value) {
                $this->line(($apply ? 'Setting ' : 'Would set ')."{$field}: {$value}");
            }
            if ($apply) {
                DB::transaction(fn () => Recommendation::withTrashed()->lockForUpdate()->findOrFail($request->id)->update($changes));
                $cache->forget($request);
                $this->info('Applied. Existing allocations remain released.');
            }
        }, max(1, (int) $this->option('chunk')));

        if ($found === 0) {
            $this->warn('No matching requests found.');
        }

        $this->newLine();
        $this->info("Summary: inspected={$found}, mismatches={$mismatches}, query_only={$queryOnly}, mutations=".count($mutations).', irrecoverable='.count($irrecoverable));
        $this->line('Request IDs needing mutation: '.($mutations === [] ? 'none' : implode(', ', $mutations)));
        $this->line('Potentially irrecoverable request IDs: '.($irrecoverable === [] ? 'none' : implode(', ', $irrecoverable)));

        return self::SUCCESS;
    }
}
