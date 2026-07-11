<?php

namespace App\Services;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Support\Collection;

class GuideActivityService
{
    public const CREATOR_LIMIT = 25;

    public const VOTE_LIMIT_PER_CREATOR = 5;

    public const SUGGESTION_LIMIT_PER_CREATOR = 10;

    /**
     * @return array{active_vote_count: int, suggestion_count: int, published_count: int}
     */
    public function summaryFor(User $user): array
    {
        $suggestions = $user->recommendationsSubmitted()
            ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)
            ->whereNotIn('status', ['hidden', 'withdrawn'])
            ->selectRaw('count(*) as suggestion_count, sum(case when status = ? then 1 else 0 end) as published_count', ['published'])
            ->first();

        return [
            'active_vote_count' => (int) $user->userPicks()
                ->whereHas('recommendation', fn ($query) => $query->votable())
                ->sum('vote_count'),
            'suggestion_count' => (int) ($suggestions?->suggestion_count ?? 0),
            'published_count' => (int) ($suggestions?->published_count ?? 0),
        ];
    }

    /**
     * @return array{creators: Collection, activeVotesByCreator: Collection, suggestionsByCreator: Collection}
     */
    public function forUser(User $user, string $type = 'all'): array
    {
        $type = in_array($type, ['all', 'votes', 'suggestions', 'published'], true) ? $type : 'all';

        $favoriteDates = $user->favoriteCreators()
            ->active()
            ->pluck('creator_favorites.created_at', 'creators.id');

        $suggestionStats = $user->recommendationsSubmitted()
            ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)
            ->whereNotIn('status', ['hidden', 'withdrawn'])
            ->selectRaw('creator_id, count(*) as suggestion_count, sum(case when status = ? then 1 else 0 end) as published_count, max(updated_at) as latest_at', ['published'])
            ->groupBy('creator_id')
            ->get()
            ->keyBy('creator_id');

        $voteStats = $user->userPicks()
            ->selectRaw('creator_id, max(updated_at) as latest_at')
            ->groupBy('creator_id')
            ->get()
            ->keyBy('creator_id');

        $activeVoteCounts = $user->userPicks()
            ->whereHas('recommendation', fn ($query) => $query->votable())
            ->selectRaw('creator_id, sum(vote_count) as active_vote_count, max(updated_at) as latest_at')
            ->groupBy('creator_id')
            ->get()
            ->keyBy('creator_id');

        $creatorIds = $favoriteDates->keys()
            ->merge($suggestionStats->keys())
            ->merge($voteStats->keys())
            ->unique();

        $creators = Creator::query()
            ->active()
            ->whereIn('id', $creatorIds)
            ->get()
            ->each(function (Creator $creator) use ($activeVoteCounts, $favoriteDates, $suggestionStats, $voteStats): void {
                $suggestions = $suggestionStats->get($creator->id);
                $activeVotes = $activeVoteCounts->get($creator->id);
                $creator->setAttribute('active_vote_count', (int) ($activeVotes?->active_vote_count ?? 0));
                $creator->setAttribute('suggestion_count', (int) ($suggestions?->suggestion_count ?? 0));
                $creator->setAttribute('published_count', (int) ($suggestions?->published_count ?? 0));
                $creator->setAttribute('is_favorite', $favoriteDates->has($creator->id));
                $creator->setAttribute('activity_at', collect([
                    $activeVotes?->latest_at,
                    $suggestions?->latest_at,
                    $voteStats->get($creator->id)?->latest_at,
                    $favoriteDates->get($creator->id),
                ])->filter()->max());
            })
            ->filter(fn (Creator $creator): bool => match ($type) {
                'votes' => $creator->active_vote_count > 0,
                'suggestions' => $creator->suggestion_count > 0,
                'published' => $creator->published_count > 0,
                default => true,
            })
            ->sortByDesc(fn (Creator $creator): array => [
                $creator->active_vote_count > 0,
                $creator->suggestion_count > 0,
                $creator->is_favorite,
                $creator->activity_at,
            ])
            ->take(self::CREATOR_LIMIT)
            ->values();

        $visibleIds = $creators->pluck('id');

        $activeVotesByCreator = UserPick::query()
            ->where('user_id', $user->id)
            ->whereIn('creator_id', $visibleIds)
            ->whereHas('recommendation', fn ($query) => $query->votable())
            ->with(['recommendation:id,creator_id,title,status,recommendation_type,media_type'])
            ->orderByDesc('updated_at')
            ->limit(self::CREATOR_LIMIT * self::VOTE_LIMIT_PER_CREATOR)
            ->get()
            ->groupBy('creator_id')
            ->map->take(self::VOTE_LIMIT_PER_CREATOR);

        $suggestionsByCreator = Recommendation::query()
            ->where('submitted_by', $user->id)
            ->whereIn('creator_id', $visibleIds)
            ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)
            ->whereNotIn('status', ['hidden', 'withdrawn'])
            ->withSum('userPicks as user_picks_count', 'vote_count')
            ->latest()
            ->limit(self::CREATOR_LIMIT * self::SUGGESTION_LIMIT_PER_CREATOR)
            ->get()
            ->groupBy('creator_id')
            ->map->take(self::SUGGESTION_LIMIT_PER_CREATOR);

        return compact('creators', 'activeVotesByCreator', 'suggestionsByCreator');
    }
}
