<?php

namespace App\Services\DailyJourney;

use App\Models\GameDay;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;

class GameDayService
{
    public function current(): GameDay
    {
        return $this->forInstant(CarbonImmutable::now());
    }

    public function forInstant(CarbonImmutable $instant): GameDay
    {
        $tz = config('daily_journey.timezone');
        $local = $instant->setTimezone($tz);
        $start = $local->startOfDay();
        $date = $start->toDateString();

        $existing = GameDay::query()
            ->where('game_key', config('daily_journey.key'))
            ->whereDate('local_date', $date)
            ->first();

        if ($existing) {
            return $existing;
        }

        try {
            return GameDay::query()->create([
                'game_key' => config('daily_journey.key'),
                'local_date' => $date,
                'timezone' => $tz,
                'starts_at' => $start->utc(),
                'ends_at' => $start->addDay()->utc(),
                'status' => 'open',
            ]);
        } catch (QueryException $exception) {
            return GameDay::query()
                ->where('game_key', config('daily_journey.key'))
                ->whereDate('local_date', $date)
                ->firstOr(fn () => throw $exception);
        }
    }

    public function finalizableQuery()
    {
        return GameDay::query()->whereIn('status', ['open', 'finalizing'])->where('ends_at', '<=', now()->subMinutes((int) config('daily_journey.grace_minutes')));
    }
}
