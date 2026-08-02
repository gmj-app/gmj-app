<?php

namespace Database\Factories;

use App\Models\CreatorChannel;
use App\Models\CreatorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CreatorChannel> */
class CreatorChannelFactory extends Factory
{
    public function definition(): array
    {
        return ['creator_profile_id' => CreatorProfile::factory(), 'platform' => 'youtube', 'platform_channel_id' => 'UC'.fake()->unique()->regexify('[A-Za-z0-9_-]{22}'), 'handle' => '@'.fake()->unique()->userName(), 'channel_name' => fake()->company(), 'subject_label' => 'Subject', 'content_item_label' => 'Content Item', 'category_label' => 'Category', 'default_publish_timezone' => 'America/New_York', 'is_active' => true];
    }
}
