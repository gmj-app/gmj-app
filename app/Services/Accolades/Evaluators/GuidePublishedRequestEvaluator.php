<?php

namespace App\Services\Accolades\Evaluators;

use App\Models\Recommendation;
use App\Services\Accolades\Contracts\TrackEvaluator;
use App\Services\Accolades\Evaluators\Concerns\FiltersEligibleRequests;
use App\Services\Accolades\TrackMetric;
use Illuminate\Support\Facades\DB;

class GuidePublishedRequestEvaluator implements TrackEvaluator
{
    use FiltersEligibleRequests;

    public function evaluate(int $subjectId): TrackMetric
    {
        $query = DB::table('recommendations')->where('submitted_by', $subjectId)
            ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN);

        return new TrackMetric($this->eligible($query, 'published')->count(), ['metric' => 'published_guide_requests'], now());
    }
}
