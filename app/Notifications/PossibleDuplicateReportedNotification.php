<?php

namespace App\Notifications;

use App\Models\RequestDuplicateCase;
use App\Models\User;

class PossibleDuplicateReportedNotification extends BaseDatabaseNotification
{
    public function __construct(RequestDuplicateCase $case, User $recipient)
    {
        parent::__construct(
            key: "request.duplicate.created:{$case->id}:{$recipient->id}",
            title: 'Possible duplicate Requests reported',
            message: '“'.$case->requestLow->displayTitle().'” and “'.$case->requestHigh->displayTitle().'” may be duplicates.',
            category: 'creator', audience: 'creator',
            actionUrl: route('creators.duplicates.index', $case->creator, absolute: false),
            actionLabel: 'Review possible duplicates', icon: 'list-check', severity: 'warning',
            context: ['creator_id' => $case->creator_id, 'duplicate_case_id' => $case->id]
        );
    }
}
