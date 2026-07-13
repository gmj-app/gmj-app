<?php

namespace Tests\Feature;

use App\Models\AccoladeProgress;
use App\Models\Creator;
use App\Models\User;
use App\Models\UserAccolade;
use App\Services\Accolades\AccoladeDefinitionRepository;
use App\Services\Accolades\GuideAccoladeSummaryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DashboardAccoladeSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_guide_with_earned_accolades_sees_featured_accolade(): void
    {
        $guide = User::factory()->create();
        $this->award($guide, 'guide.published_requests.trailblazer', featured: true, manual: true);

        $this->actingAs($guide)->get(route('dashboard'))->assertOk()
            ->assertSee('My Accolades')->assertSee('Your journey so far')
            ->assertSeeInOrder(['Featured Guide accolade', 'Trailblazer'])
            ->assertSee('Your first request was published.');
    }

    public function test_manual_featured_selection_is_respected_over_higher_fallback(): void
    {
        $guide = User::factory()->create();
        $manual = $this->award($guide, 'guide.published_requests.trailblazer', featured: true, manual: true);
        $this->award($guide, 'guide.published_requests.scout');

        $summary = app(GuideAccoladeSummaryService::class)->forDashboard($guide);
        $this->assertSame($manual->id, $summary['featured']['award']->id);
    }

    public function test_highest_priority_public_guide_accolade_is_the_fallback(): void
    {
        $guide = User::factory()->create();
        $this->award($guide, 'guide.published_requests.trailblazer');
        $scout = $this->award($guide, 'guide.published_requests.scout');

        $summary = app(GuideAccoladeSummaryService::class)->forDashboard($guide);
        $this->assertSame($scout->id, $summary['featured']['award']->id);
    }

    public function test_three_preferred_progress_tracks_render_authoritative_persisted_values(): void
    {
        $guide = User::factory()->create();
        $this->award($guide, 'guide.requests_submitted.tenderfoot');
        $this->progress($guide, 'guide_requests_published', 10, 'guide.published_requests.pathfinder');
        $this->progress($guide, 'guide_supported_publications', 6, 'guide.supported_publications.compass');
        $this->progress($guide, 'guide_creator_exploration', 2, 'guide.creator_exploration.community_connector');

        $this->actingAs($guide)->get(route('dashboard'))->assertOk()
            ->assertSee('Published Requests')->assertSee('10 / 25')->assertSee('toward Pathfinder')
            ->assertSee('Supported Requests')->assertSee('6 / 10')->assertSee('toward Compass')
            ->assertSee('Creator Exploration')->assertSee('2 / 3')->assertSee('toward Community Connector')
            ->assertSee('role="progressbar"', false);
    }

    public function test_creator_accolades_are_never_included_in_personal_guide_summary(): void
    {
        $owner = User::factory()->create();
        $creator = Creator::factory()->create();
        $creator->owners()->attach($owner, ['role' => 'owner']);
        $this->award($owner, 'creator.community_publications.community_champion', subjectType: 'creator', subjectId: $creator->id);

        $this->actingAs($owner)->get(route('dashboard'))->assertOk()
            ->assertSee('Your journey starts here')
            ->assertDontSee('Community Champion');
    }

    public function test_new_guide_sees_compact_empty_state(): void
    {
        $this->actingAs(User::factory()->create())->get(route('dashboard'))->assertOk()
            ->assertSee('Your journey starts here')
            ->assertSee('Submit requests, support community ideas, and explore creators to earn accolades.')
            ->assertSee('Explore creators')
            ->assertDontSee('View all accolades');
    }

    public function test_effective_progress_never_displays_below_highest_earned_threshold(): void
    {
        $guide = User::factory()->create();
        $this->award($guide, 'guide.published_requests.tracker');
        $this->progress($guide, 'guide_requests_published', 5, 'guide.published_requests.scout');

        $this->actingAs($guide)->get(route('dashboard'))->assertOk()
            ->assertSee('10 / 25')->assertSee('toward Pathfinder')
            ->assertDontSee('5 / 25');
    }

    public function test_view_all_action_links_to_authenticated_private_accolade_page(): void
    {
        $guide = User::factory()->create();
        $this->award($guide, 'guide.creator_exploration.explorer');

        $this->actingAs($guide)->get(route('dashboard'))->assertOk()
            ->assertSee('View all accolades')
            ->assertSee('href="'.route('accolades.index').'"', false);
        $this->actingAs($guide)->get('/accolades')->assertOk()
            ->assertSee('Your earned milestones and progress across the Guide journey.')
            ->assertSee('Explorer');
    }

    public function test_summary_uses_responsive_columns_that_stack_on_mobile(): void
    {
        $guide = User::factory()->create();
        $this->award($guide, 'guide.published_requests.trailblazer');
        $this->progress($guide, 'guide_requests_published', 1, 'guide.published_requests.scout');

        $this->actingAs($guide)->get(route('dashboard'))->assertOk()
            ->assertSee('lg:grid-cols-[minmax(0,0.85fr)_minmax(0,2.15fr)]', false)
            ->assertSee('grid min-w-0 gap-3 sm:grid-cols-3', false);
    }

    public function test_summary_query_count_is_constant_and_does_not_load_domain_history(): void
    {
        $guide = User::factory()->create();
        foreach (collect(config('accolades.definitions'))->where('subject_type', 'guide')->take(12) as $definition) {
            $this->award($guide, $definition['key']);
        }
        foreach (['guide_requests_published', 'guide_supported_publications', 'guide_creator_exploration'] as $track) {
            $this->progress($guide, $track, 2, null);
        }

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($guide)->get(route('dashboard'))->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query');
        $accoladeQueries = $queries->filter(fn (string $query) => str_contains($query, 'user_accolades') || str_contains($query, 'accolade_progress'));

        $this->assertCount(2, $accoladeQueries);
        $this->assertFalse($accoladeQueries->contains(fn (string $query) => str_contains($query, 'recommendations') || str_contains($query, 'user_picks') || str_contains($query, 'creator_favorites')));
    }

    private function award(User $user, string $key, bool $featured = false, bool $manual = false, string $subjectType = 'guide', ?int $subjectId = null): UserAccolade
    {
        $definition = app(AccoladeDefinitionRepository::class)->find($key);

        return UserAccolade::create([
            'user_id' => $user->id,
            'accolade_key' => $key,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId ?? $user->id,
            'track' => $definition['track'],
            'level' => $definition['level'],
            'progress_value_at_award' => $definition['threshold'],
            'threshold_at_award' => $definition['threshold'],
            'awarded_at' => now()->addSeconds($definition['display_order']),
            'metadata' => $manual ? ['manual_featured' => true] : null,
            'is_featured' => $featured,
            'featured_order' => $featured ? 1 : null,
            'is_public' => true,
        ]);
    }

    private function progress(User $guide, string $track, int $value, ?string $next): AccoladeProgress
    {
        return AccoladeProgress::create([
            'subject_type' => 'guide',
            'subject_id' => $guide->id,
            'track' => $track,
            'current_value' => $value,
            'next_accolade_key' => $next,
            'evaluated_at' => now(),
        ]);
    }
}
