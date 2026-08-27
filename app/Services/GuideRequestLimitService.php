<?php

namespace App\Services;

use App\Models\Creator;
use App\Models\CreatorGuideOverride;
use App\Models\Recommendation;
use App\Models\User;

class GuideRequestLimitService
{
    public function getLimit(User $guide, Creator $creator): int
    {
        return (int) (CreatorGuideOverride::query()
            ->where('creator_id', $creator->id)
            ->where('user_id', $guide->id)
            ->value('request_limit') ?? config('request_limits.default', 3));
    }

    public function getActiveRequestCount(User $guide, Creator $creator): int
    {
        return $guide->recommendationsSubmitted()
            ->where('creator_id', $creator->id)
            ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)
            ->whereNull('resource_released_at')
            ->whereIn('status', Recommendation::suggestionConsumingStatuses())
            ->count();
    }

    public function canSubmit(User $guide, Creator $creator): bool
    {
        return $this->getActiveRequestCount($guide, $creator) < $this->getLimit($guide, $creator);
    }

    public function remainingSlots(User $guide, Creator $creator): int
    {
        return max(0, $this->getLimit($guide, $creator) - $this->getActiveRequestCount($guide, $creator));
    }
}
