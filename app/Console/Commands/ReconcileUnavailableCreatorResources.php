<?php

namespace App\Console\Commands;

use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use App\Services\CreatorLifecycleService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Throwable;

class ReconcileUnavailableCreatorResources extends Command
{
    protected $signature = 'guides:reconcile-unavailable-creator-resources
        {--dry-run : Preview changes (the default)}
        {--apply : Persist releases}
        {--user= : Guide user ID}
        {--email= : Guide email address}
        {--creator= : Creator ID, including deleted or missing creators}
        {--limit= : Maximum guides to scan}';

    protected $description = 'Audit and release Guide resources tied to unavailable or missing creators.';

    public function handle(): int
    {
        if ($this->option('apply') && $this->option('dry-run')) {
            $this->error('Choose either --apply or --dry-run.');

            return self::INVALID;
        }

        $apply = (bool) $this->option('apply');
        $creatorId = filled($this->option('creator')) ? (int) $this->option('creator') : null;
        $creators = Creator::withTrashed()->get()->keyBy('id');
        $unavailable = $creators->filter(fn (Creator $creator) => ! $creator->isAvailableForGuides());
        $query = User::query()->orderBy('id');
        $query->when(filled($this->option('user')), fn (Builder $q) => $q->whereKey((int) $this->option('user')));
        $query->when(filled($this->option('email')), fn (Builder $q) => $q->where('email', $this->option('email')));
        $query->when(filled($this->option('limit')), fn (Builder $q) => $q->limit(max(1, (int) $this->option('limit'))));

        $totals = ['scanned' => 0, 'affected' => 0, 'clean' => 0, 'favorites' => 0, 'suggestions' => 0,
            'votes' => 0, 'vote_quantity' => 0, 'votes_already_released' => 0, 'missing' => []];

        foreach ($query->cursor() as $user) {
            $totals['scanned']++;
            $audit = $this->auditGuide($user, $creators, $creatorId);
            foreach (['favorites', 'suggestions', 'votes', 'votes_already_released'] as $key) {
                $totals[$key] += $audit[$key]->count();
            }
            $totals['vote_quantity'] += $audit['votes']->sum('vote_count');
            $totals['missing'] = array_values(array_unique([...$totals['missing'], ...$audit['missing']]));
            $changed = $audit['favorites']->isNotEmpty() || $audit['suggestions']->isNotEmpty() || $audit['votes']->isNotEmpty();
            $totals[$changed ? 'affected' : 'clean']++;

            if ($this->option('verbose') && ($changed || $audit['votes_already_released']->isNotEmpty())) {
                $this->guideReport($user, $audit);
            }

            if ($apply && $changed) {
                try {
                    DB::transaction(function () use ($audit): void {
                        $now = now();
                        CreatorFavorite::query()->whereKey($audit['favorites']->pluck('id'))->whereNull('released_at')
                            ->update(['released_at' => $now, 'release_reason' => CreatorLifecycleService::REASON]);
                        Recommendation::query()->whereKey($audit['suggestions']->pluck('id'))->whereNull('resource_released_at')
                            ->update(['resource_released_at' => $now, 'resource_release_reason' => CreatorLifecycleService::REASON]);
                        UserPick::query()->whereKey($audit['votes']->pluck('id'))->whereNull('released_at')
                            ->update(['released_at' => $now, 'release_reason' => CreatorLifecycleService::REASON]);
                    });
                } catch (Throwable $exception) {
                    $this->error("Guide #{$user->id} failed: {$exception->getMessage()}");
                }
            }
        }

        $this->newLine();
        $this->info($apply ? 'Apply complete.' : 'Dry run only; no data changed.');
        $this->table(['Metric', 'Count'], [
            ['Guides scanned', $totals['scanned']], ['Unavailable creators found', $unavailable->count()],
            ['Missing/orphaned creator IDs', count($totals['missing'])], ['Favorites requiring release', $totals['favorites']],
            ['Suggestions requiring release', $totals['suggestions']], ['Active vote allocations requiring release', $totals['votes']],
            ['Active vote quantity requiring release', $totals['vote_quantity']], ['Votes already released/resolved', $totals['votes_already_released']],
            ['Guides affected', $totals['affected']], ['Guides requiring no changes', $totals['clean']],
        ]);
        if ($totals['missing']) {
            $this->warn('Orphaned creator IDs: '.implode(', ', $totals['missing']));
        }

        return self::SUCCESS;
    }

    private function auditGuide(User $user, $creators, ?int $creatorId): array
    {
        $isUnavailable = fn ($row): bool => ! $creators->has($row->creator_id)
            || ! $creators->get($row->creator_id)->isAvailableForGuides();
        $filter = fn ($query) => $query->when($creatorId, fn ($q) => $q->where('creator_id', $creatorId));
        $favorites = $filter($user->creatorFavorites()->whereNull('released_at'))->get()->filter($isUnavailable)->values();
        $suggestions = $filter($user->recommendationsSubmitted()->whereNull('resource_released_at')
            ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)
            ->whereIn('status', Recommendation::suggestionConsumingStatuses()))->get()->filter($isUnavailable)->values();
        $allPicks = $filter($user->userPicks())->with('recommendation:id,status')->get()->filter($isUnavailable)->values();
        $votes = $allPicks->filter(fn (UserPick $pick) => $pick->released_at === null
            && $pick->recommendation && $pick->recommendation->isVotable())->values();
        $already = $allPicks->reject(fn (UserPick $pick) => $votes->contains('id', $pick->id))->values();
        $missing = $favorites->concat($suggestions)->concat($allPicks)->pluck('creator_id')
            ->reject(fn ($id) => $creators->has($id))->unique()->values()->all();

        return compact('favorites', 'suggestions', 'votes', 'already', 'missing') + ['votes_already_released' => $already];
    }

    private function guideReport(User $user, array $audit): void
    {
        $creatorIds = $audit['favorites']->concat($audit['suggestions'])->concat($audit['votes'])->pluck('creator_id')->unique();
        $favoriteBefore = $user->creatorFavorites()->whereNull('released_at')->count();
        $suggestionBefore = $user->recommendationsSubmitted()->whereNull('resource_released_at')
            ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)
            ->whereIn('status', Recommendation::suggestionConsumingStatuses())->count();
        $votesBefore = (int) $user->userPicks()->whereNull('released_at')
            ->whereHas('recommendation', fn ($query) => $query->votable())->sum('vote_count');
        $this->newLine();
        $this->line("Guide #{$user->id} {$user->publicName()} <{$user->email}>");
        $this->line("Favorites: {$favoriteBefore} used -> ".($favoriteBefore - $audit['favorites']->count()).' used');
        $this->line("Suggestions: {$suggestionBefore} used -> ".($suggestionBefore - $audit['suggestions']->count()).' used');
        $this->line("Active votes: {$votesBefore} used -> ".($votesBefore - $audit['votes']->sum('vote_count')).' used');
        $this->line("Changes required: favorites {$audit['favorites']->count()}, suggestions {$audit['suggestions']->count()}, votes {$audit['votes']->count()}");
        $this->line('Vote allocations already correct: '.$audit['votes_already_released']->count());
        $this->line('Unavailable creators: '.($creatorIds->implode(', ') ?: 'none'));
        $this->line('Rows: favorites ['.$audit['favorites']->pluck('id')->implode(', ').']; suggestions ['.$audit['suggestions']->pluck('id')->implode(', ').']; votes ['.$audit['votes']->pluck('id')->implode(', ').']');
    }
}
