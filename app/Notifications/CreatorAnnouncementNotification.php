<?php

namespace App\Notifications;

use App\Models\Announcement;
use App\Models\User;

class CreatorAnnouncementNotification extends BaseDatabaseNotification
{
    public function __construct(Announcement $announcement, User $recipient)
    {
        parent::__construct(
            key: "announcement:{$announcement->id}:user:{$recipient->id}",
            title: $announcement->title,
            message: $announcement->message,
            category: 'announcement',
            audience: 'creator',
            actionUrl: $announcement->action_url,
            actionLabel: $announcement->action_label,
            icon: $announcement->icon,
            severity: $announcement->severity,
            context: ['metadata' => ['announcement_id' => $announcement->id]],
        );
    }
}
