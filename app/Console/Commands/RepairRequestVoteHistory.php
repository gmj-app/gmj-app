<?php

namespace App\Console\Commands;

use App\Models\Recommendation;
use App\Services\RequestCacheInvalidator;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairRequestVoteHistory extends Command
{
    protected $signature = 'requests:repair-vote-history
        {--request= : Request ID}
        {--creator= : Creator ID}
        {--status=recorded : Status to inspect}
        {--dry-run : Report without changing data}
        {--apply : Apply reconstructable repairs}';

    protected $description = 'Safely reconstruct missing voting-close snapshots from preserved vote rows';

    public function handle(RequestCacheInvalidator $cache): int
    {
        if ($this->option('apply') && $this->option('dry-run')) {
            $this->error('Choose either --dry-run or --apply.');

            return self::INVALID;
        }

        $apply = (bool) $this->option('apply');
        $query = Recommendation::query()->withTrashed()->with('allUserPicks')->orderBy('id');
        $query->when($this->option('request'), fn ($q, $id) => $q->whereKey($id));
        $query->when($this->option('creator'), fn ($q, $id) => $q->where('creator_id', $id));
        $query->when($this->option('status'), fn ($q, $status) => $q->where('status', $status));
        $found = 0;

        $query->each(function (Recommendation $request) use ($apply, $cache, &$found): void {
            $found++;
            $votes = (int) $request->allUserPicks->sum('vote_count');
            $supporters = $request->allUserPicks->pluck('user_id')->filter()->unique()->count();
            $closedAt = $request->voting_closed_at
                ?? $request->allUserPicks->pluck('released_at')->filter()->sort()->first();

            $this->newLine();
            $this->line("Request #{$request->id}");
            $this->line('Title: '.$request->displayTitle());
            $this->line('Status: '.$request->statusLabel());
            $this->line('Visible total now: '.$request->totalVotes());
            $this->line("Historical released quantity: {$votes}");
            $this->line("Distinct supporters: {$supporters}");

            if ($request->allUserPicks->isEmpty() && $request->vote_total_at_close === null) {
                $this->warn('Cannot reconstruct: no vote rows remain. Inspect audit logs or backups; no values were guessed.');

                return;
            }

            $changes = array_filter([
                'vote_total_at_close' => $request->vote_total_at_close === null ? $votes : null,
                'supporter_count_at_close' => $request->supporter_count_at_close === null ? $supporters : null,
                'voting_closed_at' => $request->voting_closed_at === null ? $closedAt : null,
            ], fn ($value) => $value !== null);
            if ($changes === []) {
                $this->info('No repair needed.');

                return;
            }
            foreach ($changes as $field => $value) {
                $this->line(($apply ? 'Setting ' : 'Would set ')."{$field}: {$value}");
            }
            if ($apply) {
                DB::transaction(fn () => Recommendation::withTrashed()->lockForUpdate()->findOrFail($request->id)->update($changes));
                $cache->forget($request);
                $this->info('Applied. Existing allocations remain released.');
            }
        });

        if ($found === 0) {
            $this->warn('No matching requests found.');
        }

        return self::SUCCESS;
    }
}
