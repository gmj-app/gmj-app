<?php

namespace App\Services\DailyJourney;

class RunValidationService
{
    public function validate(array $data, $session): array
    {
        $flags = [];
        $duration = (int) $data['duration_ms'];
        $distance = (float) $data['distance'];
        $seconds = $duration / 1000;
        $start = (float) config('daily_journey.starting_speed');
        $accel = (float) config('daily_journey.acceleration_per_second');
        $max = (float) config('daily_journey.maximum_speed');
        $timeToMax = ($max - $start) / $accel;
        $pixels = $seconds <= $timeToMax ? $start * $seconds + 0.5 * $accel * $seconds * $seconds : $start * $timeToMax + 0.5 * $accel * $timeToMax * $timeToMax + $max * ($seconds - $timeToMax);
        $expectedMeters = $pixels / 10;
        $ratio = $expectedMeters > 0 ? $distance / $expectedMeters : 99;
        if ($duration < 1000 || $duration > config('daily_journey.maximum_run_minutes') * 60000) {
            $flags[] = 'impossible_duration';
        } if ($ratio > 1.12 || $ratio < 0.70) {
            $flags[] = 'impossible_distance';
        } $maxCollectibles = (int) ceil($seconds / 2.5) + 3;
        if ($data['collectible_count'] > $maxCollectibles) {
            $flags[] = 'impossible_collectibles';
        } if ($data['powerup_pickup_count'] > (int) ceil($seconds / 20) + 1 || $data['powerup_use_count'] > $data['powerup_pickup_count']) {
            $flags[] = 'impossible_powerups';
        } $expected = (int) floor($distance) + (int) $data['collectible_count'] * (int) config('daily_journey.collectible_bonus');
        if ((int) $data['score'] !== $expected) {
            $flags[] = 'score_mismatch';
        } $expectedTier = min(5, 1 + (int) floor($seconds / 30));
        if ($data['maximum_speed_tier'] > $expectedTier + 1) {
            $flags[] = 'impossible_speed_tier';
        } $status = $flags === [] ? 'accepted' : 'rejected';
        if ($flags !== [] && ! array_intersect($flags, ['score_mismatch', 'impossible_duration', 'impossible_distance', 'impossible_collectibles', 'impossible_powerups'])) {
            $status = 'suspicious';
        }

        return [$status, $flags, $expected];
    }
}
