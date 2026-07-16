<?php

namespace App\Console\Commands;

use App\Services\DailyJourney\GameDayService;
use Illuminate\Console\Command;

class EnsureCurrentGameDay extends Command
{
    protected $signature = 'game:ensure-current-day';

    protected $description = 'Ensure the current Asia/Manila Daily Journey game day exists';

    public function handle(GameDayService $days): int
    {
        $day = $days->current();
        $this->info("Game day {$day->local_date->toDateString()} is {$day->status}.");

        return self::SUCCESS;
    }
}
