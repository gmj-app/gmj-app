<?php

namespace App\Policies;

use App\Models\Creator;
use App\Models\User;

class CreatorPolicy
{
    public function viewDashboard(User $user, Creator $creator): bool
    {
        return $this->manage($user, $creator);
    }

    public function manage(User $user, Creator $creator): bool
    {
        return $user->isSuperAdmin() || $creator->creatorOwners()
            ->where('user_id', $user->id)
            ->where('role', 'owner')
            ->exists();
    }
}
