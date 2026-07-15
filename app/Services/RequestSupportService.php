<?php

namespace App\Services;

use App\Models\Recommendation;
use App\Models\UserPick;
use Illuminate\Database\Eloquent\Builder;

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

    public function displaySupporterCount(Recommendation $request): int
    {
        return $request->usesHistoricalSupportDisplay()
            ? $this->historicalSupporterCount($request)
            : $this->activeSupporterCount($request);
    }

    /** @param Builder<UserPick> $query */
    private function distinctSupporterCount(Builder $query): int
    {
        return (clone $query)->whereNotNull('user_id')->distinct()->count('user_id');
    }
}
