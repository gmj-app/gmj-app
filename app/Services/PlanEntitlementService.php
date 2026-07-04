<?php

namespace App\Services;

use App\Models\User;

class PlanEntitlementService
{
    /**
     * @return array<string, array{name: string, creator_favorites_limit: int, suggestions_per_creator_limit: int, upvotes_per_creator_limit: int}>
     */
    public function plans(): array
    {
        return [
            'free' => [
                'name' => 'Free',
                'creator_favorites_limit' => 3,
                'suggestions_per_creator_limit' => 3,
                'upvotes_per_creator_limit' => 3,
            ],
            'plus' => [
                'name' => 'Plus',
                'creator_favorites_limit' => 5,
                'suggestions_per_creator_limit' => 5,
                'upvotes_per_creator_limit' => 5,
            ],
            'pro' => [
                'name' => 'Pro',
                'creator_favorites_limit' => 10,
                'suggestions_per_creator_limit' => 10,
                'upvotes_per_creator_limit' => 10,
            ],
        ];
    }

    /**
     * @return array{name: string, creator_favorites_limit: int, suggestions_per_creator_limit: int, upvotes_per_creator_limit: int}
     */
    public function getPlan(string $slug): array
    {
        return $this->plans()[$slug] ?? $this->plans()['free'];
    }

    public function getUserPlan(User $user): string
    {
        $planSlug = (string) ($user->plan_slug ?? '');

        return array_key_exists($planSlug, $this->plans()) ? $planSlug : 'free';
    }

    public function getUserPlanName(User $user): string
    {
        return $this->getPlan($this->getUserPlan($user))['name'];
    }

    public function getCreatorFavoritesLimit(User $user): int
    {
        return $this->getPlan($this->getUserPlan($user))['creator_favorites_limit'];
    }

    public function getSuggestionsPerCreatorLimit(User $user): int
    {
        return $this->getPlan($this->getUserPlan($user))['suggestions_per_creator_limit'];
    }

    public function getUpvotesPerCreatorLimit(User $user): int
    {
        return $this->getPlan($this->getUserPlan($user))['upvotes_per_creator_limit'];
    }

    /**
     * @return array{label: string, reactors: int, suggestions_per_reactor: int, votes_per_reactor: int}
     */
    public function getLegacyLimitsForUser(User $user): array
    {
        return [
            'label' => $this->getUserPlanName($user),
            'reactors' => $this->getCreatorFavoritesLimit($user),
            'suggestions_per_reactor' => $this->getSuggestionsPerCreatorLimit($user),
            'votes_per_reactor' => $this->getUpvotesPerCreatorLimit($user),
        ];
    }

    /**
     * @return array{plan: string, label: string, creator_favorites_limit: int, suggestions_per_creator_limit: int, upvotes_per_creator_limit: int}
     */
    public function getLimitsForUser(User $user): array
    {
        $plan = $this->getUserPlan($user);
        $limits = $this->getPlan($plan);

        return [
            'plan' => $plan,
            'label' => $limits['name'],
            'creator_favorites_limit' => $limits['creator_favorites_limit'],
            'suggestions_per_creator_limit' => $limits['suggestions_per_creator_limit'],
            'upvotes_per_creator_limit' => $limits['upvotes_per_creator_limit'],
        ];
    }

    /**
     * @return list<string>
     */
    public function validPlanSlugs(): array
    {
        return array_keys($this->plans());
    }
}
