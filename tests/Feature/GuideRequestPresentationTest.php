<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorOwner;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class GuideRequestPresentationTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_edit_only_presentation_fields_and_a_revision_is_recorded(): void
    {
        Notification::fake();
        [$guide, $request] = $this->guideRequest();
        $identity = $request->only(['creator_id', 'youtube_url', 'normalized_url', 'youtube_video_id', 'title', 'status']);

        $this->actingAs($guide)->patch(route('requests.presentation.update', $request), [
            'display_title_override' => '  A clearer   public title ',
            'request_context' => "Useful context\nfor the creator.",
        ])->assertRedirect()->assertSessionHasNoErrors();

        $request->refresh();
        $this->assertSame('A clearer public title', $request->display_title_override);
        $this->assertSame("Useful context\nfor the creator.", $request->request_context);
        $this->assertSame($identity, $request->only(array_keys($identity)));
        $this->assertDatabaseHas('request_presentation_revisions', [
            'recommendation_id' => $request->id,
            'actor_id' => $guide->id,
            'actor_context' => 'guide',
            'action' => 'request.guide_presentation_updated',
        ]);
        Notification::assertNothingSent();
    }

    public function test_crafted_identity_fields_are_rejected_and_nothing_changes(): void
    {
        [$guide, $request] = $this->guideRequest();

        $this->actingAs($guide)->patch(route('requests.presentation.update', $request), [
            'display_title_override' => 'Allowed title',
            'youtube_url' => 'https://www.youtube.com/watch?v=BBBBBBBBBBB',
            'status' => 'published',
        ])->assertSessionHasErrors(['request_identity' => 'The linked request content cannot be changed after submission.']);

        $request->refresh();
        $this->assertNull($request->display_title_override);
        $this->assertSame('approved', $request->status);
        $this->assertSame('https://www.youtube.com/watch?v=AAAAAAAAAAA', $request->youtube_url);
        $this->assertDatabaseCount('request_presentation_revisions', 0);
    }

    public function test_non_owner_creator_added_and_terminal_requests_are_forbidden(): void
    {
        [$guide, $request] = $this->guideRequest();
        $other = User::factory()->create();

        $this->actingAs($other)->get(route('requests.presentation.edit', $request))->assertForbidden();

        $request->update(['submission_source' => Recommendation::SUBMISSION_SOURCE_CREATOR]);
        $this->actingAs($guide)->get(route('requests.presentation.edit', $request))->assertForbidden();

        $request->update(['submission_source' => Recommendation::SUBMISSION_SOURCE_FAN, 'status' => 'published']);
        $this->actingAs($guide)->patch(route('requests.presentation.update', $request), ['display_title_override' => 'No'])->assertForbidden();

        foreach (['passed', 'already_seen', 'hidden', 'withdrawn'] as $status) {
            $request->update(['status' => $status]);
            $this->assertFalse($guide->can('updateOwnPresentation', $request->fresh()));
        }

        foreach (['pending', 'approved', 'coming_soon', 'scheduled', 'recorded'] as $status) {
            $request->update(['status' => $status]);
            $this->assertTrue($guide->can('updateOwnPresentation', $request->fresh()));
        }
    }

    public function test_guest_spam_removed_and_soft_deleted_requests_cannot_be_edited(): void
    {
        [$guide, $request] = $this->guideRequest();
        $this->get(route('requests.presentation.edit', $request))->assertRedirect(route('login'));

        $request->update(['moderation_status' => 'removed']);
        $this->actingAs($guide)->get(route('requests.presentation.edit', $request))->assertForbidden();

        $request->update(['moderation_status' => null]);
        $request->delete();
        $this->actingAs($guide)->get('/requests/'.$request->id.'/presentation/edit')->assertNotFound();
    }

    public function test_shared_edit_action_renders_for_owner_across_public_activity_and_profile_surfaces(): void
    {
        [$guide, $request] = $this->guideRequest(['title' => 'Owned approved request']);
        $guide->update(['public_profile_enabled' => true]);
        $other = User::factory()->create();

        foreach ([
            route('creator.queue', $request->creator),
            route('activity.index'),
            route('guides.show', ['handle' => $guide->public_handle]),
        ] as $url) {
            $this->actingAs($guide)->get($url)
                ->assertOk()
                ->assertSee('You requested', escape: false)
                ->assertSee(route('requests.presentation.edit', $request), escape: false);
        }

        $this->actingAs($other)->get(route('creator.queue', $request->creator))
            ->assertOk()
            ->assertDontSee('You requested')
            ->assertDontSee(route('requests.presentation.edit', $request), escape: false);
    }

    public function test_pending_request_uses_shared_activity_cta_but_terminal_and_creator_added_requests_do_not(): void
    {
        [$guide, $request] = $this->guideRequest(['status' => 'pending', 'title' => 'Pending owned request']);

        $this->actingAs($guide)->get(route('activity.index'))
            ->assertOk()
            ->assertSee(route('requests.presentation.edit', $request), escape: false);

        foreach (['published', 'passed', 'already_seen'] as $status) {
            $request->update(['status' => $status]);
            $this->actingAs($guide)->get(route('activity.index'))
                ->assertOk()
                ->assertDontSee(route('requests.presentation.edit', $request), escape: false);
        }

        $request->update(['status' => 'approved', 'submission_source' => Recommendation::SUBMISSION_SOURCE_CREATOR]);
        $this->actingAs($guide)->get(route('creator.queue', $request->creator))
            ->assertOk()
            ->assertDontSee('You requested')
            ->assertDontSee(route('requests.presentation.edit', $request), escape: false);
    }

    public function test_no_revision_is_created_when_normalized_values_are_unchanged(): void
    {
        [$guide, $request] = $this->guideRequest(['display_title_override' => 'Same title', 'request_context' => 'Same context']);

        $this->actingAs($guide)->patch(route('requests.presentation.update', $request), [
            'display_title_override' => ' Same   title ',
            'request_context' => 'Same context',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseCount('request_presentation_revisions', 0);
    }

    public function test_display_priority_search_and_published_priority_are_preserved(): void
    {
        [$guide, $request] = $this->guideRequest([
            'title' => 'Fallback canonical title',
            'source_title' => 'Fetched canonical title',
            'display_title_override' => 'Guide polished title',
        ]);

        $this->assertSame('Guide polished title', $request->displayTitle());
        $this->actingAs($guide)->get(route('creator.queue', $request->creator))->assertOk()->assertSee('Guide polished title')->assertDontSee('Fetched canonical title');
        $this->get(route('search.index', ['q' => 'polished']))->assertOk()->assertSee('Guide polished title');

        $request->update(['status' => 'published', 'published_title' => 'Creator published result']);
        $this->assertSame('Creator published result', $request->fresh()->displayPublishedTitle());
    }

    public function test_correction_is_separate_from_live_identity_and_can_be_cancelled(): void
    {
        [$guide, $request] = $this->guideRequest();

        $this->actingAs($guide)->post(route('requests.corrections.store', $request), [
            'proposed_url' => 'https://www.youtube.com/watch?v=CCCCCCCCCCC',
            'explanation' => 'The original link points to the wrong upload.',
        ])->assertSessionHasNoErrors();

        $correction = $request->identityCorrections()->firstOrFail();
        $this->assertSame('https://www.youtube.com/watch?v=AAAAAAAAAAA', $request->fresh()->youtube_url);
        $this->actingAs($guide)->post(route('requests.corrections.cancel', [$request, $correction]))->assertSessionHasNoErrors();
        $this->assertSame('cancelled', $correction->fresh()->status);
    }

    public function test_limits_plain_text_escaping_and_explicit_clearing(): void
    {
        [$guide, $request] = $this->guideRequest();

        $this->actingAs($guide)->patch(route('requests.presentation.update', $request), [
            'display_title_override' => str_repeat('x', 161),
            'request_context' => str_repeat('y', 2001),
        ])->assertSessionHasErrors(['display_title_override', 'request_context']);

        $this->actingAs($guide)->patch(route('requests.presentation.update', $request), [
            'display_title_override' => '<script>alert(1)</script> Helpful title',
            'request_context' => '<img src=x onerror=alert(1)>',
        ])->assertSessionHasNoErrors();
        $this->actingAs($guide)->get(route('creator.queue', $request->creator))
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt; Helpful title', false)
            ->assertDontSee('<script>alert(1)</script>', false);

        $this->actingAs($guide)->patch(route('requests.presentation.update', $request), [
            'display_title_override' => '   ',
            'request_context' => "\n ",
        ])->assertSessionHasNoErrors();
        $this->assertNull($request->fresh()->display_title_override);
        $this->assertNull($request->fresh()->request_context);
    }

    public function test_super_admin_can_clear_and_revert_a_guide_override_with_audit_history(): void
    {
        config(['super_admin.emails' => ['admin@example.com']]);
        [$guide, $request] = $this->guideRequest(['display_title_override' => 'Guide title', 'request_context' => 'Guide context']);
        $admin = User::factory()->create(['email' => 'admin@example.com']);

        $this->actingAs($admin)->delete(route('super-admin.creators.requests.presentation.clear', [$request->creator, $request]))
            ->assertRedirect();
        $clearRevision = $request->presentationRevisions()->where('action', 'request.display_title_override_cleared')->sole();
        $this->assertNull($request->fresh()->display_title_override);

        $this->actingAs($admin)->post(route('super-admin.creators.requests.presentation.revert', [$request->creator, $request, $clearRevision]))
            ->assertRedirect();
        $this->assertSame('Guide title', $request->fresh()->display_title_override);
        $this->assertDatabaseHas('super_admin_audit_logs', ['action' => 'request.display_title_override_cleared']);
        $this->assertDatabaseHas('super_admin_audit_logs', ['action' => 'request.display_title_override_reverted']);
    }

    public function test_creator_owner_can_inspect_clear_and_revert_presentation_history(): void
    {
        [$guide, $request] = $this->guideRequest(['display_title_override' => 'Guide title', 'request_context' => 'Guide context']);
        $owner = User::factory()->create();
        CreatorOwner::query()->create(['creator_id' => $request->creator_id, 'user_id' => $owner->id, 'role' => 'owner']);

        $this->actingAs($owner)->get(route('creators.recommendations.index', $request->creator))
            ->assertOk()->assertSee('Guide title')->assertSee('Canonical:');
        $this->actingAs($owner)->delete(route('creators.recommendations.presentation.clear', [$request->creator, $request]))->assertRedirect();
        $revision = $request->presentationRevisions()->where('action', 'request.display_title_override_cleared')->sole();
        $this->assertNull($request->fresh()->display_title_override);

        $this->actingAs($owner)->post(route('creators.recommendations.presentation.revert', [$request->creator, $request, $revision]))->assertRedirect();
        $this->assertSame('Guide title', $request->fresh()->display_title_override);
    }

    /** @return array{User, Recommendation} */
    private function guideRequest(array $attributes = []): array
    {
        $guide = User::factory()->create();
        $creator = Creator::factory()->create();
        $request = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $guide->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'status' => 'approved',
            'youtube_url' => 'https://www.youtube.com/watch?v=AAAAAAAAAAA',
            'normalized_url' => 'https://www.youtube.com/watch?v=AAAAAAAAAAA',
            'youtube_video_id' => 'AAAAAAAAAAA',
            ...$attributes,
        ]);

        return [$guide, $request];
    }
}
