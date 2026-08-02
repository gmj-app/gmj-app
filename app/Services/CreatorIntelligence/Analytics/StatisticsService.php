<?php

namespace App\Services\CreatorIntelligence\Analytics;

use Illuminate\Support\Collection;

class StatisticsService
{
    public function summarize(iterable $values, int $totalCount): array
    {
        $eligible = collect($values)->filter(fn ($value) => $value !== null)->map(fn ($value) => (float) $value)->sort()->values();
        $count = $eligible->count();
        $mean = $count ? $eligible->avg() : null;
        $standardDeviation = $count ? sqrt($eligible->sum(fn ($value) => ($value - $mean) ** 2) / $count) : null;
        $cv = $count >= 3 && $mean > 0 ? $standardDeviation / $mean : null;

        return ['value' => $mean, 'sum' => $count ? $eligible->sum() : null, 'mean' => $mean, 'median' => $this->median($eligible), 'minimum' => $count ? $eligible->first() : null, 'maximum' => $count ? $eligible->last() : null, 'standard_deviation' => $standardDeviation, 'coefficient_of_variation' => $cv, 'consistency_score' => $cv === null ? null : max(0, min(100, 100 - ($cv * 100))), 'eligible_video_count' => $count, 'missing_value_count' => $totalCount - $count, 'total_group_video_count' => $totalCount];
    }

    public function median(Collection $values): ?float
    {
        $values = $values->filter(fn ($value) => $value !== null)->map(fn ($value) => (float) $value)->sort()->values();
        $count = $values->count();
        if ($count === 0) {
            return null;
        }
        $middle = intdiv($count, 2);

        return $count % 2 ? $values[$middle] : ($values[$middle - 1] + $values[$middle]) / 2;
    }

    public function difference(?float $value, ?float $baseline, bool $percentagePoints = false): array
    {
        $absolute = $value === null || $baseline === null ? null : $value - $baseline;

        return ['absolute' => $absolute, 'percentage_points' => $percentagePoints ? $absolute : null, 'relative' => $absolute === null || $baseline == 0 ? null : ($absolute / abs($baseline)) * 100];
    }

    public function sampleStrength(int $count, int $minimum): string
    {
        if ($count < $minimum) {
            return 'Insufficient';
        }
        if ($count <= 4) {
            return 'Limited';
        }
        if ($count <= 9) {
            return 'Moderate';
        }

        return 'Stronger';
    }
}
