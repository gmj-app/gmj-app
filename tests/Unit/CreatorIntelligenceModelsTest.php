<?php

namespace Tests\Unit;

use App\Enums\PerformanceSnapshotSource;
use App\Enums\VideoContentType;
use App\Enums\VideoCopyrightStatus;
use App\Enums\VideoFormat;
use App\Models\CreatorChannel;
use App\Models\CreatorVideo;
use App\Models\VideoPerformanceSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorIntelligenceModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_domain_enums_expose_the_required_values(): void
    {
        $this->assertSame(['long', 'short', 'live', 'unknown'], array_column(VideoFormat::cases(), 'value'));
        $this->assertSame(['reaction', 'review', 'interview', 'documentary', 'livestream', 'vlog', 'educational', 'other'], array_column(VideoContentType::cases(), 'value'));
        $this->assertSame(['clear', 'claimed', 'blocked', 'demonetized', 'unknown'], array_column(VideoCopyrightStatus::cases(), 'value'));
        $this->assertSame(['youtube_studio', 'vidiq', 'manual', 'combined'], array_column(PerformanceSnapshotSource::cases(), 'value'));
    }

    public function test_model_casts_and_active_scope_are_applied(): void
    {
        $active = CreatorChannel::factory()->create(['is_active' => true]);
        CreatorChannel::factory()->create(['is_active' => false]);
        $video = CreatorVideo::factory()->for($active, 'channel')->create(['video_format' => 'short', 'content_type' => 'review', 'copyright_status' => 'clear']);
        $snapshot = VideoPerformanceSnapshot::factory()->for($video, 'video')->create(['source' => 'manual', 'impressions_ctr' => 5.42]);

        $this->assertTrue($active->is_active);
        $this->assertSame([$active->id], CreatorChannel::active()->pluck('id')->all());
        $this->assertSame(VideoFormat::Short, $video->video_format);
        $this->assertSame(VideoContentType::Review, $video->content_type);
        $this->assertSame(VideoCopyrightStatus::Clear, $video->copyright_status);
        $this->assertSame(PerformanceSnapshotSource::Manual, $snapshot->source);
        $this->assertSame('5.4200', $snapshot->impressions_ctr);
    }
}
