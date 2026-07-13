<?php

namespace App\Services\Accolades\Evaluators;

use App\Services\Accolades\Contracts\TrackEvaluator;
use App\Services\Accolades\TrackMetric;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class GuideSupportedPublicationEvaluator implements TrackEvaluator
{
    public function evaluate(int $subjectId): TrackMetric
    {
        $value = DB::table('user_picks')
            ->join('recommendations', 'recommendations.id', '=', 'user_picks.recommendation_id')
            ->where('user_picks.user_id', $subjectId)
            ->where('user_picks.vote_count', '>', 0)
            ->where('recommendations.status', 'published')
            ->whereNull('recommendations.deleted_at')
            ->where(fn (Builder $query) => $query->whereNull('recommendations.moderation_status')->orWhere('recommendations.moderation_status', '!=', 'removed'))
            ->where(fn (Builder $query) => $query->whereNull('user_picks.release_reason')->orWhere('user_picks.release_reason', '!=', 'request_removed'))
            ->where(fn (Builder $query) => $query->whereNull('recommendations.submitted_by')->orWhere('recommendations.submitted_by', '!=', $subjectId))
            ->distinct()
            ->count('recommendations.id');

        return new TrackMetric($value, ['metric' => 'distinct_supported_publications', 'vote_quantity_ignored' => true], now());
    }
}
