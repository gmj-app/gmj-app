<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * @extends Factory<User>
 */
class UserFactory extends Factory
{
    /**
     * The current password being used by the factory.
     */
    protected static ?string $password;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $displayName = fake()->firstName();

        return [
            'name' => fake()->name(),
            'public_display_name' => $displayName,
            'public_handle' => fake()->unique()->regexify('[a-z][a-z0-9_-]{5,14}'),
            'public_profile_completed_at' => now(),
            'email' => fake()->unique()->safeEmail(),
            'membership_tier' => 'free',
            'plan_slug' => 'free',
            'email_verified_at' => now(),
            'password' => static::$password ??= Hash::make('password'),
            'remember_token' => Str::random(10),
        ];
    }

    /**
     * Indicate that the model's email address should be unverified.
     */
    public function unverified(): static
    {
        return $this->state(fn (array $attributes) => [
            'email_verified_at' => null,
        ]);
    }

    public function publicProfileIncomplete(): static
    {
        return $this->state(fn (array $attributes) => [
            'public_display_name' => null,
            'public_handle' => null,
            'public_profile_completed_at' => null,
        ]);
    }
}
