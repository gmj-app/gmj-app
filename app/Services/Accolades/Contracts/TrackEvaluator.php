<?php

namespace App\Services\Accolades\Contracts;

use App\Services\Accolades\TrackMetric;

interface TrackEvaluator
{
    public function evaluate(int $subjectId): TrackMetric;
}
