<?php

namespace App\Services\Accolades;

use App\Models\Recommendation;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CreatorReachQueryService
{
    public function countFor(int $creatorId): int
    {
        return count($this->guideIdsFor($creatorId));
    }

    /** @return array<int, int> */
    public function guideIdsFor(int $creatorId): array
    {
        $submissions = DB::table('recommendations')->select('submitted_by as user_id')
            ->where('creator_id', $creatorId)->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)
            ->whereNotNull('submitted_by')->whereNull('deleted_at')
            ->where(fn (Builder $query) => $query->whereNull('moderation_status')->orWhere('moderation_status', '!=', 'removed'));

        $votes = DB::table('user_picks')->select('user_picks.user_id')
            ->join('recommendations', 'recommendations.id', '=', 'user_picks.recommendation_id')
            ->where('user_picks.creator_id', $creatorId)->where('user_picks.vote_count', '>', 0)
            ->whereNull('recommendations.deleted_at')
            ->where(fn (Builder $query) => $query->whereNull('recommendations.moderation_status')->orWhere('recommendations.moderation_status', '!=', 'removed'))
            ->where(fn (Builder $query) => $query->whereNull('user_picks.release_reason')->orWhere('user_picks.release_reason', '!=', 'request_removed'));

        // Favorite-only reach is intentionally active-only. Historical released favorites
        // still count when that Guide also has a qualifying submission or vote above.
        $favorites = DB::table('creator_favorites')->select('user_id')
            ->where('creator_id', $creatorId)->whereNull('released_at');

        $interactions = DB::query()->fromSub($submissions->union($votes)->union($favorites), 'interactions')
            ->select('user_id')->distinct();

        return DB::query()->fromSub($interactions, 'reach')
            ->join('users', 'users.id', '=', 'reach.user_id')
            ->leftJoin('creator_owners', function ($join) use ($creatorId): void {
                $join->on('creator_owners.user_id', '=', 'reach.user_id')->where('creator_owners.creator_id', $creatorId);
            })
            ->whereNull('users.deleted_at')->whereNull('creator_owners.id')->orderBy('reach.user_id')
            ->pluck('reach.user_id')->map(fn ($id) => (int) $id)->all();
    }
}
