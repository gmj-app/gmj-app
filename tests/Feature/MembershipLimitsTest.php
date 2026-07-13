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

    public function test_free_users_can_suggest_three_times_for_each_of_three_reactors(): void
    {
        $user = User::factory()->create();
        $creators = Creator::factory()->count(4)->create();

        foreach ($creators->take(3) as $creator) {
            for ($suggestion = 1; $suggestion <= 3; $suggestion++) {
                $this->actingAs($user)
                    ->post(route('recommendations.store', $creator), $this->recommendationData(
                        "{$creator->display_name} suggestion {$suggestion}",
                    ))
                    ->assertRedirect(route('creator.queue', $creator));
            }

            $this->actingAs($user)
                ->post(route('recommendations.store', $creator), $this->recommendationData('Too many'))
                ->assertSessionHasErrors('limit');
        }

        $fourthCreator = $creators->last();

        $this->actingAs($user)
            ->post(route('recommendations.store', $fourthCreator), $this->recommendationData('Fourth reactor'))
            ->assertSessionHasErrors('limit');

        $this->assertSame(9, $user->recommendationsSubmitted()->count());
        $this->assertSame(3, $user->reactorsUsed());
    }

    public function test_plus_and_pro_users_receive_their_configured_allowances(): void
    {
        $plus = User::factory()->create(['plan_slug' => 'plus']);
        $pro = User::factory()->create(['plan_slug' => 'pro']);

        $this->assertSame([
            'label' => 'Plus',
            'reactors' => 5,
            'suggestions_per_reactor' => 5,
            'votes_per_reactor' => 5,
        ], $plus->membershipLimits());

        $this->assertSame([
            'label' => 'Pro',
            'reactors' => 10,
            'suggestions_per_reactor' => 10,
            'votes_per_reactor' => 10,
        ], $pro->membershipLimits());
    }

    public function test_plan_entitlements_return_expected_limits_and_fallbacks(): void
    {
        $plans = app(PlanEntitlementService::class);

        $this->assertSame([
            'plan' => 'free',
            'label' => 'Free',
            'creator_favorites_limit' => 3,
            'suggestions_per_creator_limit' => 3,
            'upvotes_per_creator_limit' => 3,
        ], $plans->getLimitsForUser(User::factory()->make(['plan_slug' => 'free'])));

        $this->assertSame(5, $plans->getCreatorFavoritesLimit(User::factory()->make(['plan_slug' => 'plus'])));
        $this->assertSame(5, $plans->getSuggestionsPerCreatorLimit(User::factory()->make(['plan_slug' => 'plus'])));
        $this->assertSame(5, $plans->getUpvotesPerCreatorLimit(User::factory()->make(['plan_slug' => 'plus'])));

        $this->assertSame(10, $plans->getCreatorFavoritesLimit(User::factory()->make(['plan_slug' => 'pro'])));
        $this->assertSame(10, $plans->getSuggestionsPerCreatorLimit(User::factory()->make(['plan_slug' => 'pro'])));
        $this->assertSame(10, $plans->getUpvotesPerCreatorLimit(User::factory()->make(['plan_slug' => 'pro'])));

        $this->assertSame('free', $plans->getUserPlan(User::factory()->make(['plan_slug' => null])));
        $this->assertSame('free', $plans->getUserPlan(User::factory()->make(['plan_slug' => 'enterprise'])));
    }

    public function test_free_users_have_three_votes_per_reactor_and_can_remove_a_vote(): void
    {
        $creator = Creator::factory()->create();
        $recommendations = Recommendation::factory()
            ->count(4)
            ->create([
                'creator_id' => $creator->id,
                'status' => 'approved',
            ]);
        $user = User::factory()->create();

        foreach (range(1, 3) as $vote) {
            $this->actingAs($user)
                ->post(route('recommendations.vote', [$creator, $recommendations->first()]))
                ->assertRedirect(
                    route('creator.queue', $creator).'#recommendation-'.$recommendations->first()->id,
                );
        }

        $this->actingAs($user)
            ->post(route('recommendations.vote', [$creator, $recommendations->last()]))
            ->assertSessionHasErrors([
                'limit' => "You've used all your votes for this creator. You'll get them back when supported requests are published or closed.",
            ]);

        $this->actingAs($user)
            ->post(route('recommendations.vote', [$creator, $recommendations->first()]), [
                'vote_action' => 'remove',
            ])
            ->assertSessionMissing('success')
            ->assertSessionHas('recommendation_action', [
                'recommendation_id' => $recommendations->first()->id,
                'message' => 'Your vote was removed.',
                'type' => 'removed',
            ]);

        $this->actingAs($user)
            ->post(route('recommendations.vote', [$creator, $recommendations->last()]))
            ->assertSessionMissing('success')
            ->assertSessionHas('recommendation_action', [
                'recommendation_id' => $recommendations->last()->id,
                'message' => 'Your vote was added.',
                'type' => 'added',
            ]);

        $this->assertSame(2, $user->userPicks()->count());
        $this->assertDatabaseHas('user_picks', [
            'user_id' => $user->id,
            'recommendation_id' => $recommendations->first()->id,
            'vote_count' => 2,
        ]);
        $this->assertSame(3, $user->fresh()->votesUsedFor($creator));
    }

    public function test_votes_on_published_recommendations_return_to_available_capacity(): void
    {
        $creator = Creator::factory()->create();
        $user = User::factory()->create();
        $publishedRecommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);
        $newRecommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);

        foreach (range(1, 3) as $vote) {
            $this->actingAs($user)
                ->post(route('recommendations.vote', [$creator, $publishedRecommendation]))
                ->assertSessionHas('recommendation_action', [
                    'recommendation_id' => $publishedRecommendation->id,
                    'message' => 'Your vote was added.',
                    'type' => 'added',
                ]);
        }

        $this->assertSame(3, $user->fresh()->votesUsedFor($creator));
        $this->assertSame(0, $user->fresh()->votesRemainingFor($creator));
        $this->assertSame(3, $publishedRecommendation->fresh()->totalVotes());

        $publishedRecommendation->update(['status' => 'published']);

        $this->assertFalse($publishedRecommendation->fresh()->isVotable());
        $this->assertSame(0, $user->fresh()->votesUsedFor($creator));
        $this->assertSame(3, $user->fresh()->votesRemainingFor($creator));
        $this->assertSame(3, $publishedRecommendation->fresh()->totalVotes());
        $this->assertSame(1, $publishedRecommendation->fresh()->userPicks()->count());

        $this->actingAs($user)
            ->post(route('recommendations.vote', [$creator, $newRecommendation]))
            ->assertSessionHas('recommendation_action', [
                'recommendation_id' => $newRecommendation->id,
                'message' => 'Your vote was added.',
                'type' => 'added',
            ]);

        $this->assertSame(1, $user->fresh()->votesUsedFor($creator));
        $this->assertSame(2, $user->fresh()->votesRemainingFor($creator));
    }

    public function test_only_votable_status_votes_count_against_vote_limit(): void
    {
        $creator = Creator::factory()->create();
        $user = User::factory()->create();

        foreach ([
            'pending' => 1,
            'approved' => 2,
            'coming_soon' => 3,
            'scheduled' => 4,
            'recorded' => 5,
            'published' => 6,
            'already_seen' => 7,
            'passed' => 8,
            'hidden' => 9,
            'withdrawn' => 10,
        ] as $status => $voteCount) {
            $recommendation = Recommendation::factory()->create([
                'creator_id' => $creator->id,
                'status' => $status,
            ]);

            $user->userPicks()->create([
                'creator_id' => $creator->id,
                'recommendation_id' => $recommendation->id,
                'vote_count' => $voteCount,
            ]);
        }

        $this->assertSame(['pending', 'approved'], Recommendation::votableStatuses());
        $this->assertSame(3, $user->fresh()->votesUsedFor($creator));
        $this->assertSame(0, $user->fresh()->votesRemainingFor($creator));
    }

    public function test_submission_uses_a_suggestion_slot_without_consuming_an_upvote(): void
    {
        $creator = Creator::factory()->create([
            'recommendation_approval_mode' => 'auto',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('recommendations.store', $creator), $this->recommendationData('Independent resources'))
            ->assertRedirect(route('creator.queue', $creator));

        $recommendation = Recommendation::query()
            ->where('title', 'Independent resources')
            ->firstOrFail();

        $this->assertSame(1, $user->fresh()->suggestionsUsedFor($creator));
        $this->assertSame(2, $user->fresh()->suggestionsRemainingFor($creator));
        $this->assertSame(0, $user->fresh()->votesUsedFor($creator));
        $this->assertSame(3, $user->fresh()->votesRemainingFor($creator));
        $this->assertSame(0, $recommendation->userPicks()->count());
        $this->assertDatabaseCount('user_picks', 0);

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSeeInOrder(['Requests', '1 / 3 used', '2 remaining'])
            ->assertSeeInOrder(['Votes', '0 / 3 used', '3 remaining'])
            ->assertSee('Independent resources')
            ->assertSee('name="vote_action" value="add"', false)
            ->assertSee('aria-label="Remove vote from this request"', false)
            ->assertSee('0/3');
    }

    public function test_user_can_explicitly_upvote_after_submitting_without_changing_suggestion_usage(): void
    {
        $creator = Creator::factory()->create([
            'recommendation_approval_mode' => 'auto',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('recommendations.store', $creator), $this->recommendationData('Upvote after submit'))
            ->assertRedirect(route('creator.queue', $creator));

        $recommendation = Recommendation::query()
            ->where('title', 'Upvote after submit')
            ->firstOrFail();

        $this->post(route('recommendations.vote', [$creator, $recommendation]), [
            'vote_action' => 'add',
        ])->assertSessionHas('recommendation_action', [
            'recommendation_id' => $recommendation->id,
            'message' => 'Your vote was added.',
            'type' => 'added',
        ]);

        $this->assertSame(1, $user->fresh()->suggestionsUsedFor($creator));
        $this->assertSame(2, $user->fresh()->suggestionsRemainingFor($creator));
        $this->assertSame(1, $user->fresh()->votesUsedFor($creator));
        $this->assertSame(2, $user->fresh()->votesRemainingFor($creator));
        $this->assertSame(1, $recommendation->fresh()->userPicks()->count());

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSeeInOrder(['Requests', '1 / 3 used', '2 remaining'])
            ->assertSeeInOrder(['Votes', '1 / 3 used', '2 remaining'])
            ->assertSee('name="vote_action" value="remove"', false)
            ->assertSee('aria-label="Remove vote from this request"', false)
            ->assertSee('1/3');
    }

    public function test_removing_an_upvote_after_submitting_returns_only_the_upvote_slot(): void
    {
        $creator = Creator::factory()->create([
            'recommendation_approval_mode' => 'auto',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('recommendations.store', $creator), $this->recommendationData('Remove explicit upvote'));

        $recommendation = Recommendation::query()
            ->where('title', 'Remove explicit upvote')
            ->firstOrFail();

        $this->post(route('recommendations.vote', [$creator, $recommendation]), [
            'vote_action' => 'add',
        ]);

        $this->post(route('recommendations.vote', [$creator, $recommendation]), [
            'vote_action' => 'remove',
        ])->assertSessionHas('recommendation_action', [
            'recommendation_id' => $recommendation->id,
            'message' => 'Your vote was removed.',
            'type' => 'removed',
        ]);

        $this->assertSame(1, $user->fresh()->suggestionsUsedFor($creator));
        $this->assertSame(2, $user->fresh()->suggestionsRemainingFor($creator));
        $this->assertSame(0, $user->fresh()->votesUsedFor($creator));
        $this->assertSame(3, $user->fresh()->votesRemainingFor($creator));
        $this->assertSame(0, $recommendation->fresh()->userPicks()->count());
    }

    public function test_exhausted_upvotes_do_not_block_suggestion_submission_when_suggestions_remain(): void
    {
        $creator = Creator::factory()->create([
            'recommendation_approval_mode' => 'auto',
        ]);
        $user = User::factory()->create();
        $recommendations = Recommendation::factory()
            ->count(3)
            ->create([
                'creator_id' => $creator->id,
                'status' => 'approved',
            ]);

        CreatorFavorite::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);

        foreach ($recommendations as $recommendation) {
            $user->userPicks()->create([
                'creator_id' => $creator->id,
                'recommendation_id' => $recommendation->id,
            ]);
        }

        $this->assertSame(0, $user->fresh()->votesRemainingFor($creator));
        $this->assertSame(3, $user->fresh()->suggestionsRemainingFor($creator));

        $this->actingAs($user)
            ->post(route('recommendations.store', $creator), $this->recommendationData('Submitted with no upvotes left'))
            ->assertRedirect(route('creator.queue', $creator));

        $recommendation = Recommendation::query()
            ->where('title', 'Submitted with no upvotes left')
            ->firstOrFail();

        $this->assertSame(2, $user->fresh()->suggestionsRemainingFor($creator));
        $this->assertSame(0, $user->fresh()->votesRemainingFor($creator));
        $this->assertSame(0, $recommendation->userPicks()->count());
    }

    public function test_exhausted_suggestions_do_not_block_upvoting_when_upvotes_remain(): void
    {
        $creator = Creator::factory()->create();
        $user = User::factory()->create();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);

        CreatorFavorite::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);

        Recommendation::factory()
            ->count(3)
            ->create([
                'creator_id' => $creator->id,
                'submitted_by' => $user->id,
                'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            ]);

        $this->assertSame(0, $user->fresh()->suggestionsRemainingFor($creator));
        $this->assertSame(3, $user->fresh()->votesRemainingFor($creator));

        $this->actingAs($user)
            ->post(route('recommendations.store', $creator), $this->recommendationData('Blocked by suggestions'))
            ->assertSessionHasErrors([
                'limit' => "You've used all your requests for this creator.",
            ]);

        $this->assertDatabaseMissing('recommendations', [
            'title' => 'Blocked by suggestions',
        ]);

        $this->post(route('recommendations.vote', [$creator, $recommendation]), [
            'vote_action' => 'add',
        ])->assertSessionHas('recommendation_action', [
            'recommendation_id' => $recommendation->id,
            'message' => 'Your vote was added.',
            'type' => 'added',
        ]);

        $this->assertSame(0, $user->fresh()->suggestionsRemainingFor($creator));
        $this->assertSame(2, $user->fresh()->votesRemainingFor($creator));
        $this->assertSame(1, $recommendation->fresh()->userPicks()->count());
    }

    public function test_queue_shows_creator_specific_guide_activity_without_global_plan_card(): void
    {
        $creator = Creator::factory()->create(['display_name' => 'JFragment']);
        $user = User::factory()->create([
            'name' => 'Example Member',
            'public_display_name' => 'Example Member',
            'public_handle' => 'examplemember',
            'plan_slug' => 'plus',
        ]);

        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $user->id,
            'status' => 'approved',
        ]);

        $this->actingAs($user)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Your activity with JFragment')
            ->assertSee('Not favorited')
            ->assertSeeInOrder(['Requests', '1 / 5 used', '4 remaining'])
            ->assertSeeInOrder(['Votes', '0 / 5 used', '5 remaining'])
            ->assertDontSee('Your limits')
            ->assertDontSee('Creator favorites remaining')
            ->assertDontSee('Plus')
            ->assertSeeInOrder([
                'Your activity with JFragment',
                'Filter requests',
            ]);
    }

    public function test_vote_automatically_favorites_creator_and_casts_vote_without_confirmation(): void
    {
        $creator = Creator::factory()->create();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post(route('recommendations.vote', [$creator, $recommendation]))
            ->assertRedirect(
                route('creator.queue', $creator)."#recommendation-{$recommendation->id}",
            )
            ->assertSessionMissing('success')
            ->assertSessionHas('recommendation_action', [
                'recommendation_id' => $recommendation->id,
                'message' => 'Your vote was added.',
                'type' => 'added',
            ]);

        $this->assertDatabaseHas('creator_favorites', [
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('user_picks', [
            'creator_id' => $creator->id,
            'recommendation_id' => $recommendation->id,
            'user_id' => $user->id,
        ]);

        $this->post(route('recommendations.vote', [$creator, $recommendation]), [
            'vote_action' => 'add',
        ])->assertSessionHas('recommendation_action', [
            'recommendation_id' => $recommendation->id,
            'message' => 'Your vote was added.',
            'type' => 'added',
        ]);

        $this->assertDatabaseCount('creator_favorites', 1);
        $this->assertDatabaseCount('user_picks', 1);

        $response = $this->get(route('creator.queue', $creator));

        $response
            ->assertOk()
            ->assertDontSee('1 user has favorited this creator.')
            ->assertSee('Followers')
            ->assertSee('aria-label="Remove vote from this request"', false)
            ->assertSee('name="vote_action" value="remove"', false)
            ->assertDontSee('data-global-success-alert', false)
            ->assertSee('data-recommendation-action-feedback', false)
            ->assertSee('Your vote was added.')
            ->assertSee('Favorited')
            ->assertSeeInOrder(['Requests', '0 / 3 used', '3 remaining'])
            ->assertSeeInOrder(['Votes', '2 / 3 used', '1 remaining']);

        $this->assertSame(
            1,
            substr_count($response->getContent(), 'data-recommendation-action-feedback'),
        );
    }

    public function test_public_queue_submits_upvotes_directly_without_confirmation_ui(): void
    {
        $creator = Creator::factory()->create();
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('name="vote_action" value="add"', false)
            ->assertSee('type="submit"', false)
            ->assertDontSee('Continue and upvote')
            ->assertDontSee('Upvoting on this journey will add this creator')
            ->assertDontSee('Favorite limit reached')
            ->assertDontSee('confirm(', false)
            ->assertDontSee('alert(', false);

        foreach (Creator::factory()->count(3)->create() as $favoritedCreator) {
            CreatorFavorite::query()->create([
                'creator_id' => $favoritedCreator->id,
                'user_id' => $user->id,
            ]);
        }

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('name="vote_action" value="add"', false)
            ->assertSee('Add vote to this request')
            ->assertDontSee('Continue and upvote')
            ->assertDontSee('Upvoting on this journey will add this creator');
    }

    public function test_suggestion_requires_confirmation_then_favorites_creator_and_submits(): void
    {
        $creator = Creator::factory()->create();
        $user = User::factory()->create();
        $payload = [
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'title' => 'Participation confirmation',
        ];

        $this->actingAs($user)
            ->post(route('recommendations.store', $creator), $payload)
            ->assertSessionHasErrors([
                'favorite_confirmation' => 'Submitting to this journey will add this creator to your favorites. You’ll use 1 of your creator favorite slots.',
            ]);

        $this->assertDatabaseCount('creator_favorites', 0);
        $this->assertDatabaseCount('recommendations', 0);

        $this->post(route('recommendations.store', $creator), [
            ...$payload,
            'confirm_favorite' => true,
        ])->assertSessionHas('success', 'Request submitted and added to the journey.');

        $this->assertDatabaseHas('creator_favorites', [
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('recommendations', [
            'creator_id' => $creator->id,
            'submitted_by' => $user->id,
            'title' => 'Participation confirmation',
            'status' => 'approved',
        ]);
    }

    public function test_submission_form_posts_directly_with_visible_favorite_and_limit_states(): void
    {
        $creator = Creator::factory()->create();
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('recommendations.create', $creator))
            ->assertOk()
            ->assertSee("Submitting to this journey will add {$creator->display_name} to your favorites and use 1 creator favorite slot.")
            ->assertSee('Creator favorites: 0 of 3 used')
            ->assertSee('name="confirm_favorite"', false)
            ->assertSee('value="1"', false)
            ->assertDontSee('request-participation-confirmation', false)
            ->assertDontSee('confirm(', false)
            ->assertDontSee('alert(', false);

        foreach (Creator::factory()->count(3)->create() as $favoritedCreator) {
            CreatorFavorite::query()->create([
                'creator_id' => $favoritedCreator->id,
                'user_id' => $user->id,
            ]);
        }

        $this->get(route('recommendations.create', $creator))
            ->assertOk()
            ->assertSee('Favorite limit reached')
            ->assertSee('name="confirm_favorite"', false)
            ->assertSee('value="0"', false)
            ->assertSee('Creator favorites: 3 of 3 used')
            ->assertSee('Favorite limit reached')
            ->assertDontSee('Continue and submit')
            ->assertDontSee('request-participation-confirmation', false);
    }

    public function test_favorite_limit_blocks_manual_favorite_vote_and_suggestion_for_new_creator(): void
    {
        $user = User::factory()->create();
        $favoritedCreators = Creator::factory()->count(3)->create();
        $newCreator = Creator::factory()->create();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $newCreator->id,
            'status' => 'approved',
        ]);

        foreach ($favoritedCreators as $creator) {
            CreatorFavorite::query()->create([
                'creator_id' => $creator->id,
                'user_id' => $user->id,
            ]);
        }

        $this->actingAs($user)
            ->post(route('creator.favorite', $newCreator))
            ->assertSessionHasErrors([
                'limit' => 'You’ve reached your creator favorite limit. Remove a favorite before adding another.',
            ]);

        $this->post(route('recommendations.vote', [$newCreator, $recommendation]))
            ->assertSessionHasErrors([
                'limit' => 'You’ve reached your creator favorite limit. Remove a favorite before upvoting on this journey.',
            ]);

        $this->post(route('recommendations.store', $newCreator), [
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'title' => 'Blocked suggestion',
            'confirm_favorite' => true,
        ])->assertSessionHasErrors([
            'limit' => 'You’ve reached your creator favorite limit. Remove a favorite before suggesting something for this journey.',
        ]);

        $this->assertDatabaseCount('creator_favorites', 3);
        $this->assertDatabaseCount('user_picks', 0);
        $this->assertDatabaseMissing('recommendations', [
            'title' => 'Blocked suggestion',
        ]);
    }

    public function test_upvote_limit_rolls_back_an_automatic_favorite(): void
    {
        $creator = Creator::factory()->create();
        $recommendations = Recommendation::factory()
            ->count(4)
            ->create([
                'creator_id' => $creator->id,
                'status' => 'approved',
            ]);
        $user = User::factory()->create();

        foreach ($recommendations->take(3) as $recommendation) {
            $user->userPicks()->create([
                'creator_id' => $creator->id,
                'recommendation_id' => $recommendation->id,
            ]);
        }

        $this->actingAs($user)
            ->post(route('recommendations.vote', [$creator, $recommendations->last()]))
            ->assertSessionHasErrors([
                'limit' => "You've used all your votes for this creator. You'll get them back when supported requests are published or closed.",
            ]);

        $this->assertDatabaseMissing('creator_favorites', [
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('user_picks', [
            'recommendation_id' => $recommendations->last()->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_upvote_capacity_returns_when_recommendation_leaves_active_pool(): void
    {
        $creator = Creator::factory()->create();
        $user = User::factory()->create();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);

        CreatorFavorite::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);

        $this->actingAs($user)
            ->post(route('recommendations.vote', [$creator, $recommendation]))
            ->assertSessionHas('recommendation_action', [
                'recommendation_id' => $recommendation->id,
                'message' => 'Your vote was added.',
                'type' => 'added',
            ]);

        $this->assertSame(2, $user->votesRemainingFor($creator));

        $recommendation->update(['status' => 'coming_soon']);

        $this->assertSame(3, $user->votesRemainingFor($creator));
    }

    /**
     * @return array<string, string>
     */
    private function recommendationData(string $title): array
    {
        $videoId = substr(md5($title), 0, 11);

        return [
            'youtube_url' => "https://www.youtube.com/watch?v={$videoId}",
            'title' => $title,
            'confirm_favorite' => '1',
        ];
    }
}
