<?php

namespace App\Services\DailyJourney;

class HomepageGameService
{
    public function __construct(private GameDayService $days, private LeaderboardService $leaderboard) {}

    public function data(?int $userId): array
    {
        $day = $this->days->current();
        $board = $this->leaderboard->rows($day, $userId);

        return ['day' => $day, 'leader' => $board['rows'][0] ?? null, 'me' => $board['me']];
    }
}
