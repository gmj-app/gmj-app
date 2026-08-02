<?php

namespace Database\Factories;

use App\Models\CreatorVideo;
use App\Models\VideoEditorialMetadata;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VideoEditorialMetadata> */ class VideoEditorialMetadataFactory extends Factory
{
    public function definition(): array
    {
        return ['creator_video_id' => CreatorVideo::factory()];
    }

    public function reviewed(): static
    {
        return $this->state(fn () => ['creator_sentiment' => 'positive', 'reaction_style' => 'emotional', 'reviewed_at' => now()]);
    }

    public function positive(): static
    {
        return $this->state(fn () => ['creator_sentiment' => 'positive']);
    }

    public function negative(): static
    {
        return $this->state(fn () => ['creator_sentiment' => 'negative']);
    }
}
