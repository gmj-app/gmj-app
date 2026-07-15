<?php

namespace App\Services;

use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Database\Eloquent\Builder;

class PublicGuideMetricsService
{
    /**
     * Allocation rows are the sole vote source. Active and released rows are
     * mutually exclusive states of the same row, so snapshots are never added.
     *
     * @return array<string, int>
     */
    public function forGuide(User $guide): array
    {
        $requests = $guide->recommendationsSubmitted()
            ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)
            ->publiclyVisible()
            ->selectRaw("COUNT(*) as requests_count, SUM(CASE WHEN recommendations.status = 'published' THEN 1 ELSE 0 END) as published_requests_count")
            ->first();
        $votableStatuses = collect(Recommendation::votableStatuses())
            ->map(fn (string $status) => "'".str_replace("'", "''", $status)."'")
            ->implode(',');
        $support = $this->lifetimeSupport($guide)
            ->join('recommendations as metric_recommendations', 'metric_recommendations.id', '=', 'user_picks.recommendation_id')
            ->selectRaw("COALESCE(SUM(user_picks.vote_count), 0) as votes_cast_count,
                COUNT(DISTINCT user_picks.creator_id) as creators_supported_count,
                COUNT(DISTINCT user_picks.recommendation_id) as requests_supported_count,
                COUNT(DISTINCT CASE WHEN user_picks.released_at IS NULL AND metric_recommendations.status IN ({$votableStatuses}) THEN user_picks.recommendation_id END) as active_requests_supported_count,
                COUNT(DISTINCT CASE WHEN user_picks.released_at IS NULL AND metric_recommendations.status IN ({$votableStatuses}) THEN user_picks.creator_id END) as active_creators_supported_count,
                COALESCE(SUM(CASE WHEN user_picks.released_at IS NULL AND metric_recommendations.status IN ({$votableStatuses}) THEN user_picks.vote_count ELSE 0 END), 0) as active_vote_quantity,
                COALESCE(SUM(CASE WHEN user_picks.released_at IS NOT NULL THEN user_picks.vote_count ELSE 0 END), 0) as historical_vote_quantity")
            ->first();

        return [
            'requests_count' => (int) ($requests?->requests_count ?? 0),
            'published_requests_count' => (int) ($requests?->published_requests_count ?? 0),
            'votes_cast_count' => (int) ($support?->votes_cast_count ?? 0),
            'creators_supported_count' => (int) ($support?->creators_supported_count ?? 0),
            'requests_supported_count' => (int) ($support?->requests_supported_count ?? 0),
            'active_requests_supported_count' => (int) ($support?->active_requests_supported_count ?? 0),
            'active_creators_supported_count' => (int) ($support?->active_creators_supported_count ?? 0),
            'active_vote_quantity' => (int) ($support?->active_vote_quantity ?? 0),
            'historical_vote_quantity' => (int) ($support?->historical_vote_quantity ?? 0),
        ];
    }

    /** @return Builder<UserPick> */
    public function lifetimeSupport(User $guide): Builder
    {
        return UserPick::query()
            ->validHistoricalSupport()
            ->where('user_id', $guide->id)
            ->whereHas('recommendation', fn (Builder $query) => $query->publiclyVisible());
    }

    /** @return Builder<UserPick> */
    public function activeSupport(User $guide): Builder
    {
        return UserPick::query()
            ->activeSupport()
            ->where('user_id', $guide->id)
            ->whereHas('recommendation', fn (Builder $query) => $query->publiclyVisible()->votable());
    }

    /** @return Builder<UserPick> */
    public function historicalSupport(User $guide): Builder
    {
        return $this->lifetimeSupport($guide)->whereNotNull('released_at');
    }
}
