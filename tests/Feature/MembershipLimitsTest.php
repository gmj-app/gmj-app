<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\Recommendation;
use App\Models\User;
use App\Services\PlanEntitlementService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MembershipLimitsTest extends TestCase
{
    use RefreshDatabase;

    public function test_free_request_creation_limit_remains_three_per_creator(): void
    {
        $user = User::factory()->create();
        $creator = Creator::factory()->create();
        CreatorFavorite::query()->create(['creator_id' => $creator->id, 'user_id' => $user->id]);

        Recommendation::factory()->count(3)->create([
            'creator_id' => $creator->id,
            'submitted_by' => $user->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
        ]);

        $this->assertSame(0, $user->fresh()->suggestionsRemainingFor($creator));
        $this->assertFalse($user->fresh()->canSuggestTo($creator));
    }

    public function test_plan_entitlements_have_no_vote_inventory(): void
    {
        $plans = app(PlanEntitlementService::class);
        $free = $plans->getLimitsForUser(User::factory()->make(['plan_slug' => 'free']));

        $this->assertSame(3, $free['suggestions_per_creator_limit']);
        $this->assertArrayNotHasKey('upvotes_per_creator_limit', $free);
        $this->assertArrayNotHasKey('votes_per_reactor', User::factory()->make()->membershipLimits());
    }

    public function test_guide_can_vote_once_on_unlimited_distinct_requests_for_same_creator(): void
    {
        $creator = Creator::factory()->create();
        $requests = Recommendation::factory()->count(8)->create(['creator_id' => $creator->id, 'status' => 'approved']);
        $user = User::factory()->create();

        foreach ($requests as $request) {
            $this->actingAs($user)->post(route('recommendations.vote', [$creator, $request]))
                ->assertSessionHas('recommendation_action.type', 'added');
        }

        $this->assertSame(8, $user->userPicks()->count());
        $this->assertSame(8, $user->votesUsedFor($creator));
        $this->assertDatabaseCount('creator_favorites', 0);
    }

    public function test_duplicate_vote_is_idempotent_and_unvote_and_revote_work(): void
    {
        $creator = Creator::factory()->create();
        $request = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'approved']);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('recommendations.vote', [$creator, $request]));
        $this->post(route('recommendations.vote', [$creator, $request]))
            ->assertSessionHas('recommendation_action.message', 'You already voted.');
        $this->assertDatabaseCount('user_picks', 1);
        $this->assertSame(1, $request->fresh()->totalVotes());

        $this->delete(route('recommendations.vote.destroy', [$creator, $request]))
            ->assertSessionHas('recommendation_action.message', 'Your vote was removed.');
        $this->delete(route('recommendations.vote.destroy', [$creator, $request]))
            ->assertSessionHas('recommendation_action.message', 'Your vote was already removed.');
        $this->assertDatabaseCount('user_picks', 0);

        $this->post(route('recommendations.vote', [$creator, $request]));
        $this->assertDatabaseCount('user_picks', 1);
    }

    public function test_vote_payload_rejects_quantity_and_closed_requests(): void
    {
        $creator = Creator::factory()->create();
        $active = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'approved']);
        $closed = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'published']);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('recommendations.vote', [$creator, $active]), ['quantity' => 2])
            ->assertSessionHasErrors('quantity');
        $this->post(route('recommendations.vote', [$creator, $closed]))->assertSessionHasErrors('limit');
        $this->assertDatabaseCount('user_picks', 0);
    }

    public function test_voting_does_not_consume_request_creation_capacity(): void
    {
        $creator = Creator::factory()->create();
        $request = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'approved']);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('recommendations.vote', [$creator, $request]));

        $this->assertSame(3, $user->fresh()->suggestionsRemainingFor($creator));
    }

    public function test_public_vote_control_is_binary_and_has_no_allocation_copy(): void
    {
        $creator = Creator::factory()->create();
        $request = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'approved']);
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('requests.card-details', $request))
            ->assertOk()
            ->assertSee('aria-label="Add vote to this request"', false)
            ->assertSee('aria-pressed="false"', false)
            ->assertDontSee('vote_action', false)
            ->assertDontSee('votes remaining');

        $this->post(route('recommendations.vote', [$creator, $request]));

        $this->get(route('requests.card-details', $request))
            ->assertOk()
            ->assertSee('You voted')
            ->assertSee('aria-label="Remove vote from this request"', false)
            ->assertSee('aria-pressed="true"', false)
            ->assertSee('name="_method" value="DELETE"', false)
            ->assertDontSee('Add another vote');
    }
}
