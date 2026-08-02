<?php

namespace Database\Factories;

use App\Models\CreatorProfile;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CreatorProfile> */
class CreatorProfileFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return ['user_id' => null, 'display_name' => $name, 'slug' => fake()->unique()->slug(), 'timezone' => 'America/New_York', 'default_currency' => 'USD'];
    }
}
