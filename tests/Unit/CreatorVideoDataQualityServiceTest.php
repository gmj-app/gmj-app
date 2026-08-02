<?php

namespace Tests\Unit;

use App\Models\CreatorVideo;
use App\Models\VideoPerformanceSnapshot;
use App\Services\CreatorIntelligence\Videos\CreatorVideoDataQualityService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorVideoDataQualityServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_no_snapshot_is_incomplete_and_fingerprint_identity_is_a_warning(): void
    {
        $video = CreatorVideo::factory()->create(['platform_video_id' => 'fingerprint:abc', 'thumbnail_url' => null]);
        $result = app(CreatorVideoDataQualityService::class)->evaluate($video);
        $this->assertSame('Incomplete', $result['status']);
        $this->assertContains(['severity' => 'critical', 'label' => 'No performance snapshots'], $result['findings']);
        $this->assertContains(['severity' => 'warning', 'label' => 'Missing platform video ID'], $result['findings']);
        $this->assertContains(['severity' => 'information', 'label' => 'Missing thumbnail URL'], $result['findings']);
    }

    public function test_explicit_zero_metrics_are_not_missing_and_information_does_not_prevent_complete(): void
    {
        $video = CreatorVideo::factory()->create(['published_at' => now(), 'is_monetized' => true]);
        $snapshot = VideoPerformanceSnapshot::factory()->for($video, 'video')->create(['snapshot_date' => now(), 'views' => 0, 'impressions' => 0, 'impressions_ctr' => 0, 'watch_time_minutes' => 0, 'average_view_duration_seconds' => 0, 'average_percentage_viewed' => 0, 'subscribers_gained' => 0, 'subscribers_lost' => 0, 'estimated_revenue' => 0, 'hype_points' => 0]);
        $result = app(CreatorVideoDataQualityService::class)->evaluate($video, $snapshot);
        $this->assertSame('Complete', $result['status']);
        $this->assertFalse(collect($result['findings'])->contains('label', 'Latest snapshot missing views'));
        $this->assertFalse(collect($result['findings'])->contains('label', 'Latest snapshot missing Hype Points'));
    }
}
