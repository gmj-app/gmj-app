<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorOwner;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
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

    public function test_browser_form_submission_persists_scalar_settings_and_shows_success(): void
    {
        [$creator] = $this->creatorWithOwner('owner@example.com', 'Original');
        $creator->update(['submissions_open' => true, 'recommendation_approval_mode' => Creator::APPROVAL_MODE_AUTO]);
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        $response = $this->actingAs($admin)->post(route('super-admin.creators.update', $creator), [
            '_method' => 'PUT',
            'display_name' => $creator->display_name,
            'slug' => $creator->slug,
            'youtube_channel_url' => $creator->youtube_channel_url ?: $creator->channel_url,
            'submissions_open' => '0',
            'recommendation_approval_mode' => Creator::APPROVAL_MODE_MANUAL,
            'save_action' => 'save',
        ]);

        $response->assertStatus(302)
            ->assertRedirect(route('super-admin.creators.assist', $creator))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('success');
        $this->assertFalse($creator->fresh()->submissions_open);
        $this->assertSame(Creator::APPROVAL_MODE_MANUAL, $creator->fresh()->recommendation_approval_mode);
        $this->followRedirects($response)->assertSee('Creator space updated successfully.');
    }

    public function test_save_and_preview_persists_before_redirecting_to_public_creator_page(): void
    {
        [$creator] = $this->creatorWithOwner('owner@example.com', 'Original');
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        $response = $this->actingAs($admin)->post(route('super-admin.creators.update', $creator), [
            '_method' => 'PUT',
            'display_name' => 'Previewed Creator',
            'slug' => $creator->slug,
            'submissions_open' => '1',
            'recommendation_approval_mode' => Creator::APPROVAL_MODE_AUTO,
            'save_action' => 'preview',
        ]);

        $response->assertStatus(302)->assertRedirect(route('super-admin.creators.preview', $creator));
        $this->assertSame('Previewed Creator', $creator->fresh()->display_name);
    }

    public function test_unchanged_legacy_identity_values_do_not_block_unrelated_assistance_updates(): void
    {
        [$creator] = $this->creatorWithOwner('owner@example.com', 'Original');
        $creator->forceFill(['slug' => 'Legacy_Slug', 'channel_url' => 'legacy-channel-reference', 'youtube_channel_url' => null])->save();
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($admin)->post(route('super-admin.creators.update', $creator), [
            '_method' => 'PUT',
            'display_name' => $creator->display_name,
            'slug' => 'Legacy_Slug',
            'youtube_channel_url' => 'legacy-channel-reference',
            'submissions_open' => '0',
            'recommendation_approval_mode' => Creator::APPROVAL_MODE_MANUAL,
            'save_action' => 'save',
        ])->assertRedirect(route('super-admin.creators.assist', $creator))->assertSessionHasNoErrors();

        $this->assertFalse($creator->fresh()->submissions_open);
    }

    public function test_assistance_validation_errors_are_visible_and_do_not_persist_partial_changes(): void
    {
        [$creator] = $this->creatorWithOwner('owner@example.com', 'Original');
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        $response = $this->actingAs($admin)->from(route('super-admin.creators.assist', $creator))
            ->post(route('super-admin.creators.update', $creator), [
                '_method' => 'PUT',
                'display_name' => 'Should Not Persist',
                'slug' => 'invalid changed slug',
                'submissions_open' => '0',
                'recommendation_approval_mode' => Creator::APPROVAL_MODE_MANUAL,
                'save_action' => 'save',
            ]);

        $response->assertStatus(302)->assertSessionHasErrors('slug');
        $this->assertSame('Original', $creator->fresh()->display_name);
        $this->followRedirects($response)
            ->assertSee('No changes were saved. Please correct the following:')
            ->assertSee('format is invalid');
    }

    public function test_assistance_mode_uploads_and_safely_replaces_creator_media(): void
    {
        Storage::fake('creator_uploads');
        [$creator] = $this->creatorWithOwner('owner@example.com', 'Original');
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($admin)->get(route('super-admin.creators.assist', $creator))
            ->assertOk()
            ->assertSee('enctype="multipart/form-data"', false)
            ->assertSee('New avatar preview')
            ->assertSee('New banner preview')
            ->assertDontSee('Remove current avatar')
            ->assertDontSee('Remove current banner');

        $payload = [
            'display_name' => $creator->display_name,
            'slug' => $creator->slug,
            'submissions_open' => '1',
            'recommendation_approval_mode' => Creator::APPROVAL_MODE_MANUAL,
        ];
        $this->put(route('super-admin.creators.update', $creator), [
            ...$payload,
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 800, 800),
            'hero' => UploadedFile::fake()->image('banner.jpg', 1600, 500),
        ])->assertRedirect(route('super-admin.creators.assist', $creator));

        $creator->refresh();
        $oldAvatar = $creator->avatar_path;
        $oldHero = $creator->hero_path;
        Storage::disk('creator_uploads')->assertExists([$oldAvatar, $oldHero]);

        $this->put(route('super-admin.creators.update', $creator), [
            ...$payload,
            'avatar' => UploadedFile::fake()->image('replacement.webp', 600, 600),
        ])->assertRedirect();

        $creator->refresh();
        $this->assertNotSame($oldAvatar, $creator->avatar_path);
        $this->assertSame($oldHero, $creator->hero_path);
        Storage::disk('creator_uploads')->assertMissing($oldAvatar);
        Storage::disk('creator_uploads')->assertExists([$creator->avatar_path, $creator->hero_path]);
        $this->assertSame(['avatar'], SuperAdminAuditLog::query()->latest('id')->first()->metadata['assets']);
    }

    public function test_assistance_mode_saves_avatar_only_banner_only_and_combined_uploads(): void
    {
        Storage::fake('creator_uploads');
        [$creator] = $this->creatorWithOwner('owner@example.com', 'Original');
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $payload = [
            'display_name' => $creator->display_name,
            'slug' => $creator->slug,
            'submissions_open' => '1',
            'recommendation_approval_mode' => Creator::APPROVAL_MODE_MANUAL,
            'save_action' => 'save',
        ];

        $this->actingAs($admin)->put(route('super-admin.creators.update', $creator), [
            ...$payload,
            'avatar' => UploadedFile::fake()->image('avatar.jpg', 600, 600),
        ])->assertSessionHas('success');
        $avatarPath = $creator->fresh()->avatar_path;
        $this->assertNotNull($avatarPath);
        $this->assertNull($creator->fresh()->hero_path);

        $this->put(route('super-admin.creators.update', $creator), [
            ...$payload,
            'hero' => UploadedFile::fake()->image('banner.jpg', 1400, 500),
        ])->assertSessionHas('success');
        $bannerPath = $creator->fresh()->hero_path;
        $this->assertSame($avatarPath, $creator->fresh()->avatar_path);
        $this->assertNotNull($bannerPath);

        $this->put(route('super-admin.creators.update', $creator), [
            ...$payload,
            'avatar' => UploadedFile::fake()->image('avatar-2.webp', 600, 600),
            'hero' => UploadedFile::fake()->image('banner-2.webp', 1400, 500),
        ])->assertSessionHas('success');
        $creator->refresh();
        $this->assertNotSame($avatarPath, $creator->avatar_path);
        $this->assertNotSame($bannerPath, $creator->hero_path);
        Storage::disk('creator_uploads')->assertMissing([$avatarPath, $bannerPath]);
        Storage::disk('creator_uploads')->assertExists([$creator->avatar_path, $creator->hero_path]);
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
