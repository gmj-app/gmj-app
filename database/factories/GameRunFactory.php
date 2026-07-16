<?php

namespace Database\Factories;

use App\Models\GameRun;
use App\Models\GameRunSession;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameRunFactory extends Factory
{
    protected $model = GameRun::class;

    public function definition(): array
    {
        return ['game_run_session_id' => GameRunSession::factory(), 'user_id' => fn (array $a) => GameRunSession::find($a['game_run_session_id'])->user_id, 'game_day_id' => fn (array $a) => GameRunSession::find($a['game_run_session_id'])->game_day_id, 'score' => 375, 'distance' => 375, 'duration_ms' => 10000, 'validation_status' => 'accepted', 'client_version' => config('daily_journey.version'), 'submitted_at' => now(), 'accepted_at' => now()];
    }
}
