<?php

namespace App\Services\CreatorIntelligence\Import;

use Illuminate\Support\Str;

class CsvColumnMapper
{
    public const CANONICAL_FIELDS = ['platform_video_id', 'title', 'description', 'published_at', 'duration', 'video_url', 'thumbnail_url', 'views', 'impressions', 'impressions_ctr', 'watch_time_minutes', 'watch_time_hours', 'average_view_duration_seconds', 'average_percentage_viewed', 'likes', 'comments', 'shares', 'subscribers_gained', 'subscribers_lost', 'estimated_revenue', 'rpm', 'cpm', 'hype_points', 'views_first_24_hours', 'views_first_7_days', 'views_first_28_days'];

    private const ALIASES = [
        'video' => 'platform_video_id', 'video title' => 'title', 'description' => 'description', 'video publish time' => 'published_at', 'publish time' => 'published_at', 'duration' => 'duration', 'video url' => 'video_url', 'thumbnail' => 'thumbnail_url', 'views' => 'views', 'impressions' => 'impressions', 'impressions click-through rate (%)' => 'impressions_ctr', 'impressions click-through rate' => 'impressions_ctr', 'watch time (hours)' => 'watch_time_hours', 'watch time (minutes)' => 'watch_time_minutes', 'average view duration' => 'average_view_duration_seconds', 'average percentage viewed (%)' => 'average_percentage_viewed', 'average percentage viewed' => 'average_percentage_viewed', 'likes' => 'likes', 'comments added' => 'comments', 'comments' => 'comments', 'shares' => 'shares', 'subscribers gained' => 'subscribers_gained', 'subscribers lost' => 'subscribers_lost', 'estimated revenue (usd)' => 'estimated_revenue', 'estimated revenue' => 'estimated_revenue', 'rpm (usd)' => 'rpm', 'rpm' => 'rpm', 'playback-based cpm (usd)' => 'cpm', 'cpm' => 'cpm', 'hype points' => 'hype_points', 'views first 24 hours' => 'views_first_24_hours', 'views first 7 days' => 'views_first_7_days', 'views first 28 days' => 'views_first_28_days',
    ];

    public function automatic(array $columns): array
    {
        $mapping = [];
        foreach ($columns as $column) {
            $withoutBom = preg_replace('/^\xEF\xBB\xBF/', '', (string) $column);
            $key = Str::lower(trim(preg_replace('/\s+/u', ' ', $withoutBom ?? (string) $column) ?? (string) $column));
            if (isset(self::ALIASES[$key]) && ! in_array(self::ALIASES[$key], $mapping, true)) {
                $mapping[$column] = self::ALIASES[$key];
            }
        }

        return $mapping;
    }

    public function isReady(array $mapping): bool
    {
        return in_array('title', $mapping, true);
    }
}
