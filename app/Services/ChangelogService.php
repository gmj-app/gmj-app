<?php

namespace App\Services;

use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;

class ChangelogService
{
    public function path(): string
    {
        return (string) config('changelog.path', storage_path('app/changelog.json'));
    }

    /**
     * @return array{available: bool, entries: Collection, groups: Collection}
     */
    public function get(): array
    {
        if (! File::exists($this->path())) {
            return $this->result(false, collect());
        }

        $decoded = json_decode((string) File::get($this->path()), true);

        if (! is_array($decoded)) {
            return $this->result(false, collect());
        }

        $entries = collect($decoded)
            ->filter(fn ($entry): bool => is_array($entry)
                && is_string($entry['subject'] ?? null)
                && is_string($entry['date'] ?? null))
            ->map(function (array $entry): ?array {
                try {
                    $date = Carbon::parse($entry['date']);
                } catch (\Throwable) {
                    return null;
                }

                return [
                    'hash' => is_string($entry['hash'] ?? null) ? mb_substr($entry['hash'], 0, 12) : null,
                    'date' => $date,
                    'subject' => str($entry['subject'])->squish()->limit((int) config('changelog.subject_max_length', 180))->toString(),
                ];
            })
            ->filter()
            ->sortByDesc('date')
            ->take((int) config('changelog.limit', 50))
            ->values();

        return $this->result(true, $entries);
    }

    private function result(bool $available, Collection $entries): array
    {
        return [
            'available' => $available,
            'entries' => $entries,
            'groups' => $entries->groupBy(fn (array $entry): string => $entry['date']->format('Y-m-d')),
        ];
    }
}
