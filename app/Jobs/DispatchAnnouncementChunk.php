<?php

namespace App\Jobs;

use App\Models\Announcement;
use App\Models\AnnouncementDelivery;
use App\Models\User;
use App\Notifications\CreatorAnnouncementNotification;
use App\Notifications\SiteAnnouncementNotification;
use App\Services\NotificationDispatchService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class DispatchAnnouncementChunk implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public int $timeout = 90;

    /** @param array<int, int> $deliveryIds */
    public function __construct(public readonly int $announcementId, public readonly array $deliveryIds) {}

    public function handle(NotificationDispatchService $notifications): void
    {
        $announcement = Announcement::query()->find($this->announcementId);
        if (! $announcement || ! in_array($announcement->status, [Announcement::STATUS_PUBLISHING, Announcement::STATUS_PUBLISHED], true)) {
            return;
        }
        if ($announcement->expires_at?->isPast() && $announcement->status === Announcement::STATUS_PUBLISHING) {
            $announcement->update(['status' => Announcement::STATUS_EXPIRED]);

            return;
        }

        foreach ($this->deliveryIds as $deliveryId) {
            try {
                DB::transaction(function () use ($deliveryId, $announcement, $notifications): void {
                    $delivery = AnnouncementDelivery::query()->lockForUpdate()->find($deliveryId);
                    if (! $delivery || $delivery->status === 'delivered') {
                        return;
                    }
                    $recipient = User::query()->find($delivery->user_id);
                    if (! $recipient) {
                        $delivery->update(['status' => 'failed', 'attempts' => $delivery->attempts + 1, 'last_error' => 'Recipient unavailable.']);

                        return;
                    }

                    $notification = $announcement->audience === Announcement::AUDIENCE_CREATORS
                        ? new CreatorAnnouncementNotification($announcement, $recipient)
                        : new SiteAnnouncementNotification($announcement, $recipient);
                    $notifications->send($recipient, $notification);
                    $delivery->update([
                        'status' => 'delivered',
                        'attempts' => $delivery->attempts + 1,
                        'delivered_at' => now(),
                        'last_error' => null,
                    ]);
                });
            } catch (Throwable $exception) {
                AnnouncementDelivery::query()->whereKey($deliveryId)->update([
                    'status' => 'failed',
                    'last_error' => str($exception->getMessage())->limit(500),
                    'updated_at' => now(),
                ]);
                Log::error('Announcement notification delivery failed.', [
                    'announcement_id' => $announcement->id,
                    'delivery_id' => $deliveryId,
                    'exception' => $exception,
                ]);
            }
        }

        $announcement->update([
            'delivered_count' => $announcement->deliveries()->where('status', 'delivered')->count(),
            'failed_count' => $announcement->deliveries()->where('status', 'failed')->count(),
        ]);
        if (! $announcement->deliveries()->where('status', 'pending')->exists()) {
            $announcement->update(['status' => Announcement::STATUS_PUBLISHED, 'published_at' => $announcement->published_at ?? now()]);
        }
    }
}
