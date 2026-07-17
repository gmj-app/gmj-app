<?php

namespace App\ViewModels;

use App\Models\User;
use App\Models\UserAccolade;
use App\Services\Accolades\AccoladeDefinitionRepository;
use Illuminate\Support\Collection;

class PublicGuideAccoladeViewModel
{
    public function __construct(private readonly AccoladeDefinitionRepository $definitions) {}

    /** @return array<string, mixed> */
    public function forGuide(User $guide): array
    {
        $awards = UserAccolade::query()
            ->where('subject_type', 'guide')
            ->where('subject_id', $guide->id)
            ->where('is_public', true)
            ->orderBy('track')
            ->orderBy('level')
            ->get()
            ->map(fn (UserAccolade $award) => $this->present($award))
            ->filter()
            ->values();

        $featured = $awards->first(fn (array $item) => $item['award']->is_featured)
            ?? $awards->sortByDesc(fn (array $item) => sprintf('%04d-%04d', $item['level'], $item['display_order']))->first();

        $highestByTrack = $awards
            ->groupBy('track_key')
            ->map(fn (Collection $track) => $track->sortByDesc('level')->first())
            ->sortBy('track_order')
            ->take(6)
            ->values();

        return [
            'featured' => $featured,
            'early_guide' => $guide->guideAvatarAccolade(),
            'highest_by_track' => $highestByTrack,
            'all' => $awards->sortBy([['track_order', 'asc'], ['level', 'desc']])->values(),
            'grouped' => $awards->sortByDesc('level')->groupBy('track_key')->sortBy(fn ($items) => $items->first()['track_order']),
            'total' => $awards->count(),
            'has_recognition' => $awards->isNotEmpty() || $guide->guideAvatarAccolade() !== null,
        ];
    }

    /** @return array<string, mixed>|null */
    private function present(UserAccolade $award): ?array
    {
        $definition = $this->definitions->find($award->accolade_key);

        if (! $definition || ($definition['subject_type'] ?? null) !== 'guide' || ! ($definition['active'] ?? true)) {
            return null;
        }

        $track = config("accolades.tracks.{$award->track}", []);

        return [
            'award' => $award,
            'definition' => $definition,
            'name' => $definition['name'],
            'description' => $definition['description'],
            'level' => (int) $definition['level'],
            'display_order' => (int) $definition['display_order'],
            'track_key' => $award->track,
            'track_label' => $track['label'] ?? str($award->track)->headline()->toString(),
            'track_order' => (int) ($track['display_order'] ?? 999),
            'theme' => $this->theme($track['accent'] ?? $definition['badge_style_key'] ?? 'slate'),
            'awarded_date' => $award->awarded_at?->format('M j, Y'),
        ];
    }

    /** @return array<string, string> */
    private function theme(string $accent): array
    {
        return match ($accent) {
            'violet' => ['border' => 'border-violet-200 dark:border-violet-800', 'surface' => 'bg-violet-50/70 dark:bg-violet-950/25', 'text' => 'text-violet-700 dark:text-violet-300'],
            'amber' => ['border' => 'border-amber-200 dark:border-amber-800', 'surface' => 'bg-amber-50/70 dark:bg-amber-950/25', 'text' => 'text-amber-700 dark:text-amber-300'],
            'emerald' => ['border' => 'border-emerald-200 dark:border-emerald-800', 'surface' => 'bg-emerald-50/70 dark:bg-emerald-950/25', 'text' => 'text-emerald-700 dark:text-emerald-300'],
            'sky' => ['border' => 'border-sky-200 dark:border-sky-800', 'surface' => 'bg-sky-50/70 dark:bg-sky-950/25', 'text' => 'text-sky-700 dark:text-sky-300'],
            default => ['border' => 'border-slate-200 dark:border-slate-700', 'surface' => 'bg-slate-50 dark:bg-slate-950/40', 'text' => 'text-slate-700 dark:text-slate-300'],
        };
    }
}
