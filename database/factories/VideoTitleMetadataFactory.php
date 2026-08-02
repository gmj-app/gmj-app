<?php

namespace Database\Factories;

use App\Models\CreatorVideo;
use App\Models\VideoTitleMetadata;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VideoTitleMetadata> */ class VideoTitleMetadataFactory extends Factory
{
    public function definition(): array
    {
        return ['creator_video_id' => CreatorVideo::factory(), 'character_count' => 20, 'word_count' => 4, 'contains_question' => false, 'contains_exclamation' => false, 'contains_pipe' => false, 'contains_parentheses' => false, 'contains_all_caps' => false];
    }

    public function reviewed(): static
    {
        return $this->state(fn () => ['title_template' => 'other', 'reviewed_at' => now()]);
    }
}
