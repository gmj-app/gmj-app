<?php

namespace Tests\Feature;

use App\Models\AccoladeProgress;
use App\Models\Creator;
use App\Models\User;
use App\Models\UserAccolade;
use App\Services\Accolades\AccoladeDefinitionRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class GuideAccoladePageTest extends TestCase
{
    use RefreshDatabase;

    public function test_page_renders_featured_early_recognition_and_ordered_compact_tracks_from_persisted_data(): void
    {
        $guide = User::factory()->create(['guide_number' => 1]);
        $explorer = $this->award($guide, 'guide.creator_exploration.explorer', '2026-07-10 10:00:00');
        $featured = $this->award($guide, 'guide.influence.first_footprint', '2026-07-11 10:00:00', featured: true);
        $trailblazer = $this->award($guide, 'guide.published_requests.trailblazer', '2026-07-12 10:00:00');
        $this->award($guide, 'guide.requests_submitted.tenderfoot', '2026-07-13 10:00:00');
        $this->award($guide, 'guide.supported_publications.hiking_boots', '2026-07-09 10:00:00');
        $this->progress($guide, 'guide_creator_exploration', 2);
        $this->progress($guide, 'guide_influence', 5);
        $this->progress($guide, 'guide_requests_published', 3);
        $this->progress($guide, 'guide_requests_submitted', 1);
        $this->progress($guide, 'guide_supported_publications', 2);

        $response = $this->actingAs($guide)->get(route('accolades.index'))->assertOk()
            ->assertSee('Featured accolade')->assertSee('First Footprint')
            ->assertSee('value="'.$featured->id.'" selected', false)
            ->assertSee('Early Guide recognition')->assertSee('Founding Guide (#1)')
            ->assertSee('border-amber-300 bg-gradient-to-r', false)
            ->assertSee('grid grid-cols-1 gap-4 lg:grid-cols-2', false)
            ->assertSeeInOrder(['Creator Exploration', 'Influence', 'Published Requests', 'Request Participation', 'Supported Requests'])
            ->assertSee('2 / 3')->assertSee('toward Community Connector')
            ->assertSee('5 / 10')->assertSee('toward Ripple Maker')
            ->assertSee('3 / 5')->assertSee('toward Scout')
            ->assertSee('2 / 5')->assertSee('toward Trail Map')
            ->assertSee('Track complete')
            ->assertSee('Jul 12, 2026')
            ->assertSee('aria-valuenow="2"', false)
            ->assertSee('aria-valuemax="3"', false)
            ->assertSee('style="width: 67%"', false)
            ->assertSee('dark:bg-slate-900', false);

        $this->assertSame(5, substr_count($response->getContent(), 'data-accolade-track='));
        $this->assertSame(1, substr_count($response->getContent(), 'Founding Guide (#1)'));
        $this->assertSame($explorer->awarded_at->toDateTimeString(), Carbon::parse('2026-07-10 10:00:00')->toDateTimeString());
        $this->assertSame($trailblazer->awarded_at->toDateTimeString(), Carbon::parse('2026-07-12 10:00:00')->toDateTimeString());
    }

    public function test_og_and_empty_guide_states_keep_all_tracks_without_live_calculation(): void
    {
        $og = User::factory()->create(['guide_number' => 123]);
        $this->actingAs($og)->get(route('accolades.index'))->assertOk()
            ->assertSee('OG Guide (#123)')
            ->assertSee('border-slate-300 bg-gradient-to-r', false)
            ->assertSee('No featured accolade yet')
            ->assertSee('No accolade earned yet')
            ->assertSee('0 / 1')->assertSee('toward Explorer')
            ->assertSee('Explore creators');

        $ordinary = User::factory()->create(['guide_number' => 700]);
        $response = $this->actingAs($ordinary)->get(route('accolades.index'))->assertOk()->assertDontSee('Early Guide recognition');
        $this->assertSame(5, substr_count($response->getContent(), 'data-accolade-track='));
    }

    public function test_highest_earned_and_effective_progress_are_used_and_creator_accolades_are_excluded(): void
    {
        $guide = User::factory()->create(['guide_number' => 700]);
        $creator = Creator::factory()->create();
        $this->award($guide, 'guide.published_requests.trailblazer', '2026-06-01 10:00:00');
        $this->award($guide, 'guide.published_requests.tracker', '2026-06-15 10:00:00');
        $this->award($guide, 'creator.community_publications.community_champion', '2026-07-01 10:00:00', subjectType: 'creator', subjectId: $creator->id);
        $this->progress($guide, 'guide_requests_published', 4);

        $this->actingAs($guide)->get(route('accolades.index'))->assertOk()
            ->assertSee('Tracker')->assertSee('Trailblazer')
            ->assertSee('Jun 15, 2026')
            ->assertSee('10 / 25')->assertSee('toward Pathfinder')
            ->assertDontSee('4 / 25')
            ->assertDontSee('Community Champion');
    }

    public function test_page_rendering_only_reads_persisted_accolade_summary_tables(): void
    {
        $guide = User::factory()->create(['guide_number' => 700]);
        $this->award($guide, 'guide.creator_exploration.explorer', '2026-07-01 10:00:00');
        $this->progress($guide, 'guide_creator_exploration', 2);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $this->actingAs($guide)->get(route('accolades.index'))->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query');
        $summaryQueries = $queries->filter(fn (string $query) => str_contains($query, 'user_accolades') || str_contains($query, 'accolade_progress'));

        $this->assertCount(2, $summaryQueries);
        $this->assertFalse($queries->contains(fn (string $query) => str_contains($query, 'recommendations') || str_contains($query, 'user_picks') || str_contains($query, 'creator_favorites')));
    }

    private function award(User $user, string $key, string $date, bool $featured = false, string $subjectType = 'guide', ?int $subjectId = null): UserAccolade
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
            'awarded_at' => Carbon::parse($date),
            'metadata' => $featured ? ['manual_featured' => true] : null,
            'is_featured' => $featured,
            'featured_order' => $featured ? 1 : null,
            'is_public' => true,
        ]);
    }

    private function progress(User $guide, string $track, int $value): void
    {
        AccoladeProgress::create([
            'subject_type' => 'guide',
            'subject_id' => $guide->id,
            'track' => $track,
            'current_value' => $value,
            'evaluated_at' => now(),
        ]);
    }
}
