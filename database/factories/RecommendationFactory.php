<?php

namespace Database\Factories;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Recommendation>
 */
class RecommendationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $videoId = fake()->unique()->regexify('[A-Za-z0-9_-]{11}');

        return [
            'creator_id' => Creator::factory(),
            'submitted_by' => User::factory(),
            'youtube_url' => "https://www.youtube.com/watch?v={$videoId}",
            'youtube_video_id' => $videoId,
            'title' => fake()->sentence(4),
            'artist' => fake()->optional()->name(),
            'category' => fake()->optional()->randomElement(['music', 'comedy', 'documentary']),
            'reason' => fake()->optional()->paragraph(),
            'status' => 'pending',
            'is_pinned' => false,
            'published_reaction_url' => null,
        ];
    }
}
