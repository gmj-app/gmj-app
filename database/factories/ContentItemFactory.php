<?php

namespace Database\Factories;

use App\Models\ContentItem;
use App\Models\CreatorChannel;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ContentItem> */ class ContentItemFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->sentence(3);

        return ['creator_channel_id' => CreatorChannel::factory(), 'subject_id' => null, 'name' => $name, 'normalized_name' => Str::lower($name), 'slug' => Str::slug($name), 'release_date' => fake()->optional()->date(), 'is_active' => true];
    }
}
