<?php

namespace App\Services;

use App\Events\AnnouncementPublished;
use App\Models\Announcement;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

class AnnouncementPublicationService
{
    public function queue(Announcement $announcement): bool
    {
        $claimed = DB::transaction(function () use ($announcement): ?Announcement {
            $locked = Announcement::query()->lockForUpdate()->find($announcement->id);
            if (! $locked || ! in_array($locked->status, [Announcement::STATUS_DRAFT, Announcement::STATUS_SCHEDULED], true)) {
                return null;
            }
            if ($locked->expires_at?->isPast()) {
                $locked->update(['status' => Announcement::STATUS_EXPIRED]);

                return null;
            }
            if ($locked->starts_at?->isFuture()) {
                $locked->update(['status' => Announcement::STATUS_SCHEDULED]);

                return null;
            }

            $locked->update([
                'status' => Announcement::STATUS_PUBLISHING,
                'recipient_count' => 0,
                'delivered_count' => 0,
                'failed_count' => 0,
            ]);

            return $locked->fresh();
        });

        if (! $claimed) {
            return false;
        }

        try {
            AnnouncementPublished::dispatch($claimed->id, $claimed->audience, now()->toIso8601String());
        } catch (Throwable $exception) {
            $claimed->update(['status' => $claimed->starts_at ? Announcement::STATUS_SCHEDULED : Announcement::STATUS_DRAFT]);
            Log::error('Unable to queue announcement delivery.', ['announcement_id' => $claimed->id, 'exception' => $exception]);

            return false;
        }

        return true;
    }
}
