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
        $ids = $this->reach->guideIdsFor($subjectId);

        return new TrackMetric(count($ids), ['metric' => 'unique_valid_guides_reached', 'record_type' => 'user'], now(), $ids);
    }
}
