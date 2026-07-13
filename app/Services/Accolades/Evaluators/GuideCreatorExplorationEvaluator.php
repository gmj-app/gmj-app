<?php

namespace App\Services\Accolades\Evaluators;

use App\Services\Accolades\Contracts\TrackEvaluator;
use App\Services\Accolades\TrackMetric;
use Illuminate\Support\Facades\DB;

class GuideCreatorExplorationEvaluator implements TrackEvaluator
{
    public function evaluate(int $subjectId): TrackMetric
    {
        $value = DB::table('creator_favorites')->join('creators', 'creators.id', '=', 'creator_favorites.creator_id')
            ->where('creator_favorites.user_id', $subjectId)->whereNull('creator_favorites.released_at')
            ->whereNull('creators.deleted_at')->where('creators.status', 'active')->whereNull('creators.deactivated_at')
            ->distinct()->count('creators.id');

        return new TrackMetric($value, ['metric' => 'simultaneous_available_favorites'], now());
    }
}
