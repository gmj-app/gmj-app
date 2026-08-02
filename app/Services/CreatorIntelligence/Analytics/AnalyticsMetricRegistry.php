<?php

namespace App\Services\CreatorIntelligence\Analytics;

class AnalyticsMetricRegistry
{
    public const LABELS = ['views' => 'Views', 'impressions' => 'Impressions', 'impressions_ctr' => 'CTR', 'watch_time_minutes' => 'Watch Time Minutes', 'average_view_duration_seconds' => 'Average View Duration', 'average_percentage_viewed' => 'Average Percentage Viewed', 'likes' => 'Likes', 'comments' => 'Comments', 'subscribers_gained' => 'Subscribers Gained', 'estimated_revenue' => 'Revenue', 'rpm' => 'RPM', 'hype_points' => 'Hype Points', 'metadata_completion_percentage' => 'Metadata Completion'];

    public function label(string $metric): string
    {
        return self::LABELS[$metric] ?? str($metric)->headline()->toString();
    }

    public function percentage(string $metric): bool
    {
        return in_array($metric, ['impressions_ctr', 'average_percentage_viewed'], true);
    }

    public function currency(string $metric): bool
    {
        return in_array($metric, ['estimated_revenue', 'rpm'], true);
    }
}
