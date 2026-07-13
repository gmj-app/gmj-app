<?php

namespace App\Services;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class AnnouncementAudienceResolver
{
    /** @return Builder<User> */
    public function query(string $audience): Builder
    {
        return User::query()
            ->when($audience === Announcement::AUDIENCE_CREATORS, function (Builder $query): void {
                $query->whereHas('creatorOwners', function (Builder $query): void {
                    $query->where('role', 'owner')
                        ->whereHas('creator', fn (Builder $query) => $query->availableForGuides());
                });
            })
            ->orderBy('users.id');
    }

    public function count(string $audience): int
    {
        return $this->query($audience)->count('users.id');
    }
}
