<?php

namespace App\Notifications;

use App\Models\Recommendation;
use App\Models\User;

class CreatorNewRequestNotification extends BaseDatabaseNotification
{
    public function __construct(Recommendation $request, User $recipient)
    {
        $awaitingReview = $request->status === 'pending';
        $guideName = $request->submittedBy?->publicName() ?: 'A Guide';
        $requestTitle = $request->displayTitle();
        $destination = $awaitingReview
            ? route('creators.recommendations.index', $request->creator, absolute: false).'#request-'.$request->id
            : route('creator.queue', $request->creator, absolute: false).'#recommendation-'.$request->id;

        parent::__construct(
            key: "request.created.creator:{$request->id}:{$recipient->id}",
            title: $awaitingReview ? 'New request awaiting review' : 'New request added',
            message: $guideName.($awaitingReview ? ' submitted “' : ' added “').$requestTitle.'”.',
            category: 'creator',
            audience: 'creator',
            actionUrl: $destination,
            actionLabel: $awaitingReview ? 'Review request' : 'View request',
            icon: 'list-check',
            severity: $awaitingReview ? 'warning' : 'info',
            context: [
                'actor_type' => $request->submitted_by ? User::class : null,
                'actor_id' => $request->submitted_by,
                'creator_id' => $request->creator_id,
                'request_id' => $request->id,
            ],
        );
    }
}
