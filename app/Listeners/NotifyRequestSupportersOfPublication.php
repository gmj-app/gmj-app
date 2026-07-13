<?php

namespace App\Listeners;

use App\Events\RequestPublished;
use App\Models\Recommendation;
use App\Notifications\SupportedRequestPublishedNotification;
use App\Services\NotificationDispatchService;
use App\Services\RequestSupportRecipientResolver;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyRequestSupportersOfPublication implements ShouldQueue
{
    public bool $afterCommit = true;

    public function __construct(
        private readonly NotificationDispatchService $notifications,
        private readonly RequestSupportRecipientResolver $recipients,
    ) {}

    public function handle(RequestPublished $event): void
    {
        $request = Recommendation::query()->with('creator')->find($event->requestId);
        if (! $request || $request->status !== 'published') {
            return;
        }

        foreach ($this->recipients->resolve($request, $request->submitted_by) as $recipient) {
            $this->notifications->send($recipient, new SupportedRequestPublishedNotification($request, $recipient));
        }
    }
}
