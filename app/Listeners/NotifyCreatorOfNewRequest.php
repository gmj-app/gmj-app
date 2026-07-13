<?php

namespace App\Listeners;

use App\Events\RequestCreated;
use App\Models\Recommendation;
use App\Notifications\CreatorNewRequestNotification;
use App\Services\NotificationDispatchService;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyCreatorOfNewRequest implements ShouldQueue
{
    public bool $afterCommit = true;

    public function __construct(private readonly NotificationDispatchService $notifications) {}

    public function handle(RequestCreated $event): void
    {
        $request = Recommendation::query()->with(['creator.creatorOwners.user', 'submittedBy'])->find($event->requestId);
        if (! $request || $request->isCreatorAdded() || $event->actorContext !== 'guide' || ! $request->creator?->isAvailableForGuides()) {
            return;
        }

        $owners = $request->creator->creatorOwners
            ->where('role', 'owner')
            ->pluck('user')
            ->filter()
            ->unique('id')
            ->reject(fn ($owner) => (int) $owner->id === (int) $request->submitted_by);

        foreach ($owners as $owner) {
            $this->notifications->send($owner, new CreatorNewRequestNotification($request, $owner));
        }
    }
}
