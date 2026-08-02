<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Models\CreatorVideo;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CreatorVideoSnapshotExportController extends Controller
{
    public function __invoke(CreatorVideo $creatorVideo): StreamedResponse
    {
        return response()->streamDownload(function () use ($creatorVideo): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['Platform Video ID', 'Video Title', 'Snapshot Date', 'Source', 'Views', 'Impressions', 'CTR', 'Watch Time Minutes', 'Average View Duration Seconds', 'Average Percentage Viewed', 'Likes', 'Comments', 'Shares', 'Subscribers Gained', 'Subscribers Lost', 'Estimated Revenue', 'RPM', 'CPM', 'Hype Points', 'Views First 24 Hours', 'Views First 7 Days', 'Views First 28 Days', 'Created At', 'Updated At']);
            foreach ($creatorVideo->performanceSnapshots()->orderByDesc('snapshot_date')->cursor() as $snapshot) {
                fputcsv($out, [$this->safe($creatorVideo->platform_video_id), $this->safe($creatorVideo->title), $snapshot->snapshot_date->toDateString(), $snapshot->source->value, $snapshot->views, $snapshot->impressions, $snapshot->impressions_ctr, $snapshot->watch_time_minutes, $snapshot->average_view_duration_seconds, $snapshot->average_percentage_viewed, $snapshot->likes, $snapshot->comments, $snapshot->shares, $snapshot->subscribers_gained, $snapshot->subscribers_lost, $snapshot->estimated_revenue, $snapshot->rpm, $snapshot->cpm, $snapshot->hype_points, $snapshot->views_first_24_hours, $snapshot->views_first_7_days, $snapshot->views_first_28_days, $snapshot->created_at?->toIso8601String(), $snapshot->updated_at?->toIso8601String()]);
            }
            fclose($out);
        }, 'video-'.$creatorVideo->platform_video_id.'-snapshots.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function safe(?string $value): ?string
    {
        return is_string($value) && preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
