<?php

namespace App\Services;

use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\User;
use Illuminate\Validation\ValidationException;

class CreatorParticipationService
{
    public function ensureFavoritedForUpvote(User $user, Creator $creator): void
    {
        if ($user->hasFavoritedCreator($creator)) {
            return;
        }

        if (! $user->canFavoriteMoreCreators()) {
            throw ValidationException::withMessages([
                'limit' => 'You’ve reached your creator favorite limit. Remove a favorite before upvoting on this journey.',
            ]);
        }

        CreatorFavorite::query()->firstOrCreate([
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);
    }

    public function ensureFavoritedForParticipation(
        User $user,
        Creator $creator,
        bool $confirmed,
        string $action,
    ): void {
        if ($user->hasFavoritedCreator($creator)) {
            return;
        }

        if (! $confirmed) {
            throw ValidationException::withMessages([
                'favorite_confirmation' => match ($action) {
                    'suggest' => 'Submitting to this journey will add this creator to your favorites. You’ll use 1 of your creator favorite slots.',
                    default => 'Continuing will add this creator to your favorites.',
                },
            ]);
        }

        if (! $user->canFavoriteMoreCreators()) {
            throw ValidationException::withMessages([
                'limit' => match ($action) {
                    'suggest' => 'You’ve reached your creator favorite limit. Remove a favorite before suggesting something for this journey.',
                    default => 'You’ve reached your creator favorite limit. Remove a favorite before adding another.',
                },
            ]);
        }

        CreatorFavorite::query()->firstOrCreate([
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);
    }
}
