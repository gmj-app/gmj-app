<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserAccolade;
use App\Models\UserPick;
use App\Services\Accolades\AccoladeDefinitionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PublicGuideProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_accolades_are_compact_and_show_manual_feature_plus_highest_per_track(): void
    {
        $guide = User::factory()->create([
            'public_display_name' => 'Accolade Guide',
            'public_handle' => 'accolade-guide',
            'public_profile_enabled' => true,
            'guide_number' => 1,
        ]);
        $trailblazer = $this->award($guide, 'guide.published_requests.trailblazer', featured: true);
        $this->award($guide, 'guide.published_requests.scout');
        $this->award($guide, 'guide.published_requests.tracker');
        $this->award($guide, 'guide.creator_exploration.explorer');

        $response = $this->get(route('guides.show', ['handle' => $guide->public_handle]))->assertOk();

        $response->assertSeeInOrder(['Featured accolade', 'Trailblazer'])
            ->assertSee('Founding Guide #1')
            ->assertSee('lg:grid-cols-[minmax(0,1.4fr)_minmax(280px,0.6fr)]', false)
            ->assertSee('View all accolades')
            ->assertSee('Tracker')
            ->assertSee('Explorer')
            ->assertSee('Scout') // retained in the modal history
            ->assertSee('Earned accolades')
            ->assertDontSee('Track complete')
            ->assertDontSee('toward Scout')
            ->assertDontSee('role="progressbar"', false)
            ->assertDontSee('profile/accolades/featured');

        $this->assertSame($trailblazer->id, $guide->fresh()->earnedAccolades()->where('is_featured', true)->first()->id);
        $this->assertSame(2, substr_count($response->getContent(), '>Tracker<')); // compact tile and modal history
        $this->assertSame(1, substr_count($response->getContent(), '>Scout<')); // modal history only
    }

    public function test_public_accolades_fallback_visibility_and_empty_state_are_safe(): void
    {
        $guide = User::factory()->create([
            'public_display_name' => 'Safe Guide',
            'public_handle' => 'safe-guide',
            'public_profile_enabled' => true,
            'guide_number' => 700,
        ]);
        $this->award($guide, 'guide.published_requests.trailblazer');
        $this->award($guide, 'guide.published_requests.scout');
        $this->award($guide, 'guide.influence.first_footprint', public: false);
        $this->award($guide, 'creator.community_publications.first_step', subjectType: 'creator');

        $this->get(route('guides.show', ['handle' => $guide->public_handle]))->assertOk()
            ->assertSeeInOrder(['Featured accolade', 'Scout'])
            ->assertDontSee('First Footprint')
            ->assertDontSee('First Step');

        $empty = User::factory()->create([
            'public_display_name' => 'New Guide',
            'public_handle' => 'new-guide',
            'public_profile_enabled' => true,
            'guide_number' => 701,
        ]);
        $this->get(route('guides.show', ['handle' => $empty->public_handle]))->assertOk()
            ->assertSee('No accolades earned yet.')
            ->assertDontSee('View all accolades');
    }

    public function test_public_profile_shows_safe_identity_impact_and_public_histories(): void
    {
        $guide = User::factory()->create([
            'name' => 'Private Google Name',
            'email' => 'private-guide@example.com',
            'public_display_name' => 'Public Guide',
            'public_handle' => 'public-guide',
            'guide_number' => 59,
            'public_profile_enabled' => true,
            'created_at' => '2026-07-01',
        ]);
        $otherGuide = User::factory()->create();
        $creator = Creator::factory()->create(['slug' => 'profile-creator', 'display_name' => 'Profile Creator']);

        $published = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $guide->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'title' => 'Original published suggestion',
            'published_title' => 'Published Guide Impact',
            'published_thumbnail_url' => 'https://i.ytimg.com/vi/example/hqdefault.jpg',
            'status' => 'published',
            'published_at' => '2026-07-09',
        ]);
        $activeSuggestion = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $guide->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'title' => 'Public active suggestion',
            'status' => 'approved',
        ]);
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $guide->id,
            'title' => 'Hidden moderation item',
            'status' => 'hidden',
        ]);
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $guide->id,
            'title' => 'Withdrawn private history',
            'status' => 'withdrawn',
        ]);

        $finishedSupport = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $otherGuide->id,
            'title' => 'Finished supported recommendation',
            'status' => 'passed',
        ]);
        $activeSupport = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $otherGuide->id,
            'title' => 'Secret active allocation',
            'status' => 'approved',
        ]);
        UserPick::factory()->create([
            'user_id' => $guide->id,
            'creator_id' => $creator->id,
            'recommendation_id' => $finishedSupport->id,
            'vote_count' => 3,
        ]);
        UserPick::factory()->create([
            'user_id' => $guide->id,
            'creator_id' => $creator->id,
            'recommendation_id' => $activeSupport->id,
            'vote_count' => 2,
        ]);

        $response = $this->get(route('guides.show', ['handle' => $guide->public_handle]));

        $response->assertOk()
            ->assertSee('Public Guide')
            ->assertSee('@public-guide')
            ->assertSee('Founding Guide')
            ->assertSee('#59')
            ->assertSee('Joined July 2026')
            ->assertSee('Published Guide Impact')
            ->assertSee('Public active suggestion')
            ->assertSee('Approved')
            ->assertSee('Finished supported recommendation')
            ->assertSee('3 votes contributed')
            ->assertSee('Currently supporting 1 active request')
            ->assertSee(route('creators.published', $creator).'#recommendation-'.$published->id, false)
            ->assertSee(route('creator.queue', $creator).'#recommendation-'.$activeSuggestion->id, false)
            ->assertDontSee('Secret active allocation')
            ->assertDontSee('2 votes contributed')
            ->assertDontSee('Hidden moderation item')
            ->assertDontSee('Withdrawn private history')
            ->assertDontSee('private-guide@example.com')
            ->assertDontSee('Private Google Name')
            ->assertDontSee('votes remaining')
            ->assertDontSee('membership_tier');

        $this->assertMatchesRegularExpression('/>3<.*votes cast/s', $response->getContent());
    }

    public function test_missing_or_disabled_public_profile_returns_not_found(): void
    {
        User::factory()->create([
            'public_display_name' => 'Private Guide',
            'public_handle' => 'private-guide',
            'public_profile_enabled' => false,
        ]);

        $this->get('/@private-guide')->assertNotFound();
        $this->get('/@missing-guide')->assertNotFound();
    }

    public function test_disabled_guide_attribution_remains_visible_without_a_profile_link(): void
    {
        $guide = User::factory()->create([
            'public_display_name' => 'Visible Attribution',
            'public_handle' => 'disabled-attribution',
            'public_profile_enabled' => false,
        ]);
        $creator = Creator::factory()->create(['slug' => 'disabled-profile-link']);
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $guide->id,
            'title' => 'Attributed recommendation',
            'status' => 'approved',
        ]);

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Visible Attribution')
            ->assertDontSee(route('guides.show', ['handle' => $guide->public_handle]), false);
    }

    public function test_guide_and_creator_same_string_routes_are_deterministic(): void
    {
        $guide = User::factory()->create([
            'public_display_name' => 'Collision Guide',
            'public_handle' => 'shared-handle',
            'public_profile_enabled' => true,
        ]);
        $creator = Creator::factory()->create([
            'slug' => 'shared-handle',
            'display_name' => 'Collision Creator',
        ]);

        $this->get(route('guides.show', ['handle' => $guide->public_handle]))
            ->assertOk()
            ->assertSee('Collision Guide')
            ->assertDontSee('Collision Creator');

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Collision Creator');
    }

    public function test_profile_queries_remain_bounded_with_multiple_history_rows(): void
    {
        $guide = User::factory()->create([
            'public_display_name' => 'Bounded Guide',
            'public_handle' => 'bounded-guide',
            'public_profile_enabled' => true,
        ]);
        $creator = Creator::factory()->create();

        Recommendation::factory()->count(12)->create([
            'creator_id' => $creator->id,
            'submitted_by' => $guide->id,
            'status' => 'published',
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get(route('guides.show', ['handle' => $guide->public_handle]))->assertOk();

        $this->assertLessThanOrEqual(18, count(DB::getQueryLog()));
    }

    private function award(User $user, string $key, bool $featured = false, bool $public = true, string $subjectType = 'guide'): UserAccolade
    {
        $definition = app(AccoladeDefinitionRepository::class)->find($key);

        return UserAccolade::create([
            'user_id' => $user->id,
            'accolade_key' => $key,
            'subject_type' => $subjectType,
            'subject_id' => $user->id,
            'track' => $definition['track'],
            'level' => $definition['level'],
            'progress_value_at_award' => $definition['threshold'],
            'threshold_at_award' => $definition['threshold'],
            'awarded_at' => now()->addSeconds($definition['display_order']),
            'is_featured' => $featured,
            'featured_order' => $featured ? 1 : null,
            'is_public' => $public,
        ]);
    }
}
