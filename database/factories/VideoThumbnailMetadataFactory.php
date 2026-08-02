<?php

namespace Database\Factories;

use App\Models\CreatorVideo;
use App\Models\VideoThumbnailMetadata;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VideoThumbnailMetadata> */ class VideoThumbnailMetadataFactory extends Factory
{
    public function definition(): array
    {
        return ['creator_video_id' => CreatorVideo::factory(), 'creator_face_visible' => false, 'subject_face_visible' => false, 'contains_question' => false, 'contains_arrow' => false, 'contains_circle' => false, 'contains_flag' => false, 'contains_logo' => false];
    }

    public function reviewed(): static
    {
        return $this->state(fn () => ['reviewed_at' => now()]);
    }

    public function withText(): static
    {
        return $this->state(fn () => ['text_content' => 'Amazing reaction', 'text_word_count' => 2]);
    }
}
