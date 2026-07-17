<?php

namespace App\Services\DailyJourney;

use App\Models\User;

class AccessService
{
    public function allows(?User $user): bool
    {
        if (! $user) {
            return false;
        }

        return (bool) config('daily_journey.public_enabled', false) || $user->isSuperAdmin();
    }

    public function isPrivate(): bool
    {
        return ! (bool) config('daily_journey.public_enabled', false);
    }
}
