<?php

namespace App\Services;

use App\Models\Recommendation;
use App\Models\UserPick;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class RequestSupportService
{
    /** @return Builder<UserPick> */
    public function activeSupport(Recommendation $request): Builder
    {
        return $request->userPicks()->getQuery();
    }

    /** @return Builder<UserPick> */
    public function historicalSupport(Recommendation $request): Builder
    {
        return $request->historicalUserPicks()->getQuery();
    }

    /** @return Builder<UserPick> */
    public function displaySupport(Recommendation $request): Builder
    {
        return $request->usesHistoricalSupportDisplay()
            ? $this->historicalSupport($request)
            : $this->activeSupport($request);
    }

    public function displaySupportScope(Recommendation $request): string
    {
        return $request->usesHistoricalSupportDisplay() ? 'historical' : 'active';
    }

    /** @return Collection<int, UserPick> */
    public function activeSupporterPreview(Recommendation $request, int $limit = 6, ?int $excludeUserId = null): Collection
    {
        return $this->preview($this->activeSupport($request), $limit, $excludeUserId);
    }

    /** @return Collection<int, UserPick> */
    public function historicalSupporterPreview(Recommendation $request, int $limit = 6, ?int $excludeUserId = null): Collection
    {
        return $this->preview($this->historicalSupport($request), $limit, $excludeUserId);
    }

    /** @return Collection<int, UserPick> */
    public function displaySupporterPreview(Recommendation $request, int $limit = 6, ?int $excludeUserId = null): Collection
    {
        return $this->preview($this->displaySupport($request), $limit, $excludeUserId);
    }

    public function activeVoteQuantity(Recommendation $request): int
    {
        return (int) $this->activeSupport($request)->sum('vote_count');
    }

    public function activeSupporterCount(Recommendation $request): int
    {
        return $this->distinctSupporterCount($this->activeSupport($request));
    }

    public function historicalVoteQuantity(Recommendation $request): int
    {
        return (int) $this->historicalSupport($request)->sum('vote_count');
    }

    public function historicalSupporterCount(Recommendation $request): int
    {
        return $this->distinctSupporterCount($this->historicalSupport($request));
    }

    public function displayVoteQuantity(Recommendation $request): int
    {
        if (! $request->usesHistoricalSupportDisplay()) {
            return $this->activeVoteQuantity($request);
        }

        return $request->vote_total_at_close ?? $this->historicalVoteQuantity($request);
    }

    public function displaySupporterCount(Recommendation $request, ?int $excludeUserId = null): int
    {
        $query = $this->displaySupport($request);
        if ($excludeUserId) {
            $query->where('user_id', '!=', $excludeUserId);
        }

        return $this->distinctSupporterCount($query);
    }

    /** @param Builder<UserPick> $query
     * @return Collection<int, UserPick>
     */
    private function preview(Builder $query, int $limit, ?int $excludeUserId): Collection
    {
        return $query
            ->when($excludeUserId, fn (Builder $query) => $query->where('user_id', '!=', $excludeUserId))
            ->whereHas('user')
            ->with(['user:id,guide_number,public_display_name,public_handle,public_profile_enabled,avatar_url', 'user.guideAccolades'])
            ->oldest('id')
            ->limit(max(1, min($limit, 24)))
            ->get();
    }

    /** @param Builder<UserPick> $query */
    private function distinctSupporterCount(Builder $query): int
    {
        return (clone $query)->whereNotNull('user_id')->distinct()->count('user_id');
    }
}
