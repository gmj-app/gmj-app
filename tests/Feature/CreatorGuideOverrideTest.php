<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\CreatorGuideOverride;
use App\Models\Recommendation;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use App\Services\GuideRequestLimitService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorGuideOverrideTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['super_admin.emails' => ['admin@example.com']]);
    }

    public function test_effective_limit_is_creator_specific_and_preserves_active_count_semantics(): void
    {
        $guide = User::factory()->create();
        $creatorA = Creator::factory()->create();
        $creatorB = Creator::factory()->create();
        CreatorGuideOverride::query()->create(['creator_id' => $creatorA->id, 'user_id' => $guide->id, 'request_limit' => 10]);
        $this->request($guide, $creatorA, 'approved');
        $this->request($guide, $creatorA, 'pending');
        $this->request($guide, $creatorA, 'published');
        $this->request($guide, $creatorA, 'withdrawn');

        $limits = app(GuideRequestLimitService::class);
        $this->assertSame(10, $limits->getLimit($guide, $creatorA));
        $this->assertSame(3, $limits->getLimit($guide, $creatorB));
        $this->assertSame(2, $limits->getActiveRequestCount($guide, $creatorA));
        $this->assertSame(8, $limits->remainingSlots($guide, $creatorA));
    }

    public function test_super_admin_can_add_edit_and_remove_an_override_by_normalized_email(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $guide = User::factory()->create(['email' => 'Jane@Example.com']);
        $creator = Creator::factory()->create();

        $this->actingAs($admin)->post(route('super-admin.creators.super-guides.store', $creator), ['guide_email' => '  jane@example.COM ', 'request_limit' => 10])->assertRedirect()->assertSessionHasNoErrors();
        $override = CreatorGuideOverride::query()->sole();
        $this->assertSame($guide->id, $override->user_id);
        $this->assertSame($admin->id, $override->created_by_user_id);

        $this->put(route('super-admin.creators.super-guides.update', [$creator, $override]), ['request_limit' => 15])->assertRedirect()->assertSessionHasNoErrors();
        $this->assertSame(15, $override->fresh()->request_limit);

        $this->delete(route('super-admin.creators.super-guides.destroy', [$creator, $override]))->assertRedirect();
        $this->assertDatabaseMissing('creator_guide_overrides', ['id' => $override->id]);
        $this->assertSame(3, app(GuideRequestLimitService::class)->getLimit($guide, $creator));
        $this->assertSame(['creator.super_guide.added', 'creator.super_guide.updated', 'creator.super_guide.removed'], SuperAdminAuditLog::query()->orderBy('id')->pluck('action')->all());
    }

    public function test_management_is_protected_and_validation_rejects_unknown_duplicate_and_invalid_overrides(): void
    {
        $creator = Creator::factory()->create();
        $guide = User::factory()->create();
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $route = route('super-admin.creators.super-guides.store', $creator);

        $this->actingAs($guide)->post($route, ['guide_email' => $guide->email, 'request_limit' => 10])->assertForbidden();
        $this->actingAs($admin)->post($route, ['guide_email' => 'missing@example.com', 'request_limit' => 10])->assertSessionHasErrors(['guide_email' => 'No Guide account was found with that email.']);
        $this->post($route, ['guide_email' => $guide->email, 'request_limit' => 3])->assertSessionHasErrors('request_limit');
        $this->post($route, ['guide_email' => $guide->email, 'request_limit' => 10])->assertSessionHasNoErrors();
        $this->post($route, ['guide_email' => $guide->email, 'request_limit' => 12])->assertSessionHasErrors('guide_email');
    }

    public function test_lowering_or_removing_an_override_preserves_requests_and_blocks_future_submission(): void
    {
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $guide = User::factory()->create();
        $creator = Creator::factory()->create();
        CreatorFavorite::query()->create(['creator_id' => $creator->id, 'user_id' => $guide->id]);
        $override = CreatorGuideOverride::query()->create(['creator_id' => $creator->id, 'user_id' => $guide->id, 'request_limit' => 10]);
        foreach (range(1, 8) as $number) {
            $this->request($guide, $creator, 'approved', "Active {$number}");
        }

        $this->actingAs($admin)->put(route('super-admin.creators.super-guides.update', [$creator, $override]), ['request_limit' => 5]);
        $this->assertSame(8, Recommendation::query()->count());
        $this->actingAs($guide)->get(route('recommendations.create', $creator))->assertSee('0 of 5')->assertSee('Request limit reached');
        $this->post(route('recommendations.store', $creator), ['recommendation_type' => 'topic', 'title' => 'Blocked request', 'description' => 'Should not be created.'])->assertSessionHasErrors(['limit' => 'You can have up to 5 active Requests for this Creator.']);

        $this->actingAs($admin)->delete(route('super-admin.creators.super-guides.destroy', [$creator, $override]));
        $this->assertSame(8, Recommendation::query()->count());
        $this->actingAs($guide)->get(route('recommendations.create', $creator))->assertSee('0 of 3');
    }

    private function request(User $guide, Creator $creator, string $status, ?string $title = null): Recommendation
    {
        return Recommendation::factory()->create(['creator_id' => $creator->id, 'submitted_by' => $guide->id, 'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN, 'status' => $status, 'title' => $title ?? $status, 'resource_released_at' => in_array($status, ['published', 'withdrawn'], true) ? now() : null]);
    }
}
