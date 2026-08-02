<?php

namespace Tests\Feature;

use App\Models\CreatorChannel;
use App\Models\CreatorVideo;
use App\Models\VideoPerformanceSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorIntelligenceThumbnailTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_populates_blanks_preserves_custom_urls_and_skips_invalid_or_non_youtube_videos(): void
    {
        $youtube = CreatorChannel::factory()->create(['platform' => 'youtube']);
        $other = CreatorChannel::factory()->create(['platform' => 'vimeo']);
        $blank = CreatorVideo::factory()->for($youtube, 'channel')->create(['platform_video_id' => 'NiDToOrsUeI', 'thumbnail_url' => null]);
        $custom = CreatorVideo::factory()->for($youtube, 'channel')->create(['platform_video_id' => 'CuStomID123', 'thumbnail_url' => 'https://example.com/custom.jpg']);
        $invalid = CreatorVideo::factory()->for($youtube, 'channel')->create(['platform_video_id' => 'invalid', 'thumbnail_url' => null]);
        $skipped = CreatorVideo::factory()->for($other, 'channel')->create(['platform_video_id' => 'OtherVid123', 'thumbnail_url' => null]);
        $snapshot = VideoPerformanceSnapshot::factory()->for($blank, 'video')->create(['views' => 321]);

        $this->artisan('creator-intelligence:populate-youtube-thumbnails', ['--channel' => $youtube->id, '--dry-run' => true])->assertSuccessful()->expectsOutput('Populated: 1')->expectsOutput('Preserved: 1')->expectsOutput('Invalid IDs: 1');
        $this->assertNull($blank->fresh()->thumbnail_url);

        $this->artisan('creator-intelligence:populate-youtube-thumbnails')->assertSuccessful()->expectsOutput('Populated: 1')->expectsOutput('Skipped: 1');
        $this->assertSame('https://i.ytimg.com/vi/NiDToOrsUeI/hqdefault.jpg', $blank->fresh()->thumbnail_url);
        $this->assertSame('https://example.com/custom.jpg', $custom->fresh()->thumbnail_url);
        $this->assertNull($invalid->fresh()->thumbnail_url);
        $this->assertNull($skipped->fresh()->thumbnail_url);
        $this->assertSame('NiDToOrsUeI', $blank->platform_video_id);
        $this->assertSame(321, $snapshot->fresh()->views);

        $this->artisan('creator-intelligence:populate-youtube-thumbnails', ['--channel' => $youtube->id])->assertSuccessful()->expectsOutput('Populated: 0')->expectsOutput('Preserved: 2');
    }

    public function test_force_replaces_youtube_variants_but_preserves_explicit_custom_urls(): void
    {
        $channel = CreatorChannel::factory()->create(['platform' => 'youtube']);
        $generated = CreatorVideo::factory()->for($channel, 'channel')->create(['platform_video_id' => 'NiDToOrsUeI', 'thumbnail_url' => 'https://i.ytimg.com/vi/NiDToOrsUeI/mqdefault.jpg']);
        $custom = CreatorVideo::factory()->for($channel, 'channel')->create(['platform_video_id' => 'CuStomID123', 'thumbnail_url' => 'https://cdn.example.com/artwork.jpg']);

        $this->artisan('creator-intelligence:populate-youtube-thumbnails', ['--force' => true])->assertSuccessful()->expectsOutput('Populated: 1')->expectsOutput('Preserved: 1');
        $this->assertSame('https://i.ytimg.com/vi/NiDToOrsUeI/hqdefault.jpg', $generated->fresh()->thumbnail_url);
        $this->assertSame('https://cdn.example.com/artwork.jpg', $custom->fresh()->thumbnail_url);
    }

    public function test_reusable_client_component_has_ordered_variants_and_neutral_terminal_fallback(): void
    {
        $source = file_get_contents(resource_path('js/creator-youtube-thumbnail.js'));
        $this->assertLessThan(strpos($source, 'sddefault.jpg'), strpos($source, 'maxresdefault.jpg'));
        $this->assertLessThan(strpos($source, 'hqdefault.jpg'), strpos($source, 'sddefault.jpg'));
        $this->assertLessThan(strpos($source, 'mqdefault.jpg'), strpos($source, 'hqdefault.jpg'));
        $this->assertStringContainsString("image.addEventListener('error', attempt", $source);
        $this->assertStringContainsString('image.naturalWidth <= 120', $source);
        $this->assertStringContainsString("fallback.textContent = 'No image'", $source);
    }
}
