<?php

namespace Database\Factories;

use App\Models\GameDailyChampion;
use App\Models\GameRun;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameDailyChampionFactory extends Factory
{
    protected $model = GameDailyChampion::class;

    public function definition(): array
    {
        return ['game_run_id' => GameRun::factory(), 'user_id' => fn (array $a) => GameRun::find($a['game_run_id'])->user_id, 'game_day_id' => fn (array $a) => GameRun::find($a['game_run_id'])->game_day_id, 'local_date' => now(config('daily_journey.timezone'))->toDateString(), 'score' => 375, 'distance' => 375, 'finalized_at' => now()];
    }
}
