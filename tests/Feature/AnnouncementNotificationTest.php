<?php

namespace Tests\Feature;

use App\Models\Announcement;
use App\Models\Creator;
use App\Models\CreatorOwner;
use App\Models\User;
use App\Services\AnnouncementPublicationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AnnouncementNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['super_admin.emails' => ['admin@example.com']]);
    }

    public function test_announcement_admin_is_protected(): void
    {
        $this->get(route('super-admin.announcements.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('super-admin.announcements.index'))->assertForbidden();
        $this->actingAs($this->admin())->get(route('super-admin.announcements.index'))->assertOk()->assertSee('Create announcement');
    }

    public function test_site_wide_announcement_delivers_once_to_active_users_and_reports_counts(): void
    {
        $admin = $this->admin();
        $first = User::factory()->create();
        $second = User::factory()->create();
        $deleted = User::factory()->create();
        $deleted->delete();

        $this->actingAs($admin)->post(route('super-admin.announcements.store'), $this->payload([
            'internal_name' => 'Summer launch',
            'title' => '<b>Summer update</b>',
            'message' => '<script>alert(1)</script>New features are ready.',
            'publish_timing' => 'now',
        ]))->assertSessionHasNoErrors()->assertSessionHas('success', 'Announcement queued for delivery.');

        $announcement = Announcement::query()->sole();
        $this->assertSame(Announcement::STATUS_PUBLISHED, $announcement->status);
        $this->assertSame(3, $announcement->recipient_count);
        $this->assertSame(3, $announcement->delivered_count);
        $this->assertSame(0, $announcement->failed_count);
        $this->assertCount(1, $first->notifications);
        $this->assertCount(1, $second->notifications);
        $this->assertCount(0, $deleted->notifications);
        $this->assertSame('Summer update', $first->notifications->first()->data['title']);
        $this->assertSame('alert(1)New features are ready.', $first->notifications->first()->data['message']);
        $this->assertSame('announcement', $first->notifications->first()->data['category']);

        app(AnnouncementPublicationService::class)->queue($announcement);
        $this->assertCount(1, $first->fresh()->notifications);
        $this->assertDatabaseHas('super_admin_audit_logs', ['action' => 'announcement.created', 'auditable_id' => $announcement->id]);
        $this->assertDatabaseHas('super_admin_audit_logs', ['action' => 'announcement.published', 'auditable_id' => $announcement->id]);
    }

    public function test_creator_only_announcement_targets_distinct_active_creator_owners(): void
    {
        $admin = $this->admin();
        $creatorOwner = User::factory()->create();
        $guideOnly = User::factory()->create();
        $inactiveOwner = User::factory()->create();
        foreach (range(1, 2) as $number) {
            $creator = Creator::factory()->create();
            CreatorOwner::query()->create(['creator_id' => $creator->id, 'user_id' => $creatorOwner->id]);
        }
        $inactiveCreator = Creator::factory()->create(['status' => 'inactive', 'deactivated_at' => now()]);
        CreatorOwner::query()->create(['creator_id' => $inactiveCreator->id, 'user_id' => $inactiveOwner->id]);

        $this->actingAs($admin)->post(route('super-admin.announcements.store'), $this->payload([
            'audience' => Announcement::AUDIENCE_CREATORS,
            'publish_timing' => 'now',
        ]))->assertSessionHasNoErrors();

        $announcement = Announcement::query()->sole();
        $this->assertSame(1, $announcement->recipient_count);
        $this->assertCount(1, $creatorOwner->notifications);
        $this->assertSame('creator', $creatorOwner->notifications->first()->data['audience']);
        $this->assertCount(0, $guideOnly->notifications);
        $this->assertCount(0, $inactiveOwner->notifications);
    }

    public function test_scheduled_announcement_is_published_when_due_and_cancelled_one_is_not(): void
    {
        $now = now()->startOfMinute();
        $this->travelTo($now);
        $admin = $this->admin();
        $recipient = User::factory()->create();
        $startsAt = $now->copy()->addMinutes(10);

        $this->actingAs($admin)->post(route('super-admin.announcements.store'), $this->payload([
            'internal_name' => 'Scheduled delivery',
            'publish_timing' => 'schedule',
            'starts_at' => $startsAt->toDateTimeString(),
        ]))->assertSessionHasNoErrors();
        $scheduled = Announcement::query()->sole();
        $this->assertSame(Announcement::STATUS_SCHEDULED, $scheduled->status);
        $this->assertCount(0, $recipient->notifications);

        $this->travelTo($startsAt->copy()->addMinute());
        $this->artisan('announcements:publish-due')->assertSuccessful();
        $this->assertSame(Announcement::STATUS_PUBLISHED, $scheduled->fresh()->status);
        $this->assertCount(1, $recipient->fresh()->notifications);
        $this->artisan('announcements:publish-due')->assertSuccessful();
        $this->assertCount(1, $recipient->fresh()->notifications);

        $cancelled = Announcement::factory()->create(['created_by_user_id' => $admin->id, 'updated_by_user_id' => $admin->id, 'status' => Announcement::STATUS_SCHEDULED, 'starts_at' => now()->addMinute()]);
        $this->actingAs($admin)->post(route('super-admin.announcements.cancel', $cancelled))->assertRedirect();
        $this->travel(2)->minutes();
        $this->artisan('announcements:publish-due')->assertSuccessful();
        $this->assertSame(Announcement::STATUS_CANCELLED, $cancelled->fresh()->status);
    }

    public function test_announcement_validation_rejects_unsafe_urls_and_allowlist_violations(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->post(route('super-admin.announcements.store'), $this->payload([
            'action_url' => '//evil.example',
            'icon' => 'arbitrary-class',
            'severity' => 'extreme',
        ]))->assertSessionHasErrors(['action_url', 'icon', 'severity']);

        $this->assertDatabaseCount('announcements', 0);
    }

    private function admin(): User
    {
        return User::factory()->create(['email' => 'admin@example.com']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'internal_name' => 'Internal update',
            'title' => 'Product update',
            'message' => 'A useful product update is available.',
            'audience' => Announcement::AUDIENCE_ALL,
            'action_url' => '/notifications',
            'action_label' => 'View update',
            'icon' => 'megaphone',
            'severity' => 'info',
            'publish_timing' => 'draft',
        ], $overrides);
    }
}
