<?php

namespace App\Services;

use App\Models\Creator;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

class PlatformStatisticsService
{
    public const CACHE_KEY = 'platform:public-counts';

    /** @return array{creatorCount:int,guideCount:int} */
    public function publicCounts(): array
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(3), fn (): array => [
            'creatorCount' => Creator::query()->active()->count(),
            'guideCount' => User::query()->count(),
        ]);
    }

    public function forget(): void
    {
        Cache::forget(self::CACHE_KEY);
    }
}
