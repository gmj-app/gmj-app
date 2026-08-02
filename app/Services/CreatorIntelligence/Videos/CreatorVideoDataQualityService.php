<?php

namespace App\Services\CreatorIntelligence\Videos;

use App\Models\CreatorVideo;
use App\Models\VideoPerformanceSnapshot;
use Carbon\Carbon;

class CreatorVideoDataQualityService
{
    public function evaluate(CreatorVideo $video, ?VideoPerformanceSnapshot $snapshot = null): array
    {
        $findings = [];
        $this->add($findings, blank($video->title), 'critical', 'Missing title');
        $this->add($findings, blank($video->platform_video_id) || str_starts_with((string) $video->platform_video_id, 'fingerprint:'), 'warning', 'Missing platform video ID');
        foreach (['description' => 'Missing description', 'video_url' => 'Missing video URL', 'thumbnail_url' => 'Missing thumbnail URL'] as $field => $label) {
            $this->add($findings, blank($video->{$field}), 'information', $label);
        }
        $this->add($findings, $video->published_at === null, 'warning', 'Missing published date');
        $this->add($findings, $video->duration_seconds === null, 'information', 'Missing duration');
        $this->add($findings, $video->video_format->value === 'unknown', 'information', 'Unknown video format');
        $this->add($findings, $video->content_type->value === 'other', 'information', 'Generic content type');
        $this->add($findings, $video->copyright_status->value === 'unknown', 'information', 'Unknown copyright status');
        $this->add($findings, $video->is_monetized === null, 'information', 'Unknown monetization status');
        if (! $snapshot) {
            $this->add($findings, true, 'critical', 'No performance snapshots');
        } else {
            $this->add($findings, Carbon::parse($snapshot->snapshot_date)->lt(now()->subDays(90)), 'warning', 'No recent performance snapshot');
            foreach (['views' => 'Latest snapshot missing views', 'impressions' => 'Latest snapshot missing impressions', 'impressions_ctr' => 'Latest snapshot missing CTR', 'watch_time_minutes' => 'Latest snapshot missing watch time', 'average_view_duration_seconds' => 'Latest snapshot missing average view duration', 'average_percentage_viewed' => 'Latest snapshot missing average percentage viewed', 'estimated_revenue' => 'Latest snapshot missing revenue', 'hype_points' => 'Latest snapshot missing Hype Points'] as $field => $label) {
                $this->add($findings, $snapshot->{$field} === null, 'information', $label);
            }
            $this->add($findings, $snapshot->subscribers_gained === null && $snapshot->subscribers_lost === null, 'information', 'Latest snapshot missing subscriber data');
        }
        $status = collect($findings)->contains('severity', 'critical') ? 'Incomplete' : (collect($findings)->contains('severity', 'warning') ? 'Needs Review' : 'Complete');

        return compact('status', 'findings');
    }

    private function add(array &$findings, bool $condition, string $severity, string $label): void
    {
        if ($condition) {
            $findings[] = compact('severity', 'label');
        }
    }
}
