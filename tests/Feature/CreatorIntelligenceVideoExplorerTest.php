<?php

namespace Tests\Feature;

use App\Models\CreatorChannel;
use App\Models\CreatorProfile;
use App\Models\CreatorVideo;
use App\Models\ImportBatch;
use App\Models\User;
use App\Models\VideoPerformanceSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorIntelligenceVideoExplorerTest extends TestCase
{
    use RefreshDatabase;

    public function test_video_routes_and_navigation_are_authorized(): void
    {
        $video = CreatorVideo::factory()->create();
        $this->get(route('superadmin.creator-intelligence.videos.index'))->assertRedirect(route('login'));
        $ordinary = User::factory()->create();
        foreach ([route('superadmin.creator-intelligence.videos.index'), route('superadmin.creator-intelligence.videos.show', $video), route('superadmin.creator-intelligence.videos.edit', $video), route('superadmin.creator-intelligence.videos.export'), route('superadmin.creator-intelligence.videos.snapshots.export', $video)] as $url) {
            $this->actingAs($ordinary)->get($url)->assertForbidden();
        }
        $this->actingAs($ordinary)->get(route('dashboard'))->assertDontSee('Creator Intelligence');
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('superadmin.creator-intelligence.videos.index'))->assertOk()->assertSee('Video Database');
    }

    public function test_latest_snapshot_uses_newest_date_then_deterministic_source_priority(): void
    {
        $video = CreatorVideo::factory()->create();
        VideoPerformanceSnapshot::factory()->for($video, 'video')->create(['snapshot_date' => '2026-07-31', 'source' => 'combined', 'views' => 900]);
        VideoPerformanceSnapshot::factory()->for($video, 'video')->create(['snapshot_date' => '2026-08-01', 'source' => 'manual', 'views' => 100]);
        VideoPerformanceSnapshot::factory()->for($video, 'video')->create(['snapshot_date' => '2026-08-01', 'source' => 'youtube_studio', 'views' => 200]);
        VideoPerformanceSnapshot::factory()->for($video, 'video')->create(['snapshot_date' => '2026-08-01', 'source' => 'combined', 'views' => 300]);

        $response = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.videos.index'));
        $response->assertOk()->assertSee('300')->assertSee('combined');
        $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.videos.show', $video))->assertOk()->assertSee('300')->assertSee('2026-08-01 · combined');
    }

    public function test_index_search_filters_sorting_and_pagination_use_latest_snapshot(): void
    {
        $profile = CreatorProfile::factory()->create(['display_name' => 'Search Profile']);
        $channel = CreatorChannel::factory()->for($profile, 'profile')->create(['channel_name' => 'Search Channel']);
        $older = CreatorVideo::factory()->for($channel, 'channel')->create(['title' => 'Alpha Formula', 'platform_video_id' => 'alpha-id', 'published_at' => '2026-01-01', 'video_format' => 'short', 'is_monetized' => null, 'thumbnail_url' => null]);
        $newer = CreatorVideo::factory()->for($channel, 'channel')->create(['title' => 'Beta', 'published_at' => '2026-02-01', 'video_format' => 'long', 'is_monetized' => false]);
        VideoPerformanceSnapshot::factory()->for($older, 'video')->create(['snapshot_date' => '2026-08-01', 'views' => 500, 'impressions_ctr' => 5.42, 'hype_points' => 40]);
        VideoPerformanceSnapshot::factory()->for($newer, 'video')->create(['snapshot_date' => '2026-08-01', 'views' => 100, 'impressions_ctr' => 2.00, 'hype_points' => null]);

        $admin = $this->admin();
        $this->actingAs($admin)->get(route('superadmin.creator-intelligence.videos.index', ['q' => 'Alpha']))->assertOk()->assertSee('Alpha Formula')->assertDontSee('Beta');
        $this->actingAs($admin)->get(route('superadmin.creator-intelligence.videos.index', ['q' => 'Search Channel']))->assertOk()->assertSee('Alpha Formula')->assertSee('Beta');
        $this->actingAs($admin)->get(route('superadmin.creator-intelligence.videos.index', ['video_format' => 'short', 'is_monetized' => 'unknown', 'has_thumbnail' => '0', 'min_views' => 400, 'min_hype_points' => 1]))->assertOk()->assertSee('Alpha Formula')->assertDontSee('Beta');
        $response = $this->actingAs($admin)->get(route('superadmin.creator-intelligence.videos.index', ['sort' => 'views', 'direction' => 'asc']));
        $response->assertOk();
        $this->assertLessThan(strpos($response->getContent(), 'Alpha Formula'), strpos($response->getContent(), 'Beta'));
    }

    public function test_detail_displays_missing_values_import_history_and_quality_without_storage_paths(): void
    {
        $video = CreatorVideo::factory()->create(['description' => null, 'thumbnail_url' => null]);
        $batch = ImportBatch::factory()->for($video->channel, 'channel')->create(['storage_path' => 'private/secret.csv']);
        $batch->rows()->create(['row_number' => 2, 'raw_data' => ['Video title' => $video->title], 'normalized_data' => ['title' => $video->title], 'status' => 'created', 'creator_video_id' => $video->id]);
        $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.videos.show', $video))->assertOk()->assertSee('No performance snapshots')->assertSee('Incomplete')->assertSee($batch->original_filename)->assertDontSee('private/secret.csv');
    }

    public function test_authorized_edit_updates_only_allowed_metadata_and_preserves_snapshots(): void
    {
        $video = CreatorVideo::factory()->create(['platform_video_id' => 'immutable-id']);
        $originalChannel = $video->creator_channel_id;
        $snapshot = VideoPerformanceSnapshot::factory()->for($video, 'video')->create(['views' => 123]);
        $payload = ['title' => 'Corrected title', 'description' => null, 'video_url' => 'https://youtube.com/watch?v=immutable-id', 'thumbnail_url' => null, 'published_at' => null, 'duration_seconds' => 0, 'video_format' => 'long', 'content_type' => 'review', 'is_premiere' => '0', 'is_live' => '0', 'is_short' => '0', 'is_documentary' => '0', 'is_interview' => '0', 'is_monetized' => '', 'copyright_status' => 'clear', 'platform_video_id' => 'changed', 'creator_channel_id' => CreatorChannel::factory()->create()->id];
        $this->actingAs($this->admin())->put(route('superadmin.creator-intelligence.videos.update', $video), $payload)->assertRedirect(route('superadmin.creator-intelligence.videos.show', $video));
        $video->refresh();
        $this->assertSame('Corrected title', $video->title);
        $this->assertSame('immutable-id', $video->platform_video_id);
        $this->assertSame($originalChannel, $video->creator_channel_id);
        $this->assertNull($video->is_monetized);
        $this->assertSame(0, $video->duration_seconds);
        $this->assertDatabaseCount('video_performance_snapshots', 1);
        $this->assertSame(123, $snapshot->fresh()->views);
    }

    public function test_edit_validation_rejects_invalid_enums_and_negative_duration(): void
    {
        $video = CreatorVideo::factory()->create();
        $this->actingAs($this->admin())->put(route('superadmin.creator-intelligence.videos.update', $video), ['title' => 'Title', 'duration_seconds' => -1, 'video_format' => 'invalid', 'content_type' => 'invalid', 'copyright_status' => 'invalid'])->assertSessionHasErrors(['duration_seconds', 'video_format', 'content_type', 'copyright_status']);
    }

    public function test_video_export_respects_filters_exports_all_rows_and_escapes_formulas(): void
    {
        $channel = CreatorChannel::factory()->create();
        foreach (range(1, 26) as $number) {
            $video = CreatorVideo::factory()->for($channel, 'channel')->create(['title' => $number === 1 ? '=Export Match 1' : 'Export Match '.$number, 'description' => null]);
            VideoPerformanceSnapshot::factory()->for($video, 'video')->create(['snapshot_date' => '2026-08-01', 'views' => $number === 2 ? 0 : $number, 'hype_points' => null]);
        }
        CreatorVideo::factory()->for($channel, 'channel')->create(['title' => 'Excluded']);
        $response = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.videos.export', ['q' => 'Export Match']));
        $content = $response->streamedContent();
        $response->assertOk();
        $this->assertSame(26, substr_count($content, 'Export Match'));
        $this->assertStringNotContainsString('Excluded', $content);
        $formula = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.videos.export', ['q' => '=Export']))->streamedContent();
        $this->assertStringContainsString("'=Export Match 1", $formula);
        $this->assertStringContainsString('Latest Snapshot Source', $formula);
    }

    public function test_snapshot_export_is_scoped_and_preserves_zero_and_blank_values(): void
    {
        $video = CreatorVideo::factory()->create();
        $other = CreatorVideo::factory()->create();
        VideoPerformanceSnapshot::factory()->for($video, 'video')->create(['views' => 0, 'hype_points' => null]);
        VideoPerformanceSnapshot::factory()->for($other, 'video')->create(['views' => 999999]);
        $content = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.videos.snapshots.export', $video))->streamedContent();
        $this->assertStringContainsString(',0,', $content);
        $this->assertStringNotContainsString('999999', $content);
    }

    private function admin(): User
    {
        return User::factory()->create(['can_manage_creator_intelligence' => true]);
    }
}
