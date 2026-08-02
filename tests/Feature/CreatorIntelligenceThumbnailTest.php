<?php

namespace Tests\Feature;

use App\Models\CreatorChannel;
use App\Models\CreatorVideo;
use App\Models\User;
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
        $this->assertStringContainsString('[customUrl, ...variants].filter(Boolean)', $source);
        $this->assertStringContainsString("'[data-creator-intelligence-thumbnail]'", $source);
        $this->assertStringNotContainsString("querySelectorAll('table tbody tr')", $source);
    }

    public function test_metadata_queue_renders_the_shared_thumbnail_component_with_stored_and_derived_sources(): void
    {
        $channel = CreatorChannel::factory()->create();
        CreatorVideo::factory()->for($channel, 'channel')->create([
            'platform_video_id' => 'NiDToOrsUeI',
            'thumbnail_url' => 'https://cdn.example.com/preferred.jpg',
            'title' => 'Shared thumbnail test',
        ]);

        $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.metadata-queue.index'))
            ->assertOk()
            ->assertSee('data-creator-intelligence-thumbnail', false)
            ->assertSee('data-video-id="NiDToOrsUeI"', false)
            ->assertSee('data-thumbnail-url="https://cdn.example.com/preferred.jpg"', false)
            ->assertSee('data-title="Shared thumbnail test"', false);
    }

    public function test_all_creator_intelligence_video_surfaces_invoke_the_canonical_component(): void
    {
        foreach ([
            'videos/index.blade.php',
            'metadata-queue/index.blade.php',
            'videos/show.blade.php',
            'metadata-suggestions/index.blade.php',
            'subjects/show.blade.php',
            'content-items/show.blade.php',
        ] as $view) {
            $source = file_get_contents(resource_path('views/super-admin/creator-intelligence/'.$view));
            $this->assertStringContainsString('x-creator-intelligence-thumbnail', $source, $view);
            $this->assertStringNotContainsString('$video->thumbnail_url)<img', $source, $view);
        }
    }

    public function test_shared_component_keeps_neutral_placeholder_for_an_invalid_identity(): void
    {
        $video = CreatorVideo::factory()->make(['platform_video_id' => 'fingerprint:invalid', 'thumbnail_url' => null, 'title' => 'Unavailable thumbnail']);

        $this->blade('<x-creator-intelligence-thumbnail :video="$video" />', ['video' => $video])
            ->assertSee('data-creator-intelligence-thumbnail', false)
            ->assertSee('No image')
            ->assertDontSee('<img', false);
    }

    private function admin(): User
    {
        return User::factory()->create(['can_manage_creator_intelligence' => true]);
    }
}
