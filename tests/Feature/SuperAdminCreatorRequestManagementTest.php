<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorOwner;
use App\Models\Recommendation;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SuperAdminCreatorRequestManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['super_admin.emails' => ['admin@example.com']]);
    }

    public function test_routes_are_protected_and_mismatched_creator_request_is_not_found(): void
    {
        [$creator, $request] = $this->creatorRequest();
        $other = Creator::factory()->create();
        $this->get(route('super-admin.creators.requests.index', $creator))->assertRedirect(route('login'));
        $this->actingAs(User::factory()->create())->get(route('super-admin.creators.requests.index', $creator))->assertForbidden();
        $this->actingAs($this->admin())->get(route('super-admin.creators.requests.index', $creator))->assertOk()->assertSee($request->title);
        $this->get(route('super-admin.creators.requests.edit', [$creator, $request]))->assertOk()->assertSee('Super Admin Assistance Mode');
        $this->get(route('super-admin.creators.requests.edit', [$other, $request]))->assertNotFound();
    }

    public function test_update_preserves_creator_submitter_origin_and_audits(): void
    {
        [$creator, $item, $submitter] = $this->creatorRequest();
        $admin = $this->admin();
        $this->actingAs($admin)->patch(route('super-admin.creators.requests.update', [$creator, $item]), [
            'title' => 'Corrected public title', 'youtube_url' => 'https://example.com/source', 'description' => 'Updated context',
            'category' => 'culture', 'tags' => 'History', 'updated_at' => $item->updated_at->toIso8601String(),
            'creator_id' => Creator::factory()->create()->id, 'submitted_by' => $admin->id,
        ])->assertRedirect();
        $item->refresh();
        $this->assertSame('Corrected public title', $item->title);
        $this->assertSame($creator->id, $item->creator_id);
        $this->assertSame($submitter->id, $item->submitted_by);
        $this->assertSame('request.updated', SuperAdminAuditLog::query()->sole()->action);
    }

    public function test_publication_requires_safe_metadata_releases_capacity_and_is_audited(): void
    {
        [$creator, $item, $submitter] = $this->creatorRequest(['status' => 'approved']);
        $admin = $this->admin();
        UserPick::factory()->create(['creator_id' => $creator->id, 'recommendation_id' => $item->id, 'user_id' => $submitter->id, 'vote_count' => 2]);
        $this->actingAs($admin)->post(route('super-admin.creators.requests.status', [$creator, $item]), ['status' => 'published'])->assertSessionHasErrors(['published_reaction_url', 'published_at']);
        $this->post(route('super-admin.creators.requests.status', [$creator, $item]), ['status' => 'published', 'published_reaction_url' => 'javascript:alert(1)', 'published_at' => now()])->assertSessionHasErrors('published_reaction_url');
        Http::fake(['*youtube.com/oembed*' => Http::response(['title' => 'Published Work', 'author_name' => 'Creator Channel'])]);
        $this->post(route('super-admin.creators.requests.status', [$creator, $item]), ['status' => 'published', 'published_reaction_url' => 'https://www.youtube.com/watch?v=REACTION001', 'published_at' => now()->toDateTimeString()])->assertRedirect();
        $item->refresh();
        $this->assertSame('published', $item->status);
        $this->assertSame('Published Work', $item->published_title);
        $this->assertSame(0, $submitter->fresh()->votesUsedFor($creator));
        $this->assertSame(0, $submitter->fresh()->suggestionsUsedFor($creator));
        $this->assertSame('request.published', SuperAdminAuditLog::query()->sole()->action);
        $this->assertSame(2, data_get(SuperAdminAuditLog::query()->sole()->metadata, 'released_votes'));
    }

    public function test_spam_removal_soft_deletes_releases_resources_and_restore_stays_released(): void
    {
        [$creator, $item, $submitter] = $this->creatorRequest(['status' => 'approved']);
        $admin = $this->admin();
        UserPick::factory()->create(['creator_id' => $creator->id, 'recommendation_id' => $item->id, 'user_id' => $submitter->id, 'vote_count' => 2]);
        $this->actingAs($admin)->delete(route('super-admin.creators.requests.destroy', [$creator, $item]), ['moderation_reason' => 'spam'])->assertRedirect();
        $this->assertSoftDeleted($item);
        $this->assertSame(0, $submitter->fresh()->votesUsedFor($creator));
        $this->assertSame(0, $submitter->fresh()->suggestionsUsedFor($creator));
        $this->post(route('super-admin.creators.requests.restore', [$creator, $item->id]), ['status' => 'pending'])->assertRedirect();
        $item->refresh();
        $this->assertSame('pending', $item->status);
        $this->assertSame(0, $submitter->fresh()->votesUsedFor($creator));
        $this->assertSame(0, $submitter->fresh()->suggestionsUsedFor($creator));
        $this->assertSame(['request.soft_deleted', 'request.restored'], SuperAdminAuditLog::query()->orderBy('id')->pluck('action')->all());
    }

    private function admin(): User
    {
        return User::factory()->create(['email' => 'admin@example.com']);
    }

    private function creatorRequest(array $attributes = []): array
    {
        $creator = Creator::factory()->create();
        $owner = User::factory()->create();
        CreatorOwner::query()->create(['creator_id' => $creator->id, 'user_id' => $owner->id]);
        $submitter = User::factory()->create();
        $item = Recommendation::factory()->create([...$attributes, 'creator_id' => $creator->id, 'submitted_by' => $submitter->id, 'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN]);

        return [$creator, $item, $submitter];
    }
}
