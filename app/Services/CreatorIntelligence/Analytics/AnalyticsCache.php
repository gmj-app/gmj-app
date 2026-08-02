<?php

namespace App\Services\CreatorIntelligence\Analytics;

use Closure;
use Illuminate\Support\Facades\Cache;

class AnalyticsCache
{
    private const VERSION_KEY = 'creator-intelligence:analytics:version';

    private const PAYLOAD_VERSION = 3;

    public function remember(string $report, AnalyticsContext $context, Closure $callback): array
    {
        $version = Cache::get(self::VERSION_KEY, 1);
        $key = 'creator-intelligence:analytics:'.self::PAYLOAD_VERSION.':'.$version.':'.$report.':'.sha1(json_encode($context->filters));

        return Cache::remember($key, now()->addMinutes(10), $callback);
    }

    public function invalidate(): void
    {
        Cache::forever(self::VERSION_KEY, ((int) Cache::get(self::VERSION_KEY, 1)) + 1);
    }
}
