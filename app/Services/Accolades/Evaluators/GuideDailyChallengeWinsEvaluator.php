<?php

namespace App\Services\Accolades\Evaluators;

use App\Models\GameDailyChampion;
use App\Models\User;
use App\Services\Accolades\Contracts\TrackEvaluator;
use App\Services\Accolades\TrackMetric;
use App\Services\DailyJourney\AccessService;

class GuideDailyChallengeWinsEvaluator implements TrackEvaluator
{
    public function __construct(private readonly AccessService $access) {}

    public function evaluate(int $subjectId): TrackMetric
    {
        if ($this->access->isPrivate() && ! $this->access->allows(User::find($subjectId))) {
            return new TrackMetric(0, ['source' => 'private_daily_journey_access_denied'], now(), []);
        }

        $ids = GameDailyChampion::query()->where('user_id', $subjectId)->pluck('id');

        return new TrackMetric($ids->count(), ['source' => 'finalized_daily_champions'], now(), $ids->all());
    }
}
