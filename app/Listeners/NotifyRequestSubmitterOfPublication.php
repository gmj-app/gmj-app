<?php

namespace App\Listeners;

use App\Events\RequestPublished;
use App\Models\Recommendation;
use App\Notifications\GuideRequestPublishedNotification;
use App\Services\NotificationDispatchService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyRequestSubmitterOfPublication implements ShouldQueue
{
    public bool $afterCommit = true;

    public function __construct(private readonly NotificationDispatchService $notifications) {}

    public function handle(RequestPublished $event): void
    {
        $request = Recommendation::query()->with(['creator', 'submittedBy'])->find($event->requestId);
        if (! $request || $request->status !== 'published' || $request->isCreatorAdded() || ! $request->submittedBy) {
            return;
        }

        $this->notifications->send($request->submittedBy, new GuideRequestPublishedNotification($request, $request->submittedBy));
    }
}
