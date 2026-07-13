<?php

namespace App\Services\Accolades\Evaluators\Concerns;

use Illuminate\Database\Query\Builder;

trait FiltersEligibleRequests
{
    private function eligible(Builder $query, ?string $status = null): Builder
    {
        return $query
            ->whereNull('recommendations.deleted_at')
            ->where(function (Builder $query): void {
                $query->whereNull('recommendations.moderation_status')
                    ->orWhere('recommendations.moderation_status', '!=', 'removed');
            })
            ->when($status, fn (Builder $query) => $query->where('recommendations.status', $status));
    }
}
