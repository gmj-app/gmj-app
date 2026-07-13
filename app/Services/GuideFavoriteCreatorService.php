<?php

namespace App\Services;

use App\Models\Creator;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;

class GuideFavoriteCreatorService
{
    /** @return Collection<int, Creator> */
    public function activeFor(User $user): Collection
    {
        return $user->favoriteCreators()
            ->select([
                'creators.id',
                'creators.slug',
                'creators.display_name',
                'creators.avatar_path',
                'creators.youtube_thumbnail_url',
                'creators.status',
                'creators.deactivated_at',
            ])
            ->orderBy('creators.display_name')
            ->get();
    }
}
