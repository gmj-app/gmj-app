<?php

namespace App\Services;

use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\Recommendation;
use App\Models\UserPick;
use Illuminate\Support\Facades\DB;

class CreatorLifecycleService
{
    public const REASON = 'creator_unavailable';

    /** @return array{favorites:int,suggestions:int,votes:int,vote_quantity:int} */
    public function releaseResourcesForCreatorId(int $creatorId): array
    {
        return DB::transaction(function () use ($creatorId): array {
            $now = now();
            $favorites = CreatorFavorite::query()->where('creator_id', $creatorId)->whereNull('released_at')
                ->update(['released_at' => $now, 'release_reason' => self::REASON]);
            $suggestions = Recommendation::query()->where('creator_id', $creatorId)
                ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)
                ->whereIn('status', Recommendation::suggestionConsumingStatuses())
                ->whereNull('resource_released_at')
                ->update(['resource_released_at' => $now, 'resource_release_reason' => self::REASON]);
            $activeVotes = UserPick::query()->where('creator_id', $creatorId)->whereNull('released_at')
                ->whereHas('recommendation', fn ($query) => $query->votable());
            $voteQuantity = (int) (clone $activeVotes)->sum('vote_count');
            $votes = $activeVotes->update(['released_at' => $now, 'release_reason' => self::REASON]);

            return [
                'favorites' => $favorites,
                'suggestions' => $suggestions,
                'votes' => $votes,
                'vote_quantity' => $voteQuantity,
            ];
        });
    }

    /** @return array{favorites:int,suggestions:int,votes:int,vote_quantity:int} */
    public function releaseResources(Creator $creator): array
    {
        return $this->releaseResourcesForCreatorId((int) $creator->getKey());
    }
}
