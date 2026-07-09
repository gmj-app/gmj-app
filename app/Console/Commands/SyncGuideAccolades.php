<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GuideAccoladeService;
use App\Services\GuideNumberService;
use Illuminate\Console\Command;

class SyncGuideAccolades extends Command
{
    protected $signature = 'guides:sync-accolades';

    protected $description = 'Assign missing guide numbers and sync automatic guide accolades.';

    public function handle(GuideNumberService $guideNumbers, GuideAccoladeService $guideAccolades): int
    {
        $guideAccolades->ensureInitialAccolades();

        $backfilled = $guideNumbers->backfillMissingGuideNumbers();
        $synced = 0;

        User::query()
            ->whereNotNull('guide_number')
            ->orderBy('guide_number')
            ->each(function (User $user) use ($guideAccolades, &$synced): void {
                $guideAccolades->syncEarlyGuideAccolades($user);
                $synced++;
            });

        $this->info("Backfilled {$backfilled} missing guide numbers.");
        $this->info("Synced {$synced} guide accolade assignments.");

        return self::SUCCESS;
    }
}
