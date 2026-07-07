<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\CreatorOwner;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSee('My favorite creators')
            ->assertSee('Favorite Journey')
            ->assertSee('href="'.route('creator.queue', $favoriteCreator).'"', false)
            ->assertDontSee('Inactive Favorite');
    }
}
