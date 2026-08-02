<?php

namespace Tests\Feature;

use App\Models\CreatorChannel;
use App\Models\CreatorProfile;
use App\Models\CreatorVideo;
use App\Models\User;
use App\Models\VideoPerformanceSnapshot;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CreatorIntelligenceMilestoneOneTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_creator_intelligence(): void
    {
        $this->get(route('superadmin.creator-intelligence.overview'))->assertRedirect(route('login'));
    }

    public function test_unauthorized_users_receive_forbidden_and_do_not_see_navigation(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('superadmin.creator-intelligence.overview'))->assertForbidden();
        $this->actingAs($user)->get(route('dashboard'))->assertOk()->assertDontSee('Creator Intelligence');
    }

    public function test_superadmins_and_explicitly_authorized_users_can_access_and_see_navigation(): void
    {
        $admin = User::factory()->create(['email' => 'creator-intelligence@example.com']);
        config(['super_admin.emails' => [$admin->email]]);

        $this->actingAs($admin)->get(route('superadmin.creator-intelligence.overview'))->assertOk()->assertSee('Creator Intelligence');

        $authorized = User::factory()->create(['can_manage_creator_intelligence' => true]);
        $this->actingAs($authorized)->get(route('dashboard'))->assertOk()->assertSee('Creator Intelligence');
    }

    public function test_authorized_user_can_create_profile_and_channel(): void
    {
        $user = User::factory()->create(['can_manage_creator_intelligence' => true]);

        $this->actingAs($user)->post(route('superadmin.creator-intelligence.profiles.store'), [
            'display_name' => 'JFragment', 'slug' => 'jfragment', 'timezone' => 'America/New_York', 'default_currency' => 'usd',
        ])->assertRedirect(route('superadmin.creator-intelligence.profiles.index'));

        $profile = CreatorProfile::query()->sole();
        $this->assertSame('USD', $profile->default_currency);

        $this->actingAs($user)->post(route('superadmin.creator-intelligence.channels.store'), [
            'creator_profile_id' => $profile->id, 'platform' => 'youtube', 'channel_name' => 'JFragment', 'subject_label' => 'Artist', 'content_item_label' => 'Song', 'category_label' => 'Genre', 'default_publish_timezone' => 'America/New_York', 'is_active' => '1',
        ])->assertRedirect(route('superadmin.creator-intelligence.channels.index'));

        $this->assertTrue($profile->fresh()->channels()->sole()->is_active);
    }

    public function test_profile_and_channel_validation_rejects_invalid_data(): void
    {
        $user = User::factory()->create(['can_manage_creator_intelligence' => true]);

        $this->actingAs($user)->post(route('superadmin.creator-intelligence.profiles.store'), [
            'display_name' => '', 'slug' => 'bad slug', 'timezone' => 'Not/A_Timezone', 'default_currency' => 'US',
        ])->assertSessionHasErrors(['display_name', 'slug', 'timezone', 'default_currency']);

        $this->actingAs($user)->post(route('superadmin.creator-intelligence.channels.store'), [
            'creator_profile_id' => 999, 'platform' => '', 'channel_name' => '', 'subject_label' => '', 'content_item_label' => '', 'category_label' => '', 'default_publish_timezone' => 'invalid',
        ])->assertSessionHasErrors(['creator_profile_id', 'platform', 'channel_name', 'subject_label', 'content_item_label', 'category_label', 'default_publish_timezone']);
    }

    public function test_video_identity_is_unique_per_channel_but_reusable_across_channels(): void
    {
        $first = CreatorChannel::factory()->create();
        $second = CreatorChannel::factory()->create();
        CreatorVideo::factory()->for($first, 'channel')->create(['platform_video_id' => 'same-video']);
        CreatorVideo::factory()->for($second, 'channel')->create(['platform_video_id' => 'same-video']);

        $this->expectException(QueryException::class);
        CreatorVideo::factory()->for($first, 'channel')->create(['platform_video_id' => 'same-video']);
    }

    public function test_snapshot_identity_is_unique_and_missing_metrics_remain_null(): void
    {
        $video = CreatorVideo::factory()->create();
        $snapshot = VideoPerformanceSnapshot::create(['creator_video_id' => $video->id, 'snapshot_date' => '2026-08-01', 'source' => 'manual']);

        $this->assertNull($snapshot->fresh()->views);
        $this->assertNull($snapshot->fresh()->impressions_ctr);

        $this->expectException(QueryException::class);
        VideoPerformanceSnapshot::create(['creator_video_id' => $video->id, 'snapshot_date' => '2026-08-01', 'source' => 'manual']);
    }

    public function test_profile_user_is_nullable_on_user_delete(): void
    {
        $user = User::factory()->create();
        $profile = CreatorProfile::factory()->create(['user_id' => $user->id]);

        $user->forceDelete();

        $this->assertNull($profile->fresh()->user_id);
    }

    public function test_profile_deletion_cascades_through_channels_videos_and_snapshots(): void
    {
        $profile = CreatorProfile::factory()->create();
        $channel = CreatorChannel::factory()->for($profile, 'profile')->create();
        $video = CreatorVideo::factory()->for($channel, 'channel')->create();
        VideoPerformanceSnapshot::factory()->for($video, 'video')->create();

        $profile->delete();

        $this->assertDatabaseCount('creator_channels', 0);
        $this->assertDatabaseCount('creator_videos', 0);
        $this->assertDatabaseCount('video_performance_snapshots', 0);
    }

    public function test_migration_recovers_after_a_partial_mysql_style_schema_commit(): void
    {
        Schema::drop('video_performance_snapshots');

        $migration = require database_path('migrations/2026_08_01_000100_create_creator_intelligence_tables.php');
        $migration->up();
        $migration->up();

        $this->assertTrue(Schema::hasTable('creator_profiles'));
        $this->assertTrue(Schema::hasTable('creator_channels'));
        $this->assertTrue(Schema::hasTable('creator_videos'));
        $this->assertTrue(Schema::hasTable('video_performance_snapshots'));

        $video = CreatorVideo::factory()->create();
        VideoPerformanceSnapshot::create(['creator_video_id' => $video->id, 'snapshot_date' => '2026-08-01', 'source' => 'manual']);

        $this->expectException(QueryException::class);
        VideoPerformanceSnapshot::create(['creator_video_id' => $video->id, 'snapshot_date' => '2026-08-01', 'source' => 'manual']);
    }
}
