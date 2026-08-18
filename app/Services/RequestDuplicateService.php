<?php

namespace App\Services;

use App\Models\Recommendation;
use App\Models\RequestDuplicateCase;
use App\Models\RequestDuplicateReport;
use App\Models\User;
use App\Models\UserPick;
use App\Notifications\DuplicateReportResolvedNotification;
use App\Notifications\PossibleDuplicateReportedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestDuplicateService
{
    public function __construct(private readonly NotificationDispatchService $notifications, private readonly RequestCacheInvalidator $cache, private readonly SuperAdminAuditService $audit) {}

    public function report(User $reporter, Recommendation $a, Recommendation $b): RequestDuplicateCase
    {
        $this->validatePair($a, $b);
        [$low, $high] = $a->id < $b->id ? [$a, $b] : [$b, $a];
        [$case, $created] = DB::transaction(function () use ($reporter, $low, $high): array {
            $case = RequestDuplicateCase::query()->firstOrCreate(
                ['request_low_id' => $low->id, 'request_high_id' => $high->id],
                ['creator_id' => $low->creator_id, 'status' => 'pending']
            );
            if ($case->status !== 'pending') {
                throw ValidationException::withMessages(['duplicate' => 'This Request pair has already been reviewed.']);
            }
            $report = RequestDuplicateReport::query()->firstOrCreate(['case_id' => $case->id, 'reported_by_user_id' => $reporter->id]);
            if (! $report->wasRecentlyCreated) {
                throw ValidationException::withMessages(['duplicate' => 'You already reported this Request pair.']);
            }

            return [$case, $case->wasRecentlyCreated];
        });
        $case->load(['creator.creatorOwners.user', 'requestLow', 'requestHigh']);
        if ($created) {
            foreach ($case->creator->creatorOwners as $owner) {
                if ($owner->user) {
                    $this->notifications->send($owner->user, new PossibleDuplicateReportedNotification($case, $owner->user));
                }
            }
        }

        return $case;
    }

    public function resolve(RequestDuplicateCase $case, User $actor, string $resolution, ?Request $request = null): RequestDuplicateCase
    {
        $result = DB::transaction(function () use ($case, $actor, $resolution, $request): RequestDuplicateCase {
            $lockedCase = RequestDuplicateCase::query()->lockForUpdate()->findOrFail($case->id);
            if ($lockedCase->status !== 'pending') {
                throw ValidationException::withMessages(['duplicate' => 'This duplicate case has already been resolved.']);
            }
            $items = Recommendation::withTrashed()->whereIn('id', [$lockedCase->request_low_id, $lockedCase->request_high_id])->lockForUpdate()->get()->keyBy('id');
            $a = $items[$lockedCase->request_low_id] ?? null;
            $b = $items[$lockedCase->request_high_id] ?? null;
            if (! $a || ! $b) {
                throw ValidationException::withMessages(['duplicate' => 'One of these Requests no longer exists.']);
            }
            if ($resolution === 'not_duplicate') {
                $lockedCase->update(['status' => 'rejected', 'resolution' => 'not_duplicate', 'reviewed_by_user_id' => $actor->id, 'reviewed_at' => now()]);
                $this->audit->record($actor, $lockedCase, 'request_duplicate.rejected', 'Possible duplicate pair rejected.', [], ['resolution' => 'not_duplicate'], [], $request);

                return $lockedCase;
            }
            $survivor = $resolution === 'keep_a' ? $a : $b;
            $loser = $resolution === 'keep_a' ? $b : $a;
            $this->validatePair($survivor, $loser);
            $survivorUsers = UserPick::query()->activeSupport()->where('recommendation_id', $survivor->id)->lockForUpdate()->pluck('user_id')->all();
            $loserPicks = UserPick::query()->activeSupport()->where('recommendation_id', $loser->id)->lockForUpdate()->get();
            $overlap = 0;
            $transferred = 0;
            foreach ($loserPicks as $pick) {
                if (in_array($pick->user_id, $survivorUsers, true)) {
                    $pick->update(['released_at' => now(), 'release_reason' => 'merged_duplicate']);
                    $overlap++;
                } else {
                    $pick->update(['recommendation_id' => $survivor->id, 'creator_id' => $survivor->creator_id]);
                    $survivorUsers[] = $pick->user_id;
                    $transferred++;
                }
            }
            $loser->update(['status' => 'merged_duplicate', 'merged_into_request_id' => $survivor->id, 'merged_at' => now(), 'merged_by_user_id' => $actor->id, 'voting_closed_at' => now(), 'vote_total_at_close' => $loserPicks->count(), 'supporter_count_at_close' => $loserPicks->count(), 'resource_released_at' => $loser->resource_released_at ?? now(), 'resource_release_reason' => 'merged_duplicate']);
            $summary = ['transferred_supporters' => $transferred, 'overlapping_supporters' => $overlap, 'final_unique_supporters' => count($survivorUsers), 'requester_capacity_released' => $loser->submitted_by !== null];
            $lockedCase->update(['status' => 'confirmed', 'resolution' => $resolution, 'survivor_request_id' => $survivor->id, 'merged_request_id' => $loser->id, 'reviewed_by_user_id' => $actor->id, 'reviewed_at' => now(), 'merge_summary' => $summary]);
            $this->audit->record($actor, $lockedCase, 'request_duplicate.merged', 'Duplicate Requests merged.', [], ['survivor_request_id' => $survivor->id, 'merged_request_id' => $loser->id], $summary, $request);

            return $lockedCase;
        });
        $result->load(['creator', 'reports.reporter', 'survivor', 'mergedRequest']);
        if ($result->survivor) {
            $this->cache->forget($result->survivor);
        }
        if ($result->mergedRequest) {
            $this->cache->forget($result->mergedRequest);
        }
        foreach ($result->reports as $report) {
            if ($report->reporter) {
                $this->notifications->send($report->reporter, new DuplicateReportResolvedNotification($result, $report->reporter));
            }
        }

        return $result;
    }

    private function validatePair(Recommendation $a, Recommendation $b): void
    {
        if ($a->id === $b->id) {
            throw ValidationException::withMessages(['duplicate' => 'Choose two different Requests.']);
        }
        if ($a->creator_id !== $b->creator_id) {
            throw ValidationException::withMessages(['duplicate' => 'Both Requests must belong to the same Creator.']);
        }
        foreach ([$a, $b] as $item) {
            if ($item->trashed() || ! $item->isVotable() || $item->merged_into_request_id) {
                throw ValidationException::withMessages(['duplicate' => 'Only active, voteable Requests can be reported or merged.']);
            }
        }
    }
}
