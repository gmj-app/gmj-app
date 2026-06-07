<?php

namespace Database\Factories;

use App\Models\Creator;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Creator>
 */
class CreatorFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $displayName = fake()->unique()->name();

        return [
            'slug' => fake()->unique()->slug(),
            'display_name' => $displayName,
            'channel_url' => fake()->optional()->url(),
        ];
    }
}
