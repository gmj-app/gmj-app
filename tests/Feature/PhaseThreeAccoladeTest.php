<?php

namespace Tests\Feature;

use App\Events\AccoladeAwarded;
use App\Models\AccoladeProgress;
use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserAccolade;
use App\Models\UserPick;
use App\Services\Accolades\AccoladeDefinitionRepository;
use App\Services\Accolades\AccoladeEvaluationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Event;
use Tests\TestCase;

class PhaseThreeAccoladeTest extends TestCase
{
    use RefreshDatabase;

    public function test_launch_definitions_have_stable_ordered_allowlisted_contracts(): void
    {
        $repository = app(AccoladeDefinitionRepository::class);
        $repository->validate();

        $this->assertCount(29, $repository->all());
        $this->assertSame($repository->all()->count(), $repository->all()->pluck('key')->unique()->count());
        $this->assertTrue($repository->all()->every(fn (array $item) => ! str_contains($item['name'], 'Suggestion')));
    }

    public function test_guide_tracks_count_distinct_valid_history_and_awards_idempotently(): void
    {
        Event::fake([AccoladeAwarded::class]);
        $submitter = User::factory()->create();
        $supporter = User::factory()->create();
        $creator = Creator::factory()->create();
        $own = $this->publishedCommunityRequest($creator, $submitter);
        $supported = $this->publishedCommunityRequest($creator, User::factory()->create());
        UserPick::factory()->create(['user_id' => $supporter->id, 'creator_id' => $creator->id, 'recommendation_id' => $supported->id, 'vote_count' => 7, 'released_at' => now(), 'release_reason' => 'request_published']);
        UserPick::factory()->create(['user_id' => $submitter->id, 'creator_id' => $creator->id, 'recommendation_id' => $own->id, 'vote_count' => 2]);

        $service = app(AccoladeEvaluationService::class);
        $service->evaluateGuide($submitter);
        $service->evaluateGuide($supporter);
        $service->evaluateGuide($submitter);

        $this->assertDatabaseHas('user_accolades', ['subject_type' => 'guide', 'subject_id' => $submitter->id, 'accolade_key' => 'guide.requests_submitted.tenderfoot']);
        $this->assertDatabaseHas('user_accolades', ['subject_id' => $submitter->id, 'accolade_key' => 'guide.published_requests.trailblazer']);
        $this->assertDatabaseHas('user_accolades', ['subject_id' => $submitter->id, 'accolade_key' => 'guide.influence.first_footprint']);
        $this->assertDatabaseMissing('user_accolades', ['subject_id' => $submitter->id, 'accolade_key' => 'guide.supported_publications.hiking_boots']);
        $this->assertDatabaseHas('user_accolades', ['subject_id' => $supporter->id, 'accolade_key' => 'guide.supported_publications.hiking_boots', 'progress_value_at_award' => 1]);
        $this->assertSame(1, UserAccolade::where('subject_id', $submitter->id)->where('accolade_key', 'guide.published_requests.trailblazer')->count());
        Event::assertDispatched(AccoladeAwarded::class);
    }

    public function test_accolade_awarded_event_is_dispatched_once_for_a_new_unique_award(): void
    {
        Event::fake([AccoladeAwarded::class]);
        $guide = User::factory()->create();
        Recommendation::factory()->create([
            'submitted_by' => $guide->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'status' => 'approved',
        ]);
        $service = app(AccoladeEvaluationService::class);

        $service->evaluateGuide($guide, ['guide_requests_submitted'], ['event_type' => 'test', 'event_id' => 10]);
        $service->evaluateGuide($guide, ['guide_requests_submitted'], ['event_type' => 'test', 'event_id' => 10]);

        Event::assertDispatchedTimes(AccoladeAwarded::class, 1);
        Event::assertDispatched(fn (AccoladeAwarded $event) => $event->accoladeKey === 'guide.requests_submitted.tenderfoot'
            && $event->subjectId === $guide->id && $event->sourceContext['event_id'] === 10);
    }

    public function test_exploration_awards_permanently_while_current_progress_can_decrease(): void
    {
        $guide = User::factory()->create();
        $creators = Creator::factory()->count(5)->create();
        foreach ($creators as $creator) {
            CreatorFavorite::create(['user_id' => $guide->id, 'creator_id' => $creator->id]);
        }
        $service = app(AccoladeEvaluationService::class);
        $service->evaluateGuide($guide, ['guide_creator_exploration']);
        CreatorFavorite::where('user_id', $guide->id)->limit(4)->delete();
        $service->evaluateGuide($guide, ['guide_creator_exploration'], ['source' => 'test'], true, false);

        $this->assertDatabaseHas('user_accolades', ['subject_id' => $guide->id, 'accolade_key' => 'guide.creator_exploration.ambassador']);
        $this->assertDatabaseHas('accolade_progress', ['subject_type' => 'guide', 'subject_id' => $guide->id, 'track' => 'guide_creator_exploration', 'current_value' => 1]);
    }

    public function test_creator_publication_consistency_and_reach_exclude_owner_and_deduplicate_guides(): void
    {
        $owner = User::factory()->create();
        $creator = Creator::factory()->create();
        $creator->owners()->attach($owner, ['role' => 'owner']);
        foreach ([now()->subMonths(2), now()->subMonth(), now()] as $date) {
            $this->publishedCommunityRequest($creator, User::factory()->create(), $date);
        }
        $guides = User::factory()->count(22)->create();
        foreach ($guides as $guide) {
            CreatorFavorite::create(['user_id' => $guide->id, 'creator_id' => $creator->id]);
        }
        CreatorFavorite::create(['user_id' => $owner->id, 'creator_id' => $creator->id]);

        app(AccoladeEvaluationService::class)->evaluateCreator($creator);

        $this->assertDatabaseHas('user_accolades', ['subject_type' => 'creator', 'subject_id' => $creator->id, 'accolade_key' => 'creator.community_publications.first_step']);
        $this->assertDatabaseHas('user_accolades', ['subject_id' => $creator->id, 'accolade_key' => 'creator.consistency.momentum']);
        $this->assertDatabaseHas('user_accolades', ['subject_id' => $creator->id, 'accolade_key' => 'creator.community_reach.gathering_crowd']);
        $this->assertDatabaseHas('accolade_progress', ['subject_type' => 'creator', 'subject_id' => $creator->id, 'track' => 'creator_community_reach', 'current_value' => 25]);
    }

    public function test_creator_added_requests_do_not_count_as_community_publications(): void
    {
        $owner = User::factory()->create();
        $creator = Creator::factory()->create();
        $creator->owners()->attach($owner, ['role' => 'owner']);
        Recommendation::factory()->create(['creator_id' => $creator->id, 'submitted_by' => $owner->id, 'submission_source' => Recommendation::SUBMISSION_SOURCE_CREATOR, 'status' => 'published', 'published_at' => now()]);

        app(AccoladeEvaluationService::class)->evaluateCreator($creator);

        $this->assertDatabaseMissing('user_accolades', ['subject_id' => $creator->id, 'track' => 'creator_community_publications']);
    }

    public function test_backfill_dry_run_is_non_mutating_and_apply_is_idempotent(): void
    {
        $guide = User::factory()->create();
        Recommendation::factory()->create(['submitted_by' => $guide->id, 'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN, 'status' => 'approved']);

        $this->artisan("accolades:backfill --subject=guides --user={$guide->id} --dry-run")->assertSuccessful();
        $this->assertSame(0, UserAccolade::count());
        $this->assertSame(0, AccoladeProgress::count());
        $this->artisan("accolades:backfill --subject=guides --user={$guide->id} --apply")->assertSuccessful();
        $this->artisan("accolades:backfill --subject=guides --user={$guide->id} --apply")->assertSuccessful();
        $this->assertSame(1, UserAccolade::where('accolade_key', 'guide.requests_submitted.tenderfoot')->count());
    }

    public function test_guide_can_feature_only_an_owned_public_guide_accolade(): void
    {
        $guide = User::factory()->create();
        $other = User::factory()->create();
        $award = $this->award($guide, 'guide', $guide->id, 'guide.requests_submitted.tenderfoot');
        $otherAward = $this->award($other, 'guide', $other->id, 'guide.requests_submitted.tenderfoot');

        $this->actingAs($guide)->patch(route('profile.accolades.featured'), ['accolade_id' => $award->id])->assertRedirect();
        $this->assertTrue($award->fresh()->is_featured);
        $this->actingAs($guide)->patch(route('profile.accolades.featured'), ['accolade_id' => $otherAward->id])->assertNotFound();
    }

    public function test_creator_feature_selection_is_limited_authorized_and_visible_in_header(): void
    {
        $owner = User::factory()->create();
        $stranger = User::factory()->create();
        $creator = Creator::factory()->create();
        $creator->owners()->attach($owner, ['role' => 'owner']);
        $award = $this->award($owner, 'creator', $creator->id, 'creator.community_publications.first_step');

        $this->actingAs($stranger)->patch(route('creators.settings.accolades.update', $creator), ['accolade_ids' => [$award->id]])->assertForbidden();
        $this->actingAs($owner)->patch(route('creators.settings.accolades.update', $creator), ['accolade_ids' => [$award->id]])->assertRedirect();
        $this->get(route('creator.queue', $creator))->assertOk()->assertSee('First Step')->assertSee('Community milestones');
    }

    private function publishedCommunityRequest(Creator $creator, User $guide, ?Carbon $date = null): Recommendation
    {
        return Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $guide->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'status' => 'published',
            'published_at' => $date ?? now(),
        ]);
    }

    private function award(User $user, string $subjectType, int $subjectId, string $key): UserAccolade
    {
        $definition = app(AccoladeDefinitionRepository::class)->find($key);

        return UserAccolade::create([
            'user_id' => $user->id, 'subject_type' => $subjectType, 'subject_id' => $subjectId,
            'accolade_key' => $key, 'track' => $definition['track'], 'level' => $definition['level'],
            'progress_value_at_award' => $definition['threshold'], 'threshold_at_award' => $definition['threshold'],
            'awarded_at' => now(), 'is_public' => true,
        ]);
    }
}
