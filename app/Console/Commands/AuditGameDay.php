<?php

namespace App\Console\Commands;

use App\Models\GameDay;
use Illuminate\Console\Command;

class AuditGameDay extends Command
{
    protected $signature = 'game:audit-day {--date=}';

    protected $description = 'Read-only audit of a Daily Journey day';

    public function handle(): int
    {
        $day = GameDay::query()->where('game_key', config('daily_journey.key'))->whereDate('local_date', $this->option('date') ?: now(config('daily_journey.timezone'))->toDateString())->first();
        if (! $day) {
            $this->error('Game day not found.');

            return self::FAILURE;
        }$this->table(['Date', 'Status', 'Runs', 'Bests', 'Winner'], [[$day->local_date->toDateString(), $day->status, $day->runs()->count(), $day->dailyBests()->count(), $day->winner_score ?? '—']]);

        return self::SUCCESS;
    }
}
