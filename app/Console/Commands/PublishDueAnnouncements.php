<?php

namespace App\Console\Commands;

use App\Models\Announcement;
use App\Services\AnnouncementPublicationService;
use App\Services\SuperAdminAuditService;
use Illuminate\Console\Command;

class PublishDueAnnouncements extends Command
{
    protected $signature = 'announcements:publish-due';

    protected $description = 'Queue scheduled announcements whose start time has arrived';

    public function handle(AnnouncementPublicationService $publisher, SuperAdminAuditService $audit): int
    {
        $queued = 0;
        Announcement::query()
            ->where('status', Announcement::STATUS_SCHEDULED)
            ->where('starts_at', '<=', now())
            ->where(fn ($query) => $query->whereNull('expires_at')->orWhere('expires_at', '>', now()))
            ->orderBy('id')
            ->with('creator')
            ->chunkById(100, function ($announcements) use ($publisher, $audit, &$queued): void {
                foreach ($announcements as $announcement) {
                    if ($publisher->queue($announcement)) {
                        $queued++;
                        if ($announcement->creator) {
                            $audit->record($announcement->creator, $announcement, 'announcement.published', 'Scheduled announcement queued for publication.', ['status' => Announcement::STATUS_SCHEDULED], ['status' => Announcement::STATUS_PUBLISHING], ['audience' => $announcement->audience]);
                        }
                    }
                }
            });

        Announcement::query()
            ->where('status', Announcement::STATUS_SCHEDULED)
            ->whereNotNull('expires_at')
            ->where('expires_at', '<=', now())
            ->update(['status' => Announcement::STATUS_EXPIRED, 'updated_at' => now()]);

        $this->info("Queued {$queued} due announcement(s).");

        return self::SUCCESS;
    }
}
