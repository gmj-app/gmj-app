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

        $query = DB::table('recommendations')->where('creator_id', $subjectId)
            ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)->whereNotNull('submitted_by')
            ->where('status', 'published')->whereNotNull('published_at')->whereNull('deleted_at')
            ->where(fn (Builder $query) => $query->whereNull('moderation_status')->orWhere('moderation_status', '!=', 'removed'));
        $months = (clone $query)->selectRaw("{$expression} as publication_month")->distinct()->orderBy('publication_month')->pluck('publication_month')->all();
        $ids = $query->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();

        return new TrackMetric(count($months), ['metric' => 'distinct_publication_calendar_months', 'timezone' => config('app.timezone'), 'qualifying_months' => $months], now(), $ids);
    }

    private function timezoneOffset(): string
    {
        return now(config('app.timezone'))->format('P');
    }
}
