<?php

namespace App\Services;

use App\Models\Creator;
use App\Models\User;

class HomepageStatsService
{
    /**
     * @return array{creatorCount: int, guideCount: int}
     */
    public function counts(): array
    {
        return [
            'creatorCount' => Creator::query()
                ->active()
                ->count(),
            'guideCount' => User::query()
                ->count(),
        ];
    }
}
