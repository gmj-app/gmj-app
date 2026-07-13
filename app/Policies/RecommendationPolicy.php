<?php

namespace App\Policies;

use App\Models\Recommendation;
use App\Models\User;

class RecommendationPolicy
{
    public function updateOwnPresentation(User $user, Recommendation $recommendation): bool
    {
        return (int) $recommendation->submitted_by === (int) $user->id
            && $recommendation->canGuideEditPresentation();
    }
}
