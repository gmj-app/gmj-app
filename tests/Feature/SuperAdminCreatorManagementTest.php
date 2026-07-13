<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorOwner;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SuperAdminCreatorManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['super_admin.emails' => ['admin@example.com']]);
    }

    public function test_creator_admin_routes_are_protected_and_list_is_searchable(): void
    {
        [$creator] = $this->creatorWithOwner('owner@example.com', 'Russell Creator');
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        $this->get(route('super-admin.creators.index'))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('super-admin.creators.index'))->assertForbidden();
        $this->actingAs($admin)->get(route('super-admin.creators.index', ['q' => 'owner@example.com']))
            ->assertOk()->assertSee('Russell Creator')->assertSee('owner@example.com');
        $this->get(route('super-admin.creators.assist', $creator))->assertOk()->assertSee('Super Admin Assistance Mode');
    }

    public function test_assisted_update_preserves_owner_and_private_identity_and_is_audited(): void
    {
        [$creator, $owner] = $this->creatorWithOwner('owner@example.com', 'Original');
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($admin)->put(route('super-admin.creators.update', $creator), [
            'display_name' => 'Updated Creator', 'slug' => 'updated-creator', 'youtube_channel_url' => 'https://youtube.com/@updated',
            'bio' => 'An assisted biography.', 'submission_instructions' => 'Keep it useful.', 'submissions_open' => '1',
            'recommendation_approval_mode' => Creator::APPROVAL_MODE_MANUAL, 'tags' => 'Music, Interviews',
            'owner_id' => $admin->id, 'google_id' => 'attempted-overwrite',
        ])->assertRedirect(route('super-admin.creators.assist', $creator));

        $creator->refresh();
        $this->assertSame('Updated Creator', $creator->display_name);
        $this->assertTrue($creator->creatorOwners()->where('user_id', $owner->id)->exists());
        $this->assertSame(2, $creator->creatorTags()->count());
        $this->assertSame('creator.profile.updated', SuperAdminAuditLog::query()->sole()->action);
        $this->assertSame('owner-google-id', $owner->fresh()->google_id);
    }

    public function test_starter_request_is_creator_attributed_and_lifecycle_restore_does_not_reactivate_resources(): void
    {
        [$creator, $owner] = $this->creatorWithOwner('owner@example.com', 'Creator');
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($admin)->post(route('super-admin.creators.starter', $creator), [
            'suggestions' => [['title' => 'Starter topic', 'url' => null, 'category' => 'culture', 'note' => 'Start here']],
        ])->assertRedirect();
        $request = $creator->recommendations()->sole();
        $this->assertSame('creator', $request->submission_source);
        $this->assertSame($owner->id, $request->submitted_by);
        $this->assertNotSame($admin->id, $request->submitted_by);

        $this->delete(route('super-admin.creators.destroy', $creator))->assertRedirect();
        $this->assertSoftDeleted($creator);
        $this->patch(route('super-admin.creators.restore', $creator->id))->assertRedirect();
        $this->assertNotSoftDeleted($creator);
        $this->assertSame($owner->id, $creator->creatorOwners()->where('role', 'owner')->value('user_id'));
    }

    private function creatorWithOwner(string $email, string $name): array
    {
        $creator = Creator::factory()->create(['display_name' => $name]);
        $owner = User::factory()->create(['email' => $email, 'google_id' => 'owner-google-id']);
        CreatorOwner::query()->create(['creator_id' => $creator->id, 'user_id' => $owner->id, 'role' => 'owner']);

        return [$creator, $owner];
    }
}
