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
            'submissions_open' => true,
            'recommendation_approval_mode' => Creator::APPROVAL_MODE_AUTO,
            'status' => 'active',
        ];
    }

    public function autoApproving(): static
    {
        return $this->state(fn (): array => [
            'recommendation_approval_mode' => Creator::APPROVAL_MODE_AUTO,
        ]);
    }

    public function moderated(): static
    {
        return $this->state(fn (): array => [
            'recommendation_approval_mode' => Creator::APPROVAL_MODE_MANUAL,
        ]);
    }
}
