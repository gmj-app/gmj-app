<?php

namespace App\Services\Accolades\Evaluators;

use App\Models\GameDailyChampion;
use App\Services\Accolades\Contracts\TrackEvaluator;
use App\Services\Accolades\TrackMetric;

class GuideDailyChallengeWinsEvaluator implements TrackEvaluator
{
    public function evaluate(int $subjectId): TrackMetric
    {
        $ids = GameDailyChampion::query()->where('user_id', $subjectId)->pluck('id');

        return new TrackMetric($ids->count(), ['source' => 'finalized_daily_champions'], now(), $ids->all());
    }
}
