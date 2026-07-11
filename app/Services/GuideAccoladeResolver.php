<?php

namespace App\Services;

use App\Models\GuideAccolade;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class GuideAccoladeResolver
{
    public const CACHE_KEY = 'guide_accolades.active_rule_tiers';

    public function resolveForGuide(User $guide): ?GuideAccolade
    {
        return $this->activeTiers()
            ->first(fn (GuideAccolade $tier): bool => $this->isEligible($tier, $guide));
    }

    /** @return Collection<int, GuideAccolade> */
    public function activeTiers(): Collection
    {
        return Cache::rememberForever(self::CACHE_KEY, fn (): Collection => GuideAccolade::query()
            ->where('is_active', true)
            ->whereNotNull('rule_type')
            ->orderByDesc('priority')
            ->orderBy('id')
            ->get());
    }

    public function forgetCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function isEligible(GuideAccolade $tier, User $guide): bool
    {
        return $tier->isCurrentlyActive() && match ($tier->rule_type) {
            GuideAccolade::RULE_GUIDE_NUMBER_RANGE => $guide->guide_number !== null
                && ($tier->minimum_guide_number === null || $guide->guide_number >= $tier->minimum_guide_number)
                && ($tier->maximum_guide_number === null || $guide->guide_number <= $tier->maximum_guide_number),
            default => false,
        };
    }
}
