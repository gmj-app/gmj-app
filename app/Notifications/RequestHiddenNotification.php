<?php

namespace App\Notifications;

use App\Models\Recommendation;
use App\Models\User;

class RequestHiddenNotification extends BaseDatabaseNotification
{
    public function __construct(Recommendation $item, User $recipient)
    {
        parent::__construct(key: "request.hidden.report:{$item->id}:{$recipient->id}", title: 'Your Request was removed', message: 'The Creator removed “'.$item->displayTitle().'” from the active list.', category: 'requests', audience: 'guide', actionUrl: route('creator.queue', $item->creator, absolute: false), actionLabel: 'View Creator', icon: 'flag', severity: 'warning', context: ['creator_id' => $item->creator_id, 'request_id' => $item->id]);
    }
}
