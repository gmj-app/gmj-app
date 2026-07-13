<?php

namespace App\Services\Accolades\Evaluators;

use App\Services\Accolades\Contracts\TrackEvaluator;
use App\Services\Accolades\CreatorReachQueryService;
use App\Services\Accolades\TrackMetric;

class CreatorCommunityReachEvaluator implements TrackEvaluator
{
    public function __construct(private readonly CreatorReachQueryService $reach) {}

    public function evaluate(int $subjectId): TrackMetric
    {
        return new TrackMetric($this->reach->countFor($subjectId), ['metric' => 'unique_valid_guides_reached'], now());
    }
}
