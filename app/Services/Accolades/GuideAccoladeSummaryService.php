<?php

namespace App\Services\Accolades;

use App\Models\AccoladeProgress;
use App\Models\User;
use App\Models\UserAccolade;
use Illuminate\Support\Collection;

class GuideAccoladeSummaryService
{
    public const DASHBOARD_TRACKS = [
        'guide_requests_published',
        'guide_supported_publications',
        'guide_creator_exploration',
    ];

    public function __construct(private readonly AccoladeDefinitionRepository $definitions) {}

    /** @return array<string, mixed> */
    public function forDashboard(User $user): array
    {
        return $this->build($user, self::DASHBOARD_TRACKS, true, false);
    }

    /** @return array<string, mixed> */
    public function forPrivatePage(User $user): array
    {
        $tracks = collect(config('accolades.tracks', []))
            ->filter(fn (array $track) => $track['subject_type'] === 'guide')
            ->sortBy('display_order')
            ->keys()->all();

        return $this->build($user, $tracks, false, true);
    }

    /**
     * @param  array<int, string>  $requestedTracks
     * @return array<string, mixed>
     */
    private function build(User $user, array $requestedTracks, bool $recentLimit, bool $includeEmptyTracks): array
    {
        $awards = UserAccolade::query()
            ->where('user_id', $user->id)
            ->where('subject_type', 'guide')
            ->where('subject_id', $user->id)
            ->latest('awarded_at')
            ->latest('id')
            ->get()
            ->map(fn (UserAccolade $award) => [
                'award' => $award,
                'definition' => $this->definitions->find($award->accolade_key),
            ])
            ->filter(fn (array $item) => $item['definition'] !== null)
            ->values();

        $progress = AccoladeProgress::query()
            ->where('subject_type', 'guide')
            ->where('subject_id', $user->id)
            ->whereIn('track', $requestedTracks)
            ->get()
            ->keyBy('track');

        $manualFeature = $awards->first(fn (array $item) => $item['award']->is_featured
            && $item['award']->is_public
            && (bool) data_get($item, 'award.metadata.manual_featured', false));
        $fallbackFeature = $awards->filter(fn (array $item) => $item['award']->is_public)
            ->sortByDesc(fn (array $item) => $this->priority($item['definition']))
            ->first();
        $featured = $manualFeature ?: $fallbackFeature;

        $tracks = collect($requestedTracks)->map(function (string $track) use ($awards, $progress, $includeEmptyTracks): ?array {
            $earned = $awards->filter(fn (array $item) => $item['award']->track === $track)
                ->sortBy(fn (array $item) => $item['award']->level)->values();
            $row = $progress->get($track);
            if (! $includeEmptyTracks && ! $row && $earned->isEmpty()) {
                return null;
            }

            $highestEarned = $earned->sortByDesc(fn (array $item) => $item['award']->level)->first();
            $highestThreshold = (int) $earned->max(fn (array $item) => $item['award']->threshold_at_award ?? $item['definition']['threshold']);
            $current = (int) ($row?->current_value ?? 0);
            $effective = max($current, $highestThreshold);
            $next = $this->definitions->forTrack('guide', $track)
                ->first(fn (array $definition) => $definition['threshold'] > $effective);

            return [
                'key' => $track,
                'label' => config("accolades.tracks.{$track}.label", str($track)->headline()),
                'earned' => $earned,
                'highest_earned' => $highestEarned,
                'current_value' => $current,
                'effective_value' => $effective,
                'next' => $next,
                'progress_percent' => $next ? min(100, (int) round(($effective / max(1, $next['threshold'])) * 100)) : 100,
            ];
        })->filter()->values();

        $recent = $awards
            ->reject(fn (array $item) => $featured && $item['award']->id === $featured['award']->id)
            ->when($recentLimit, fn (Collection $items) => $items->take(3))
            ->values();

        return [
            'has_earned' => $awards->isNotEmpty(),
            'featured' => $featured,
            'tracks' => $tracks,
            'recent' => $recent,
            'awards' => $awards,
        ];
    }

    /** @param array<string, mixed> $definition */
    private function priority(array $definition): int
    {
        return ((int) $definition['level'] * 10_000)
            + (int) $definition['threshold']
            + (int) $definition['display_order'];
    }
}
