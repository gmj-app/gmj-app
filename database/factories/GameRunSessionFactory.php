<?php

namespace Database\Factories;

use App\Models\GameDay;
use App\Models\GameRunSession;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameRunSessionFactory extends Factory
{
    protected $model = GameRunSession::class;

    public function definition(): array
    {
        return ['public_token' => fake()->uuid(), 'user_id' => User::factory(), 'game_day_id' => GameDay::factory(), 'game_key' => config('daily_journey.key'), 'status' => 'issued', 'random_seed' => fake()->numberBetween(1, PHP_INT_MAX), 'game_version' => config('daily_journey.version'), 'issued_at' => now(), 'expires_at' => now()->addMinutes(30)];
    }
}
