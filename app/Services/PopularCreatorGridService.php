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
            $creatorIndex = $items->search(fn (array $item): bool => $item['type'] === 'creator'
                && strcasecmp(trim((string) $item['item']->display_name), trim((string) $advertisement->advertiser_name)) === 0);
            $sponsoredCreator = $creatorIndex === false ? null : $items->pull($creatorIndex)['item'];
            $items = $items->values();
            $collisionOffset = $collisions[$advertisement->placement] ?? 0;
            $index = min(max(0, $advertisement->placement - 1 + $collisionOffset), $items->count());
            $items->splice($index, 0, [[
                'type' => $sponsoredCreator ? 'sponsored_creator' : 'advertisement',
                'item' => $sponsoredCreator ?: $advertisement,
                'advertisement' => $advertisement,
            ]]);
            $collisions[$advertisement->placement] = $collisionOffset + 1;
        }

        if ($includeAddCreator) {
            $items->push(['type' => 'add_creator', 'item' => null]);
        }

        return $items->values();
    }
}
