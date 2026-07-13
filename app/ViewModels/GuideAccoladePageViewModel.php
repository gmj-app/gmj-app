<?php

namespace App\ViewModels;

use App\Models\User;
use App\Services\Accolades\GuideAccoladeSummaryService;
use App\Services\GuideAccoladeService;

class GuideAccoladePageViewModel
{
    public function __construct(
        private readonly GuideAccoladeSummaryService $summaries,
        private readonly GuideAccoladeService $legacyAccolades,
    ) {}

    /** @return array<string, mixed> */
    public function forUser(User $user): array
    {
        $summary = $this->summaries->forPrivatePage($user);
        $earlyGuide = $this->legacyAccolades->resolveEarlyGuideAccolade($user->guide_number);

        if ($earlyGuide) {
            $tier = collect(config('guide_accolades.early_guide_tiers', []))
                ->firstWhere('key', $earlyGuide['key']);
            $earlyGuide['description'] = $tier['description'] ?? null;
        }

        $tracks = $summary['tracks']->map(function (array $track): array {
            $configuration = config("accolades.tracks.{$track['key']}", []);
            $highestEarned = $track['highest_earned'];

            return [
                ...$track,
                'icon_key' => $configuration['icon_key'] ?? 'trail-marker',
                'accent' => $configuration['accent'] ?? 'slate',
                'display_order' => (int) ($configuration['display_order'] ?? 999),
                'earned_date' => data_get($highestEarned, 'award.awarded_at'),
                'completed' => $track['next'] === null,
            ];
        })->sortBy('display_order')->values();

        return [
            ...$summary,
            'tracks' => $tracks,
            'feature_options' => $summary['awards']
                ->filter(fn (array $item) => $item['award']->is_public)
                ->values(),
            'early_guide' => $earlyGuide,
        ];
    }
}
