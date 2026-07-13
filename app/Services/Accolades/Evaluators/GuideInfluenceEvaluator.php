<?php

namespace App\Services\Accolades\Evaluators;

use App\Models\Recommendation;
use App\Services\Accolades\Contracts\TrackEvaluator;
use App\Services\Accolades\TrackMetric;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class GuideInfluenceEvaluator implements TrackEvaluator
{
    public function evaluate(int $subjectId): TrackMetric
    {
        $submitted = DB::table('recommendations')->select('id')->where('submitted_by', $subjectId)
            ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN);
        $supported = DB::table('user_picks')->select('recommendation_id as id')->where('user_id', $subjectId)
            ->where('vote_count', '>', 0)
            ->where(fn (Builder $query) => $query->whereNull('release_reason')->orWhere('release_reason', '!=', 'request_removed'));

        $ids = DB::query()->fromSub($submitted->union($supported), 'influenced')->select('id')->distinct();
        $recordIds = DB::table('recommendations')->whereIn('id', $ids)->where('status', 'published')
            ->whereNull('deleted_at')->where(fn (Builder $query) => $query->whereNull('moderation_status')->orWhere('moderation_status', '!=', 'removed'))
            ->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();

        return new TrackMetric(count($recordIds), ['metric' => 'distinct_published_requests_influenced'], now(), $recordIds);
    }
}
