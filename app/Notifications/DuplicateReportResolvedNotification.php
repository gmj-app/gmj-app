<?php

namespace App\Notifications;

use App\Models\RequestDuplicateCase;
use App\Models\User;

class DuplicateReportResolvedNotification extends BaseDatabaseNotification
{
    public function __construct(RequestDuplicateCase $case, User $recipient)
    {
        $merged = $case->status === 'confirmed';
        parent::__construct(
            key: "request.duplicate.resolved:{$case->id}:{$recipient->id}",
            title: $merged ? 'Duplicate Requests merged' : 'Duplicate report reviewed',
            message: $merged ? 'The Creator merged the duplicate Requests you reported.' : 'The Creator reviewed your report and kept both Requests.',
            category: 'requests', audience: 'guide',
            actionUrl: route('creator.queue', $case->creator, absolute: false),
            actionLabel: 'View Requests', icon: 'list-check', severity: 'info',
            context: ['creator_id' => $case->creator_id, 'duplicate_case_id' => $case->id]
        );
    }
}
