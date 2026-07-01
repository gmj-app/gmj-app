<?php

namespace App\Services;

use App\Models\Creator;
use App\Models\CreatorTag;
use App\Models\Recommendation;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class CreatorTagService
{
    public const DEFAULT_TAGS = [
        'Topic Idea',
        'YouTube Link',
        'Deep Dive',
        'Quick Take',
        'Tutorial',
        'Review',
        'Community Favorite',
        'Time Sensitive',
        'Beginner Friendly',
        'Advanced',
    ];

    public const MAX_TAGS_PER_RECOMMENDATION = 5;

    public const MAX_TAGS_PER_CREATOR = 100;

    public function createDefaults(Creator $creator): void
    {
        collect(self::DEFAULT_TAGS)
            ->chunk(self::MAX_TAGS_PER_RECOMMENDATION)
            ->each(fn (Collection $tags) => $this->resolve($creator, $tags));
    }

    public function syncFromCommaSeparated(
        Creator $creator,
        Recommendation $recommendation,
        ?string $input,
    ): void {
        abort_unless($recommendation->creator_id === $creator->id, 404);

        $tags = $this->resolve($creator, explode(',', (string) $input));

        if ($tags->count() > self::MAX_TAGS_PER_RECOMMENDATION) {
            throw ValidationException::withMessages([
                'tags' => 'Add no more than 5 tags to a recommendation.',
            ]);
        }

        $recommendation->creatorTags()->sync($tags->pluck('id')->all());
    }

    /**
     * @param  iterable<int, string>  $names
     * @return Collection<int, CreatorTag>
     */
    public function resolve(Creator $creator, iterable $names): Collection
    {
        $normalized = collect($names)
            ->map(fn (string $name) => preg_replace('/\s+/', ' ', trim(strip_tags($name))))
            ->filter()
            ->map(function (string $name): array {
                if (Str::length($name) > 50) {
                    throw ValidationException::withMessages([
                        'tags' => 'Each tag must be 50 characters or fewer.',
                    ]);
                }

                $slug = Str::slug($name);

                if ($slug === '') {
                    throw ValidationException::withMessages([
                        'tags' => 'Tags must contain letters or numbers.',
                    ]);
                }

                return ['name' => $name, 'slug' => $slug];
            })
            ->unique('slug')
            ->values();

        if ($normalized->count() > self::MAX_TAGS_PER_RECOMMENDATION) {
            throw ValidationException::withMessages([
                'tags' => 'Add no more than 5 tags to a recommendation.',
            ]);
        }

        $existing = $creator->creatorTags()
            ->whereIn('slug', $normalized->pluck('slug'))
            ->get()
            ->keyBy('slug');
        $missing = $normalized->reject(fn (array $tag) => $existing->has($tag['slug']));

        if ($creator->creatorTags()->count() + $missing->count() > self::MAX_TAGS_PER_CREATOR) {
            throw ValidationException::withMessages([
                'tags' => 'This creator has reached the 100 tag limit.',
            ]);
        }

        foreach ($missing as $tag) {
            $existing->put(
                $tag['slug'],
                $creator->creatorTags()->create($tag),
            );
        }

        return $normalized
            ->map(fn (array $tag) => $existing->get($tag['slug']))
            ->filter()
            ->values();
    }
}
