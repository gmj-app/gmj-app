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

    public function test_dashboard_shows_a_compact_activity_summary_with_centralized_counts(): void
    {
        $user = User::factory()->create(['membership_tier' => 'free']);
        $favoriteCreator = Creator::factory()->create([
            'display_name' => 'Favorite Journey',
            'status' => 'active',
        ]);
        CreatorFavorite::query()->create([
            'creator_id' => $favoriteCreator->id,
            'user_id' => $user->id,
        ]);

        Recommendation::factory()->create([
            'creator_id' => $favoriteCreator->id,
            'submitted_by' => $user->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'title' => 'Active suggestion detail',
        ]);
        Recommendation::factory()->create([
            'creator_id' => $favoriteCreator->id,
            'submitted_by' => $user->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'title' => 'Published suggestion detail',
            'status' => 'published',
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
            ->assertSeeInOrder(['Creator favorites', '1', '/ 3'])
            ->assertSeeInOrder(['Active votes', '2'])
            ->assertSeeInOrder(['Suggestions submitted', '2'])
            ->assertSee('My Activity')
            ->assertSee('Your votes and suggestions')
            ->assertSee('2 active votes')
            ->assertSee('2 suggestions')
            ->assertSee('1 published')
            ->assertSee('View My Activity')
            ->assertSee('href="'.route('activity.index').'"', false)
            ->assertDontSee('Active suggestion detail')
            ->assertDontSee('Published suggestion detail')
            ->assertDontSee('Your active votes');
    }

    public function test_dashboard_activity_card_has_a_useful_zero_state(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Your votes and suggestions')
            ->assertSee('No activity yet')
            ->assertSee('Favorite a creator, submit a suggestion, or cast a vote to start building your activity history.')
            ->assertSee('Find creators')
            ->assertSee('href="'.route('home').'"', false);
    }

    public function test_dashboard_does_not_load_detailed_creator_activity_collections(): void
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

        $this->assertLessThanOrEqual(4, $activityQueries->count());
        $this->assertFalse($activityQueries->contains(fn (string $query): bool => str_contains($query, 'select * from "creators"')));
    }
}
