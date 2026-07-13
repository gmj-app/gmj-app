<?php

namespace App\Services\Accolades;

use App\Models\AccoladeProgress;
use App\Models\User;
use App\Models\UserAccolade;
use Illuminate\Support\Collection;

class AccoladeShowcaseService
{
    public function __construct(private readonly AccoladeDefinitionRepository $definitions) {}

    /** @return array<string, mixed> */
    public function forSubject(string $subjectType, int $subjectId): array
    {
        $awards = UserAccolade::query()->where('subject_type', $subjectType)->where('subject_id', $subjectId)
            ->where('is_public', true)->orderBy('track')->orderBy('level')->get()
            ->map(fn (UserAccolade $award) => ['award' => $award, 'definition' => $this->definitions->find($award->accolade_key)])
            ->filter(fn (array $item) => $item['definition'] !== null)->values();
        $progress = AccoladeProgress::query()->where('subject_type', $subjectType)->where('subject_id', $subjectId)
            ->get()->keyBy('track');

        $tracks = $awards->groupBy(fn (array $item) => $item['award']->track)
            ->map(function (Collection $earned, string $track) use ($progress, $subjectType): array {
                $row = $progress->get($track);
                $highestThreshold = (int) $earned->max(fn (array $item) => $item['award']->threshold_at_award ?? 0);
                $current = (int) ($row?->current_value ?? 0);
                $effective = max($current, $highestThreshold);
                $next = $this->definitions->forTrack($subjectType, $track)
                    ->first(fn (array $definition) => $definition['threshold'] > $effective);

                return [
                    'key' => $track,
                    'label' => config("accolades.tracks.{$track}.label", str($track)->headline()),
                    'earned' => $earned,
                    'current_value' => $current,
                    'effective_value' => $effective,
                    'next' => $next,
                ];
            })->values();

        $featured = $awards->filter(fn (array $item) => $item['award']->is_featured)
            ->sortBy(fn (array $item) => $item['award']->featured_order ?? 99)->values();
        if ($featured->isEmpty()) {
            $featured = $subjectType === 'creator'
                ? $this->defaultCreatorFeatures($awards)
                : $awards->sortByDesc(fn (array $item) => [$item['definition']['level'], $item['definition']['display_order']])->take(1)->values();
        }

        $legacy = $subjectType === 'guide' ? User::query()->find($subjectId)?->guideAvatarAccolade() : null;

        return ['awards' => $awards, 'tracks' => $tracks, 'featured' => $featured, 'legacy' => $legacy];
    }

    /** @param Collection<int, array<string, mixed>> $awards */
    private function defaultCreatorFeatures(Collection $awards): Collection
    {
        return collect(['creator_community_publications', 'creator_consistency', 'creator_community_reach'])
            ->map(fn (string $track) => $awards->where(fn (array $item) => $item['award']->track === $track)
                ->sortByDesc(fn (array $item) => $item['award']->level)->first())
            ->filter()->values();
    }
}
