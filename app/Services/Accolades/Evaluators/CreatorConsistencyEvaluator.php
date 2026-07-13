<?php

namespace App\Services\Accolades\Evaluators;

use App\Models\Recommendation;
use App\Services\Accolades\Contracts\TrackEvaluator;
use App\Services\Accolades\TrackMetric;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

class CreatorConsistencyEvaluator implements TrackEvaluator
{
    public function evaluate(int $subjectId): TrackMetric
    {
        $expression = DB::getDriverName() === 'sqlite'
            ? "strftime('%Y-%m', published_at)"
            : "DATE_FORMAT(CONVERT_TZ(published_at, '+00:00', '".$this->timezoneOffset()."'), '%Y-%m')";

        $value = DB::table('recommendations')->where('creator_id', $subjectId)
            ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)->whereNotNull('submitted_by')
            ->where('status', 'published')->whereNotNull('published_at')->whereNull('deleted_at')
            ->where(fn (Builder $query) => $query->whereNull('moderation_status')->orWhere('moderation_status', '!=', 'removed'))
            ->distinct()->count(DB::raw($expression));

        return new TrackMetric($value, ['metric' => 'distinct_publication_calendar_months', 'timezone' => config('app.timezone')], now());
    }

    private function timezoneOffset(): string
    {
        return now(config('app.timezone'))->format('P');
    }
}
