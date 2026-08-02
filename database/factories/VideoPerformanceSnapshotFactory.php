<?php

namespace Database\Factories;

use App\Enums\PerformanceSnapshotSource;
use App\Models\CreatorVideo;
use App\Models\VideoPerformanceSnapshot;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<VideoPerformanceSnapshot> */
class VideoPerformanceSnapshotFactory extends Factory
{
    public function definition(): array
    {
        return ['creator_video_id' => CreatorVideo::factory(), 'snapshot_date' => fake()->date(), 'source' => PerformanceSnapshotSource::YouTubeStudio, 'views' => fake()->numberBetween(100, 500000), 'impressions' => fake()->numberBetween(1000, 2000000), 'impressions_ctr' => fake()->randomFloat(2, 0, 20), 'watch_time_minutes' => fake()->randomFloat(2, 10, 100000), 'average_view_duration_seconds' => fake()->numberBetween(30, 1800), 'average_percentage_viewed' => fake()->randomFloat(2, 1, 100), 'likes' => fake()->numberBetween(0, 30000), 'comments' => fake()->numberBetween(0, 5000), 'shares' => fake()->numberBetween(0, 2000), 'subscribers_gained' => fake()->numberBetween(0, 3000), 'subscribers_lost' => fake()->numberBetween(0, 100), 'estimated_revenue' => fake()->randomFloat(2, 0, 5000), 'rpm' => fake()->randomFloat(2, 0, 20), 'cpm' => fake()->randomFloat(2, 0, 40), 'hype_points' => fake()->optional()->numberBetween(0, 100000), 'views_first_24_hours' => fake()->optional()->numberBetween(0, 100000), 'views_first_7_days' => fake()->optional()->numberBetween(0, 250000), 'views_first_28_days' => fake()->optional()->numberBetween(0, 500000)];
    }
}
