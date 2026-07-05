<?php

namespace App\Services\Youtube;

use Illuminate\Support\Collection;

class DescriptionPreview
{
    /**
     * @param  Collection<int, DescriptionChange>  $changes
     */
    public function __construct(
        public readonly Collection $changes,
    ) {}

    public function totalVideos(): int
    {
        return $this->changes->count();
    }

    public function changedVideos(): Collection
    {
        return $this->changes->filter->changed()->values();
    }

    public function skippedVideos(): Collection
    {
        return $this->changes->reject->changed()->values();
    }

    /**
     * @return array<int, array<string, string|null>>
     */
    public function toArray(): array
    {
        return $this->changes
            ->map(fn (DescriptionChange $change) => $change->toArray())
            ->values()
            ->all();
    }
}
