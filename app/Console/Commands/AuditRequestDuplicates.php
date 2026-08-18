<?php

namespace App\Console\Commands;

use App\Models\Recommendation;
use App\Models\RequestDuplicateCase;
use App\Models\UserPick;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class AuditRequestDuplicates extends Command
{
    protected $signature = 'requests:audit-duplicates';

    protected $description = 'Read-only integrity audit for duplicate Request cases and merges';

    public function handle(): int
    {
        $metrics = [
            'Pending cases' => RequestDuplicateCase::where('status', 'pending')->count(),
            'Cases with missing Requests' => RequestDuplicateCase::whereDoesntHave('requestLow')->orWhereDoesntHave('requestHigh')->count(),
            'Merged Requests without survivor' => Recommendation::where('status', 'merged_duplicate')->whereNull('merged_into_request_id')->count(),
            'Cross-Creator merge links' => DB::table('recommendations as losers')->join('recommendations as survivors', 'survivors.id', '=', 'losers.merged_into_request_id')->whereColumn('losers.creator_id', '!=', 'survivors.creator_id')->count(),
            'Merged Requests with active support' => UserPick::activeSupport()->whereHas('recommendation', fn ($q) => $q->where('status', 'merged_duplicate'))->count(),
            'Merged Requests missing merge timestamp' => Recommendation::where('status', 'merged_duplicate')->whereNull('merged_at')->count(),
        ];
        $this->table(['Check', 'Count'], collect($metrics)->map(fn ($count, $label) => [$label, $count])->values()->all());

        return collect($metrics)->except('Pending cases')->sum() === 0 ? self::SUCCESS : self::FAILURE;
    }
}
