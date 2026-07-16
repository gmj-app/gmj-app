<?php

namespace Database\Factories;

use App\Models\GameDay;
use Illuminate\Database\Eloquent\Factories\Factory;

class GameDayFactory extends Factory
{
    protected $model = GameDay::class;

    public function definition(): array
    {
        $start = now(config('daily_journey.timezone'))->startOfDay();

        return ['game_key' => config('daily_journey.key'), 'local_date' => $start->toDateString(), 'timezone' => config('daily_journey.timezone'), 'starts_at' => $start->utc(), 'ends_at' => $start->copy()->addDay()->utc(), 'status' => 'open'];
    }
}
