<?php

namespace App\Notifications;

use App\Models\Recommendation;
use App\Models\User;

class SupportedRequestPublishedNotification extends BaseDatabaseNotification
{
    public function __construct(Recommendation $request, User $recipient)
    {
        $creatorName = filled($request->creator?->display_name)
            ? $request->creator->display_name
            : ($request->creator?->slug ?: 'A creator');
        $requestTitle = filled($request->published_title)
            ? $request->published_title
            : $request->displayTitle();
        $destination = $request->creator
            ? route('creators.published', $request->creator, absolute: false).'#recommendation-'.$request->id
            : route('notifications.index', absolute: false);

        parent::__construct(
            key: "request.published.supporter:{$request->id}:{$recipient->id}",
            title: 'A request you supported was published',
            message: $creatorName.' published “'.$requestTitle.'”.',
            category: 'request',
            audience: 'guide',
            actionUrl: $destination,
            actionLabel: 'View published request',
            icon: 'check-circle',
            severity: 'success',
            context: ['creator_id' => $request->creator_id, 'request_id' => $request->id],
        );
    }
}
