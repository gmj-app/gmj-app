<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Collection;

class NotificationReadService
{
    /** @return Collection<int, string> */
    public function unreadSnapshot(User $user): Collection
    {
        return $user->unreadNotifications()->reorder()->pluck('id');
    }

    /** @param Collection<int, string> $ids */
    public function acknowledge(User $user, Collection $ids): void
    {
        if ($ids->isNotEmpty()) {
            $user->unreadNotifications()->whereIn('id', $ids)->update(['read_at' => now()]);
        }
    }
}
