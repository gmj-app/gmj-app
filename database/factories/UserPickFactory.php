<?php

namespace Database\Factories;

use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<UserPick>
 */
class UserPickFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'recommendation_id' => Recommendation::factory(),
            'creator_id' => fn (array $attributes) => Recommendation::findOrFail(
                $attributes['recommendation_id']
            )->creator_id,
            'vote_count' => 1,
            'rank' => fake()->optional()->numberBetween(1, 10),
        ];
    }
}
