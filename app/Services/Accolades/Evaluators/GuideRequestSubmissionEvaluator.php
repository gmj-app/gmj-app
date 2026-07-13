<?php

namespace App\Services\Accolades\Evaluators;

use App\Models\Recommendation;
use App\Services\Accolades\Contracts\TrackEvaluator;
use App\Services\Accolades\Evaluators\Concerns\FiltersEligibleRequests;
use App\Services\Accolades\TrackMetric;
use Illuminate\Support\Facades\DB;

class GuideRequestSubmissionEvaluator implements TrackEvaluator
{
    use FiltersEligibleRequests;

    public function evaluate(int $subjectId): TrackMetric
    {
        $query = DB::table('recommendations')->where('submitted_by', $subjectId)
            ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)
            ->whereNotIn('status', ['withdrawn', 'hidden']);

        $ids = $this->eligible($query)->pluck('recommendations.id')->map(fn ($id) => (int) $id)->all();

        return new TrackMetric(count($ids), ['metric' => 'valid_guide_requests_submitted'], now(), $ids);
    }
}
