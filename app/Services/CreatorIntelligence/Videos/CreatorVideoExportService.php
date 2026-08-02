<?php

namespace App\Services\CreatorIntelligence\Videos;

use App\Models\VideoPerformanceSnapshot;
use Illuminate\Database\Eloquent\Builder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CreatorVideoExportService
{
    public function __construct(private readonly MetricFormatter $format, private readonly CreatorVideoDataQualityService $quality) {}

    public function download(Builder $query): StreamedResponse
    {
        $query->leftJoin('creator_channels as export_channels', 'export_channels.id', '=', 'creator_videos.creator_channel_id')->leftJoin('creator_profiles as export_profiles', 'export_profiles.id', '=', 'export_channels.creator_profile_id')->addSelect(['export_channels.channel_name as export_channel_name', 'export_channels.platform as export_platform', 'export_channels.platform_channel_id as export_platform_channel_id', 'export_channels.default_publish_timezone as export_timezone', 'export_profiles.display_name as export_profile_name', 'export_profiles.default_currency as export_currency']);

        return response()->streamDownload(function () use ($query): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, $this->headers());
            foreach ($query->cursor() as $video) {
                $published = $video->published_at?->clone()->timezone($video->export_timezone);
                $snapshot = $this->snapshotFromAliases($video);
                $quality = $this->quality->evaluate($video, $snapshot)['status'];
                fputcsv($out, array_map($this->escape(...), [$video->export_profile_name, $video->export_channel_name, $video->export_platform, $video->export_platform_channel_id, $video->platform_video_id, $video->title, $video->description, $video->video_url, $video->thumbnail_url, $published?->toIso8601String(), $published?->toDateString(), $published?->format('H:i:s'), $published?->format('l'), $video->duration_seconds, $video->duration_seconds === null ? null : $this->format->duration($video->duration_seconds), $video->video_format->value, $video->content_type->value, $this->format->boolean($video->is_premiere), $this->format->boolean($video->is_live), $this->format->boolean($video->is_short), $this->format->boolean($video->is_documentary), $this->format->boolean($video->is_interview), $this->format->boolean($video->is_monetized), $video->copyright_status->value, $video->latest_snapshot_date, $video->latest_source, $video->latest_views, $video->latest_impressions, $video->latest_impressions_ctr, $video->latest_watch_time_minutes, $video->latest_average_view_duration_seconds, $video->latest_average_percentage_viewed, $video->latest_likes, $video->latest_comments, $video->latest_shares, $video->latest_subscribers_gained, $video->latest_subscribers_lost, $video->latest_estimated_revenue, $video->latest_rpm, $video->latest_cpm, $video->latest_hype_points, $video->latest_views_first_24_hours, $video->latest_views_first_7_days, $video->latest_views_first_28_days, $video->import_rows_count, $quality, $video->metadata_completion_percentage, $video->metadata_completion_status?->value ?? $video->metadata_completion_status, $video->created_at?->toIso8601String(), $video->updated_at?->toIso8601String()]));
            }
            fclose($out);
        }, 'creator-videos-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function headers(): array
    {
        return ['Creator Profile', 'Creator Channel', 'Platform', 'Platform Channel ID', 'Platform Video ID', 'Video Title', 'Description', 'Video URL', 'Thumbnail URL', 'Published At', 'Published Date', 'Published Time', 'Published Weekday', 'Duration Seconds', 'Duration Formatted', 'Video Format', 'Content Type', 'Is Premiere', 'Is Live', 'Is Short', 'Is Documentary', 'Is Interview', 'Monetization Status', 'Copyright Status', 'Latest Snapshot Date', 'Latest Snapshot Source', 'Views', 'Impressions', 'CTR', 'Watch Time Minutes', 'Average View Duration Seconds', 'Average Percentage Viewed', 'Likes', 'Comments', 'Shares', 'Subscribers Gained', 'Subscribers Lost', 'Estimated Revenue', 'RPM', 'CPM', 'Hype Points', 'Views First 24 Hours', 'Views First 7 Days', 'Views First 28 Days', 'Import Count', 'Data Quality Status', 'Metadata Completion Percentage', 'Metadata Completion Status', 'Created At', 'Updated At'];
    }

    private function escape(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }

    private function snapshotFromAliases($video): ?VideoPerformanceSnapshot
    {
        if ($video->latest_snapshot_date === null) {
            return null;
        }
        $attributes = ['snapshot_date' => $video->latest_snapshot_date, 'source' => $video->latest_source];
        foreach (LatestSnapshotResolver::FIELDS as $field) {
            $attributes[$field] = $video->{'latest_'.$field};
        }

        return new VideoPerformanceSnapshot($attributes);
    }
}
