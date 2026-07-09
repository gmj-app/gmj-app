<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\GuideNumberService;
use Illuminate\Console\Command;

class AssignGuideNumbers extends Command
{
    protected $signature = 'guides:assign-numbers';

    protected $description = 'Assign stable guide numbers to users missing them.';

    public function handle(GuideNumberService $guideNumbers): int
    {
        $assigned = $guideNumbers->backfillMissingGuideNumbers(function (User $user): void {
            $this->line("Assigned Guide #{$user->guide_number} to user ID {$user->id}");
        });

        $this->info("Assigned guide numbers to {$assigned} users.");

        return self::SUCCESS;
    }
}
