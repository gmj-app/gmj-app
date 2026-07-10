<?php

namespace Tests\Feature;

use App\Models\Recommendation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class BackfillYouTubePlaylistsTest extends TestCase
{
    use RefreshDatabase;

    public function test_dry_run_detects_playlist_without_changing_it(): void
    {
        $recommendation = Recommendation::factory()->create([
            'media_type' => null,
            'youtube_url' => 'https://www.youtube.com/playlist?list=PL1234567890&si=old',
            'normalized_url' => null,
            'youtube_video_id' => null,
        ]);

        $this->artisan('recommendations:backfill-youtube-playlists', ['--dry-run' => true])
            ->expectsOutputToContain("Would update recommendation {$recommendation->id}")
            ->assertSuccessful();

        $this->assertNull($recommendation->fresh()->media_type);
    }

    public function test_command_classifies_pure_playlist_and_is_idempotent(): void
    {
        $recommendation = Recommendation::factory()->create([
            'media_type' => null,
            'youtube_url' => 'https://m.youtube.com/playlist?list=PL1234567890',
            'normalized_url' => null,
            'youtube_video_id' => null,
            'status' => 'approved',
        ]);

        $this->artisan('recommendations:backfill-youtube-playlists')->assertSuccessful();

        $recommendation->refresh();
        $this->assertSame('playlist', $recommendation->media_type);
        $this->assertSame('PL1234567890', $recommendation->youtube_playlist_id);
        $this->assertSame('https://www.youtube.com/playlist?list=PL1234567890', $recommendation->normalized_url);
        $this->assertSame('approved', $recommendation->status);

        $this->artisan('recommendations:backfill-youtube-playlists')
            ->expectsOutputToContain('Playlist records found: 0; updated: 0')
            ->assertSuccessful();
    }

    public function test_command_does_not_reclassify_mixed_watch_urls(): void
    {
        $recommendation = Recommendation::factory()->create([
            'media_type' => null,
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PL1234567890',
            'normalized_url' => null,
        ]);

        $this->artisan('recommendations:backfill-youtube-playlists')->assertSuccessful();

        $this->assertNull($recommendation->fresh()->media_type);
    }
}
