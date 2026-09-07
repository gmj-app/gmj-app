<?php

namespace App\Console\Commands;

use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use App\Services\RequestCacheInvalidator;
use App\Services\RequestSupportService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BackfillRequesterSupport extends Command
{
    protected $signature = 'requests:backfill-requester-support {--dry-run} {--apply}';

    protected $description = 'Audit or backfill requester support on open Requests; preserve frozen history';

    public function handle(RequestSupportService $support, RequestCacheInvalidator $cache): int
    {
        if ($this->option('apply') && $this->option('dry-run')) {
            $this->error('Choose --dry-run or --apply, not both.');

            return self::FAILURE;
        }
        $counts = array_fill_keys(['Active Requests inspected', 'Active Requests with real requester', 'Already self-supported', 'Missing requester support', 'Ineligible/system-created Requests', 'Existing inactive support rows preserved', 'Support rows that would be created', 'Resulting affected Request count', 'Historical Requests theoretically missing support', 'Support rows created'], 0);
        Recommendation::withTrashed()->orderBy('id')->chunkById(200, function ($requests) use (&$counts, $support, $cache) {
            foreach ($requests as $request) {
                $open = $this->isOpen($request);
                $eligible = $request->submission_source === Recommendation::SUBMISSION_SOURCE_FAN && $request->submittedBy()->exists();
                $pick = UserPick::where('recommendation_id', $request->id)->where('user_id', $request->submitted_by)->first();
                $supported = $pick && $pick->vote_count > 0 && ($open ? $pick->released_at === null : $pick->release_reason !== 'request_removed');
                if (! $open) {
                    $counts['Historical Requests theoretically missing support'] += (int) ($eligible && ! $supported);

                    continue;
                }
                $counts['Active Requests inspected']++;
                if (! $eligible) {
                    $counts['Ineligible/system-created Requests']++;

                    continue;
                }
                $counts['Active Requests with real requester']++;
                if ($supported) {
                    $counts['Already self-supported']++;

                    continue;
                }
                $counts['Missing requester support']++;
                if ($pick) {
                    $counts['Existing inactive support rows preserved']++;

                    continue;
                }
                $counts['Support rows that would be created']++;
                $counts['Resulting affected Request count']++;
                if (! $this->option('apply')) {
                    continue;
                }
                $counts['Support rows created'] += DB::transaction(function () use ($request, $support, $cache) {
                    $user = User::lockForUpdate()->find($request->submitted_by);
                    $fresh = Recommendation::lockForUpdate()->find($request->id);
                    if (! $user || ! $fresh || ! $this->isOpen($fresh) || $fresh->submitted_by !== $user->id || $fresh->submission_source !== Recommendation::SUBMISSION_SOURCE_FAN) {
                        return 0;
                    }
                    $pick = $support->supportRequest($user, $fresh);
                    if ($pick->wasRecentlyCreated) {
                        DB::afterCommit(fn () => $cache->forget($fresh));
                    }

                    return (int) $pick->wasRecentlyCreated;
                });
            }
        });
        $this->info($this->option('apply') ? 'Apply complete.' : 'Dry run: no data changed.');
        $this->table(['Metric', 'Count'], collect($counts)->map(fn ($count, $label) => [$label, $count])->values()->all());

        return self::SUCCESS;
    }

    private function isOpen(Recommendation $request): bool
    {
        return ! $request->trashed() && in_array($request->status, ['pending', 'approved'], true)
            && $request->resource_released_at === null && $request->voting_closed_at === null
            && $request->moderation_status !== 'removed';
    }
}
