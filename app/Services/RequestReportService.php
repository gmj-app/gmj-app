<?php

namespace App\Services;

use App\Models\Recommendation;
use App\Models\RequestReport;
use App\Models\User;
use App\Notifications\RequestHiddenNotification;
use App\Notifications\RequestReportedNotification;
use App\Notifications\RequestReportResolvedNotification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RequestReportService
{
    public function __construct(private readonly NotificationDispatchService $notifications, private readonly RecommendationStatusTransitionService $transitions, private readonly SuperAdminAuditService $audit) {}

    public function report(User $user, Recommendation $item, string $reason, ?string $details): RequestReport
    {
        if (! $item->isReportable()) {
            throw ValidationException::withMessages(['report' => 'Only active Requests can be reported.']);
        }
        $firstPending = ! RequestReport::where('recommendation_id', $item->id)->where('status', 'pending')->exists();
        $report = RequestReport::firstOrCreate(['recommendation_id' => $item->id, 'reported_by_user_id' => $user->id], ['creator_id' => $item->creator_id, 'reason' => $reason, 'details' => $details, 'status' => 'pending']);
        if (! $report->wasRecentlyCreated) {
            throw ValidationException::withMessages(['report' => 'You already reported this Request.']);
        }
        if ($firstPending) {
            $item->load('creator.creatorOwners.user');
            foreach ($item->creator->creatorOwners as $owner) {
                if ($owner->user) {
                    $this->notifications->send($owner->user, new RequestReportedNotification($item, $owner->user));
                }
            }
        }

        return $report;
    }

    public function resolve(Recommendation $item, User $actor, string $resolution, ?Request $request = null): int
    {
        $reports = DB::transaction(function () use ($item, $actor, $resolution, $request) {
            $locked = Recommendation::lockForUpdate()->findOrFail($item->id);
            $reports = RequestReport::where('recommendation_id', $locked->id)->where('status', 'pending')->lockForUpdate()->get();
            if ($reports->isEmpty()) {
                throw ValidationException::withMessages(['report' => 'These reports have already been resolved.']);
            }
            if ($resolution === 'hidden') {
                $this->transitions->transition($locked, 'hidden', $actor, ['moderation_reason' => 'inappropriate', 'moderation_note' => 'Hidden after Creator review of Guide reports.'], 'creator');
            }
            RequestReport::whereKey($reports->pluck('id'))->update(['status' => 'resolved', 'resolution' => $resolution, 'reviewed_by_user_id' => $actor->id, 'reviewed_at' => now()]);
            $this->audit->record($actor, $locked, 'request_report.'.$resolution, 'Request reports resolved.', [], ['status' => $resolution === 'hidden' ? 'hidden' : $locked->status], ['report_count' => $reports->count(), 'resolution' => $resolution], $request);

            return $reports;
        });
        $item->refresh()->load(['creator', 'submittedBy']);
        foreach ($reports as $report) {
            $report->setRelation('recommendation', $item);
            $report->load('reporter');
            if ($report->reporter) {
                $this->notifications->send($report->reporter, new RequestReportResolvedNotification($report->fresh()->setRelation('recommendation', $item), $report->reporter));
            }
        }
        if ($resolution === 'hidden' && $item->submittedBy) {
            $this->notifications->send($item->submittedBy, new RequestHiddenNotification($item,$item->submittedBy));
        }

        return $reports->count();
    }
}
