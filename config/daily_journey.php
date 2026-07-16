<?php

return [
    'key' => env('DAILY_JOURNEY_KEY', 'daily-journey'),
    'title' => env('DAILY_JOURNEY_TITLE', 'Daily Journey Challenge'),
    'timezone' => env('DAILY_JOURNEY_TIMEZONE', 'Asia/Manila'),
    'version' => 'daily-journey-v1',
    'supported_versions' => ['daily-journey-v1'],
    'grace_minutes' => 5,
    'session_minutes' => 30,
    'maximum_run_minutes' => 20,
    'collectible_bonus' => 25,
    'starting_speed' => 340,
    'maximum_speed' => 720,
    'acceleration_per_second' => 7,
    'minimum_obstacle_gap' => 390,
    'maximum_obstacle_gap' => 680,
    'leaderboard_size' => 25,
    'cache_seconds' => 30,
    'all_time_enabled' => false,
    'accolade_track' => 'guide_daily_challenge_wins',
];
