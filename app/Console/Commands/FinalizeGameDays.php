<?php

namespace App\Console\Commands;

use App\Services\DailyJourney\FinalizationService;
use App\Services\DailyJourney\GameDayService;
use Illuminate\Console\Command;

class FinalizeGameDays extends Command
{
    protected $signature = 'game:finalize-days';

    protected $description = 'Idempotently finalize Daily Journey days after their grace period';

    public function handle(GameDayService $days, FinalizationService $finalizer): int
    {
        $count = 0;
        $days->finalizableQuery()->eachById(function ($day) use ($finalizer, &$count) {
            $champion = $finalizer->finalize($day);
            $finalizer->deliver($champion);
            $count++;
        });
        $days->current();
        $this->info("Finalized {$count} game day(s).");

        return self::SUCCESS;
    }
}
