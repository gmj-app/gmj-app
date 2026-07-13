<?php

namespace App\Services;

use App\Models\User;
use App\Notifications\BaseDatabaseNotification;
use Illuminate\Database\QueryException;
use Illuminate\Support\Collection;

class NotificationDispatchService
{
    public function send(User $recipient, BaseDatabaseNotification $notification): bool
    {
        if ($this->hasBeenSent($recipient, $notification->deduplicationKey())) {
            return false;
        }

        try {
            $recipient->notify($notification);
        } catch (QueryException $exception) {
            if ($this->hasBeenSent($recipient, $notification->deduplicationKey())) {
                return false;
            }
            throw $exception;
        }

        return true;
    }

    public function hasBeenSent(User $recipient, string $key): bool
    {
        return $recipient->notifications()->where('deduplication_key', $key)->exists();
    }

    /** @return array{sent:int,duplicates:int} */
    public function sendMany(Collection $recipients, callable $factory): array
    {
        $sent = $duplicates = 0;
        foreach ($recipients as $recipient) {
            $this->send($recipient, $factory($recipient)) ? $sent++ : $duplicates++;
        }

        return compact('sent', 'duplicates');
    }
}
