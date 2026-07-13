<?php

namespace App\Services;

use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class UnfavoriteCreatorAction
{
    /**
     * @return array{removed_upvotes: int, removed_recommendations: int}
     */
    public function handle(User $user, Creator $creator): array
    {
        return DB::transaction(function () use ($user, $creator): array {
            /** @var User $lockedUser */
            $lockedUser = User::query()->lockForUpdate()->findOrFail($user->id);

            $favorite = CreatorFavorite::query()
                ->where('creator_id', $creator->id)
                ->where('user_id', $lockedUser->id)
                ->whereNull('released_at')
                ->lockForUpdate()
                ->first();

            if (! $favorite) {
                return [
                    'removed_upvotes' => 0,
                    'removed_recommendations' => 0,
                ];
            }

            $activeVotesQuery = $lockedUser->userPicks()
                ->where('creator_id', $creator->id)
                ->whereHas('recommendation', fn ($query) => $query
                    ->whereIn('status', Recommendation::unfavoriteRemovableStatuses()));

            $removedUpvotes = (int) $activeVotesQuery->sum('vote_count');
            $activeVotesQuery->delete();

            $removedRecommendations = Recommendation::query()
                ->where('creator_id', $creator->id)
                ->where('submitted_by', $lockedUser->id)
                ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)
                ->whereIn('status', Recommendation::unfavoriteRemovableStatuses())
                ->whereDoesntHave('userPicks')
                ->forceDelete();

            $favorite->delete();

            return [
                'removed_upvotes' => $removedUpvotes,
                'removed_recommendations' => $removedRecommendations,
            ];
        });
    }
}
