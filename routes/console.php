<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Schedule::command('announcements:publish-due')->everyMinute()->withoutOverlapping();
Schedule::command('game:ensure-current-day')->everyFiveMinutes()->withoutOverlapping();
Schedule::command('game:finalize-days')->everyMinute()->withoutOverlapping();
Schedule::command('game:expire-sessions')->everyFiveMinutes()->withoutOverlapping();
