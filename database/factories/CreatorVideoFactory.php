<?php

namespace Database\Factories;

use App\Enums\VideoContentType;
use App\Enums\VideoCopyrightStatus;
use App\Enums\VideoFormat;
use App\Models\CreatorChannel;
use App\Models\CreatorVideo;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CreatorVideo> */
class CreatorVideoFactory extends Factory
{
    public function definition(): array
    {
        $id = fake()->unique()->regexify('[A-Za-z0-9_-]{11}');

        return ['creator_channel_id' => CreatorChannel::factory(), 'platform_video_id' => $id, 'title' => fake()->sentence(6), 'description' => fake()->optional()->paragraph(), 'video_url' => "https://www.youtube.com/watch?v={$id}", 'thumbnail_url' => "https://i.ytimg.com/vi/{$id}/hqdefault.jpg", 'published_at' => fake()->dateTimeBetween('-3 years'), 'duration_seconds' => fake()->numberBetween(60, 7200), 'video_format' => VideoFormat::Long, 'content_type' => VideoContentType::Reaction, 'copyright_status' => VideoCopyrightStatus::Unknown, 'is_premiere' => false, 'is_live' => false, 'is_short' => false, 'is_documentary' => false, 'is_interview' => false, 'is_monetized' => fake()->optional()->boolean()];
    }
}
