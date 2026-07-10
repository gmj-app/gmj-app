<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\CreatorOwner;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_role_hub_for_a_guide_without_creator_pages(): void
    {
        $user = User::factory()->create([
            'name' => 'Guide User',
            'public_display_name' => 'Guide User',
            'public_handle' => 'guideuser',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Welcome back, Guide User')
            ->assertSee('Fans suggest. Communities vote. Creators decide.')
            ->assertSee('Use your resources to favorite creators, submit suggestions, and vote for ideas.')
            ->assertSee('My Hub')
            ->assertSee("I'm a Creator", false)
            ->assertSee('Create your creator page so fans can suggest, vote, and help guide what you make next.')
            ->assertSee('Set up creator page')
            ->assertSee('href="'.route('creators.create').'"', false)
            ->assertSee("I'm a Guide", false)
            ->assertSee('Favorite creators, suggest ideas or links, and vote for what you want to see next.')
            ->assertSee('Explore creators')
            ->assertSee('Creator favorites')
            ->assertSee('Active votes')
            ->assertSee('Suggestions submitted')
            ->assertDontSee('Your resources')
            ->assertDontSee('Link Creator Account')
            ->assertDontSee('Creator setup coming soon')
            ->assertDontSee("You're logged in!");
    }

    public function test_dashboard_uses_the_canonical_creator_page_content_width(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk();

        $response->assertSee('class="px-4 sm:px-6 lg:px-8"', false)
            ->assertSee('class="mx-auto min-w-0 max-w-5xl"', false)
            ->assertDontSee('mx-auto max-w-7xl px-4 sm:px-6 lg:px-8', false);

        $this->assertGreaterThanOrEqual(2, substr_count(
            $response->getContent(),
            'mx-auto min-w-0 max-w-5xl',
        ));
    }

    public function test_dashboard_links_a_single_owned_creator_directly_to_its_dashboard(): void
    {
        $user = User::factory()->create();
        $creator = Creator::factory()->create(['display_name' => 'Owned Creator']);
        CreatorOwner::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee("I'm a Creator", false)
            ->assertSee('Manage creator page')
            ->assertSee('href="'.route('creators.dashboard', $creator).'"', false)
            ->assertDontSee('Creator setup coming soon');
    }

    public function test_dashboard_links_multiple_owned_creators_to_the_creator_page_selector(): void
    {
        $user = User::factory()->create();

        Creator::factory()
            ->count(2)
            ->create()
            ->each(fn (Creator $creator) => CreatorOwner::query()->create([
                'creator_id' => $creator->id,
                'user_id' => $user->id,
                'role' => 'owner',
            ]));

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Manage creator pages')
            ->assertSee('href="'.route('creators.index').'"', false);
    }

    public function test_dashboard_shows_resource_counts_and_active_favorite_creators(): void
    {
        $user = User::factory()->create(['membership_tier' => 'free']);
        $favoriteCreator = Creator::factory()->create([
            'display_name' => 'Favorite Journey',
            'status' => 'active',
        ]);
        $inactiveCreator = Creator::factory()->create([
            'display_name' => 'Inactive Favorite',
            'status' => 'inactive',
        ]);

        foreach ([$favoriteCreator, $inactiveCreator] as $creator) {
            CreatorFavorite::query()->create([
                'creator_id' => $creator->id,
                'user_id' => $user->id,
            ]);
        }

        Recommendation::factory()->count(2)->create([
            'creator_id' => $favoriteCreator->id,
            'submitted_by' => $user->id,
        ]);
        $activeRecommendation = Recommendation::factory()->create([
            'creator_id' => $favoriteCreator->id,
            'status' => 'approved',
        ]);
        $closedRecommendation = Recommendation::factory()->create([
            'creator_id' => $favoriteCreator->id,
            'status' => 'published',
        ]);

        foreach ([$activeRecommendation, $closedRecommendation] as $recommendation) {
            UserPick::factory()->create([
                'user_id' => $user->id,
                'creator_id' => $favoriteCreator->id,
                'recommendation_id' => $recommendation->id,
                'vote_count' => $recommendation->is($activeRecommendation) ? 2 : 5,
            ]);
        }

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Creator favorites', '2', '/ 3'])
            ->assertSeeInOrder(['Active votes', '2'])
            ->assertSeeInOrder(['Suggestions submitted', '2'])
            ->assertSee('My Guide Activity')
            ->assertSee('Your creators and activity')
            ->assertSee('Favorite Journey')
            ->assertSee('2 active votes')
            ->assertSee('2 suggestions')
            ->assertSee('Your votes')
            ->assertSee('Your suggestions')
            ->assertSee('href="'.route('creator.queue', $favoriteCreator).'"', false)
            ->assertDontSee('Inactive Favorite');
    }

    public function test_dashboard_activity_groups_active_votes_and_suggestions_by_favorite_creator(): void
    {
        $user = User::factory()->create();
        $firstCreator = Creator::factory()->create(['display_name' => 'First Favorite', 'status' => 'active']);
        $secondCreator = Creator::factory()->create(['display_name' => 'Second Favorite', 'status' => 'active']);
        $unrelatedCreator = Creator::factory()->create(['display_name' => 'Not Favorited', 'status' => 'active']);

        foreach ([$firstCreator, $secondCreator] as $creator) {
            CreatorFavorite::query()->create(['creator_id' => $creator->id, 'user_id' => $user->id]);
        }

        $active = Recommendation::factory()->create([
            'creator_id' => $firstCreator->id,
            'title' => 'Active allocation',
            'status' => 'approved',
        ]);
        $publishedVote = Recommendation::factory()->create([
            'creator_id' => $firstCreator->id,
            'title' => 'Historical allocation',
            'status' => 'published',
        ]);
        UserPick::factory()->create([
            'user_id' => $user->id,
            'creator_id' => $firstCreator->id,
            'recommendation_id' => $active->id,
            'vote_count' => 2,
        ]);
        UserPick::factory()->create([
            'user_id' => $user->id,
            'creator_id' => $firstCreator->id,
            'recommendation_id' => $publishedVote->id,
            'vote_count' => 5,
        ]);

        $activeSuggestion = Recommendation::factory()->create([
            'creator_id' => $firstCreator->id,
            'submitted_by' => $user->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'title' => 'My active suggestion',
            'status' => 'approved',
        ]);
        $publishedSuggestion = Recommendation::factory()->create([
            'creator_id' => $firstCreator->id,
            'submitted_by' => $user->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'title' => 'My published suggestion',
            'status' => 'published',
        ]);
        Recommendation::factory()->create([
            'creator_id' => $unrelatedCreator->id,
            'submitted_by' => $user->id,
            'title' => 'Suggestion outside favorites',
            'status' => 'approved',
        ]);

        $response = $this->actingAs($user)->get(route('dashboard'));

        $response->assertOk()
            ->assertSeeInOrder(['First Favorite', '2 active votes', '2 suggestions'])
            ->assertSee('Active allocation')
            ->assertSee('2 votes')
            ->assertDontSee('Historical allocation')
            ->assertSee('My active suggestion')
            ->assertSee('My published suggestion')
            ->assertSee('Approved')
            ->assertSee('Published')
            ->assertSee(route('creator.queue', $firstCreator).'#recommendation-'.$activeSuggestion->id, false)
            ->assertSee(route('creators.published', $firstCreator).'#recommendation-'.$publishedSuggestion->id, false)
            ->assertSee('No active votes with this creator.')
            ->assertSee('No suggestions submitted to this creator.')
            ->assertDontSee('Not Favorited')
            ->assertDontSee('Suggestion outside favorites');
    }

    public function test_dashboard_activity_shows_a_useful_empty_state_without_favorites(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('No favorite creators yet.')
            ->assertSee('Favorite a creator to track your suggestions and votes here.')
            ->assertSee('Find creators')
            ->assertSee('href="'.route('home').'"', false);
    }

    public function test_dashboard_activity_query_count_does_not_scale_per_favorite_creator(): void
    {
        $user = User::factory()->create();

        Creator::factory()->count(3)->create()->each(function (Creator $creator) use ($user): void {
            CreatorFavorite::query()->create(['creator_id' => $creator->id, 'user_id' => $user->id]);
            $recommendation = Recommendation::factory()->create([
                'creator_id' => $creator->id,
                'submitted_by' => $user->id,
                'status' => 'approved',
            ]);
            UserPick::factory()->create([
                'user_id' => $user->id,
                'creator_id' => $creator->id,
                'recommendation_id' => $recommendation->id,
            ]);
        });

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($user)->get(route('dashboard'))->assertOk();

        $activityQueries = collect(DB::getQueryLog())
            ->pluck('query')
            ->filter(fn (string $query): bool => str_contains($query, 'creator_favorites')
                || str_contains($query, 'user_picks')
                || str_contains($query, 'recommendations'));

        $this->assertLessThanOrEqual(8, $activityQueries->count());
    }
}
