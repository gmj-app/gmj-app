<?php

namespace App\Services\Accolades;

use Illuminate\Support\Collection;
use InvalidArgumentException;

class AccoladeDefinitionRepository
{
    /** @return Collection<int, array<string, mixed>> */
    public function all(): Collection
    {
        return collect(config('accolades.definitions', []));
    }

    /** @return Collection<int, array<string, mixed>> */
    public function forTrack(string $subjectType, string $track, bool $activeOnly = true): Collection
    {
        return $this->all()
            ->where('subject_type', $subjectType)
            ->where('track', $track)
            ->when($activeOnly, fn (Collection $items) => $items->where('active', true))
            ->sortBy([['threshold', 'asc'], ['level', 'asc']])
            ->values();
    }

    /** @return array<string, mixed>|null */
    public function find(?string $key): ?array
    {
        if (! $key) {
            return null;
        }

        return $this->all()->firstWhere('key', $key);
    }

    public function validate(): void
    {
        $definitions = $this->all();
        if ($definitions->pluck('key')->duplicates()->isNotEmpty()) {
            throw new InvalidArgumentException('Accolade keys must be unique.');
        }

        foreach ($definitions->groupBy(fn (array $item) => $item['subject_type'].':'.$item['track']) as $items) {
            $ordered = $items->sortBy('level')->values();
            if ($ordered->pluck('level')->duplicates()->isNotEmpty()
                || $ordered->pluck('threshold')->values()->all() !== $ordered->pluck('threshold')->sort()->values()->all()) {
                throw new InvalidArgumentException('Accolade levels and thresholds must increase within each track.');
            }
        }

        $icons = config('accolades.icons', []);
        $styles = config('accolades.styles', []);
        foreach ($definitions as $definition) {
            if (! in_array($definition['icon_key'], $icons, true) || ! in_array($definition['badge_style_key'], $styles, true)) {
                throw new InvalidArgumentException("Accolade {$definition['key']} uses an unsupported icon or style.");
            }
        }
    }
}
