<?php

namespace App\Services\Accolades\Evaluators;

use App\Models\Recommendation;
use App\Services\Accolades\Contracts\TrackEvaluator;
use App\Services\Accolades\Evaluators\Concerns\FiltersEligibleRequests;
use App\Services\Accolades\TrackMetric;
use Illuminate\Support\Facades\DB;

class CreatorCommunityPublicationEvaluator implements TrackEvaluator
{
    use FiltersEligibleRequests;

    public function evaluate(int $subjectId): TrackMetric
    {
        $query = DB::table('recommendations')->where('creator_id', $subjectId)
            ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)->whereNotNull('submitted_by');

        $ids = $this->eligible($query, 'published')->pluck('recommendations.id')->map(fn ($id) => (int) $id)->all();

        return new TrackMetric(count($ids), ['metric' => 'published_community_requests'], now(), $ids);
    }
}
