<?php

namespace App\Services\CreatorIntelligence\Videos;

use App\Models\CreatorVideo;
use App\Models\VideoPerformanceSnapshot;
use Illuminate\Database\Eloquent\Builder;

class LatestSnapshotResolver
{
    public const SOURCE_PRIORITY = ['combined', 'youtube_studio', 'vidiq', 'manual'];

    public const FIELDS = ['snapshot_date', 'source', 'views', 'impressions', 'impressions_ctr', 'watch_time_minutes', 'average_view_duration_seconds', 'average_percentage_viewed', 'likes', 'comments', 'shares', 'subscribers_gained', 'subscribers_lost', 'estimated_revenue', 'rpm', 'cpm', 'hype_points', 'views_first_24_hours', 'views_first_7_days', 'views_first_28_days'];

    public function subquery(string $column): Builder
    {
        return VideoPerformanceSnapshot::query()->select($column)
            ->whereColumn('creator_video_id', 'creator_videos.id')
            ->orderByDesc('snapshot_date')
            ->orderByRaw("CASE source WHEN 'combined' THEN 1 WHEN 'youtube_studio' THEN 2 WHEN 'vidiq' THEN 3 WHEN 'manual' THEN 4 ELSE 5 END")
            ->orderByDesc('id')->limit(1);
    }

    public function resolve(CreatorVideo $video): ?VideoPerformanceSnapshot
    {
        return $video->performanceSnapshots()->orderByDesc('snapshot_date')
            ->orderByRaw("CASE source WHEN 'combined' THEN 1 WHEN 'youtube_studio' THEN 2 WHEN 'vidiq' THEN 3 WHEN 'manual' THEN 4 ELSE 5 END")
            ->orderByDesc('id')->first();
    }
}
