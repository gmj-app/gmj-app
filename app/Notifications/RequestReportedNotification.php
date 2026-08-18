<?php

namespace App\Notifications;

use App\Models\Recommendation;
use App\Models\User;

class RequestReportedNotification extends BaseDatabaseNotification
{
    public function __construct(Recommendation $item, User $recipient)
    {
        parent::__construct(key: "request.reported:{$item->id}:{$recipient->id}", title: 'Request reported', message: '“'.$item->displayTitle().'” was flagged for review.', category: 'creator', audience: 'creator', actionUrl: route('creators.reports.index', $item->creator, absolute: false), actionLabel: 'Review reports', icon: 'flag', severity: 'warning', context: ['creator_id' => $item->creator_id, 'request_id' => $item->id]);
    }
}
