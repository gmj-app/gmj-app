<?php

namespace App\Notifications;

use App\Models\RequestReport;
use App\Models\User;

class RequestReportResolvedNotification extends BaseDatabaseNotification
{
    public function __construct(RequestReport $report, User $recipient)
    {
        $hidden = $report->resolution === 'hidden';
        parent::__construct(key: "request.report.resolved:{$report->id}:{$recipient->id}", title: 'Request report reviewed', message: $hidden ? 'The Creator removed the reported Request from the active list.' : 'The Creator reviewed your report and kept the Request active.', category: 'requests', audience: 'guide', actionUrl: route('creator.queue', $report->recommendation->creator, absolute: false), actionLabel: 'View Creator', icon: 'flag', severity: 'info', context: ['creator_id' => $report->creator_id, 'request_id' => $report->recommendation_id]);
    }
}
