<?php

namespace App\Listeners;

use App\Events\AnnouncementPublished;
use App\Jobs\DispatchAnnouncementChunk;
use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Services\AnnouncementAudienceResolver;
use Illuminate\Contracts\Queue\ShouldQueue;

class DistributeAnnouncementNotifications implements ShouldQueue
{
    public bool $afterCommit = true;

    public function __construct(private readonly AnnouncementAudienceResolver $audiences) {}

    public function handle(AnnouncementPublished $event): void
    {
        $announcement = Announcement::query()->find($event->announcementId);
        if (! $announcement || $announcement->status !== Announcement::STATUS_PUBLISHING) {
            return;
        }

        $this->audiences->query($announcement->audience)
            ->select('users.id')
            ->chunkById(250, function ($users) use ($announcement): void {
                $now = now();
                AnnouncementDelivery::query()->insertOrIgnore($users->map(fn ($user): array => [
                    'announcement_id' => $announcement->id,
                    'user_id' => $user->id,
                    'status' => 'pending',
                    'attempts' => 0,
                    'created_at' => $now,
                    'updated_at' => $now,
                ])->all());
            }, 'users.id', 'id');

        $recipientCount = $announcement->deliveries()->count();
        $announcement->update(['recipient_count' => $recipientCount]);

        if ($recipientCount === 0) {
            $announcement->update(['status' => Announcement::STATUS_PUBLISHED, 'published_at' => now()]);

            return;
        }

        $announcement->deliveries()->select('id')->chunkById(250, function ($deliveries) use ($announcement): void {
            DispatchAnnouncementChunk::dispatch($announcement->id, $deliveries->pluck('id')->all());
        });
    }
}
