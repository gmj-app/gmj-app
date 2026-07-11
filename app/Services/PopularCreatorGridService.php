<?php

namespace App\Services;

use Illuminate\Support\Collection;

class PopularCreatorGridService
{
    public function compose(Collection $creators, Collection $advertisements, bool $includeAddCreator = true): Collection
    {
        $items = $creators->values()->map(fn ($creator) => ['type' => 'creator', 'item' => $creator]);

        $collisions = [];
        foreach ($advertisements->sortBy([['placement', 'asc'], ['id', 'asc']])->values() as $advertisement) {
            $collisionOffset = $collisions[$advertisement->placement] ?? 0;
            $index = min(max(0, $advertisement->placement - 1 + $collisionOffset), $items->count());
            $items->splice($index, 0, [['type' => 'advertisement', 'item' => $advertisement]]);
            $collisions[$advertisement->placement] = $collisionOffset + 1;
        }

        if ($includeAddCreator) {
            $items->push(['type' => 'add_creator', 'item' => null]);
        }

        return $items->values();
    }
}
