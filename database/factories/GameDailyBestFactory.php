<?php

namespace Database\Factories;

use App\Models\GameDailyBest;
use App\Models\GameRun;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameDailyBestFactory extends Factory
{
    protected $model = GameDailyBest::class;

    public function definition(): array
    {
        return ['game_run_id' => GameRun::factory(), 'user_id' => fn (array $a) => GameRun::find($a['game_run_id'])->user_id, 'game_day_id' => fn (array $a) => GameRun::find($a['game_run_id'])->game_day_id, 'score' => 375, 'distance' => 375, 'duration_ms' => 10000, 'accepted_at' => now()];
    }
}
