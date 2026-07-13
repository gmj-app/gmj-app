<?php

namespace App\Notifications\Channels;

use App\Notifications\BaseDatabaseNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class DeduplicatedDatabaseChannel
{
    public function send(object $notifiable, Notification $notification): mixed
    {
        abort_unless($notification instanceof BaseDatabaseNotification, 500);

        return $notifiable->routeNotificationFor('database', $notification)->create([
            'id' => $notification->id ?: Str::uuid()->toString(),
            'type' => $notification::class,
            'deduplication_key' => $notification->deduplicationKey(),
            'data' => $notification->toArray($notifiable),
            'read_at' => null,
        ]);
    }
}
