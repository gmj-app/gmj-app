<?php

namespace Tests\Feature;

use App\Events\AccoladeAwarded;
use App\Models\AccoladeProgress;
use App\Models\Creator;
use App\Models\User;
use App\Models\UserAccolade;
use App\Models\UserPick;
use App\Services\Accolades\AccoladeEvaluationService;
use Database\Seeders\AccoladeDemoSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use RuntimeException;
use Tests\TestCase;

class AccoladeTestingToolkitTest extends TestCase
{
    use RefreshDatabase;

    public function test_demo_seeder_creates_below_exact_and_above_boundaries_for_every_launch_metric(): void
    {
        $this->seed(AccoladeDemoSeeder::class);
        $evaluation = app(AccoladeEvaluationService::class);
        $guideTracks = [
            'submitted' => ['track' => 'guide_requests_published', 'values' => [4, 5, 6], 'exact_key' => 'guide.published_requests.scout'],
            'supported' => ['track' => 'guide_supported_publications', 'values' => [4, 5, 6], 'exact_key' => 'guide.supported_publications.trail_map'],
            'favorites' => ['track' => 'guide_creator_exploration', 'values' => [2, 3, 4], 'exact_key' => 'guide.creator_exploration.community_connector'],
        ];
        $creatorTracks = [
            'creator-publications' => ['track' => 'creator_community_publications', 'values' => [4, 5, 6], 'exact_key' => 'creator.community_publications.listener'],
            'creator-consistency' => ['track' => 'creator_consistency', 'values' => [2, 3, 4], 'exact_key' => 'creator.consistency.momentum'],
            'creator-reach' => ['track' => 'creator_community_reach', 'values' => [24, 25, 26], 'exact_key' => 'creator.community_reach.gathering_crowd'],
        ];

        foreach ($guideTracks as $scenario => $expectation) {
            foreach (['below', 'exact', 'above'] as $index => $boundary) {
                $user = User::where('email', "accolade.{$scenario}.{$boundary}@example.test")->firstOrFail();
                $result = $evaluation->evaluateGuide($user, [$expectation['track']], persist: false);
                $this->assertSame($expectation['values'][$index], $result->tracks[$expectation['track']]['current_value'], "{$scenario} {$boundary}");
                if ($boundary === 'exact') {
                    $this->assertContains($expectation['exact_key'], $result->tracks[$expectation['track']]['would_award']);
                }
            }
        }
        foreach ($creatorTracks as $scenario => $expectation) {
            foreach (['below', 'exact', 'above'] as $index => $boundary) {
                $creator = Creator::where('slug', "accolade-{$scenario}-{$boundary}")->firstOrFail();
                $result = $evaluation->evaluateCreator($creator, [$expectation['track']], persist: false);
                $this->assertSame($expectation['values'][$index], $result->tracks[$expectation['track']]['current_value'], "{$scenario} {$boundary}");
                if ($boundary === 'exact') {
                    $this->assertContains($expectation['exact_key'], $result->tracks[$expectation['track']]['would_award']);
                }
            }
        }
    }

    public function test_seeded_controls_prove_vote_quantity_history_distinct_requests_months_self_exclusion_and_creator_origin_exclusion(): void
    {
        $this->seed(AccoladeDemoSeeder::class);
        $evaluation = app(AccoladeEvaluationService::class);
        $supporter = User::where('email', 'accolade.supported.exact@example.test')->firstOrFail();
        $support = $evaluation->evaluateGuide($supporter, ['guide_supported_publications'], persist: false)->tracks['guide_supported_publications'];

        $this->assertSame(5, $support['current_value']);
        $this->assertCount(5, $support['qualifying_record_ids']);
        $this->assertSame(7, UserPick::where('user_id', $supporter->id)->max('vote_count'));
        $this->assertSame(5, UserPick::where('user_id', $supporter->id)->whereNotNull('released_at')->count());

        $submitted = User::where('email', 'accolade.submitted.exact@example.test')->firstOrFail();
        $ownRequest = $submitted->recommendationsSubmitted()->firstOrFail();
        UserPick::create(['user_id' => $submitted->id, 'creator_id' => $ownRequest->creator_id, 'recommendation_id' => $ownRequest->id, 'vote_count' => 9]);
        $selfSupport = $evaluation->evaluateGuide($submitted, ['guide_supported_publications'], persist: false)->tracks['guide_supported_publications'];
        $this->assertSame(0, $selfSupport['current_value']);

        $consistencyCreator = Creator::where('slug', 'accolade-creator-consistency-exact')->firstOrFail();
        $consistency = $evaluation->evaluateCreator($consistencyCreator, ['creator_consistency'], persist: false)->tracks['creator_consistency'];
        $this->assertSame(3, $consistency['current_value']);
        $this->assertCount(4, $consistency['qualifying_record_ids']);
        $this->assertCount(3, $consistency['metadata']['qualifying_months']);

        $publicationCreator = Creator::where('slug', 'accolade-creator-publications-exact')->firstOrFail();
        $publications = $evaluation->evaluateCreator($publicationCreator, ['creator_community_publications'], persist: false)->tracks['creator_community_publications'];
        $this->assertSame(5, $publications['current_value']);
        $this->assertSame(6, $publicationCreator->recommendations()->count());
    }

    public function test_test_subject_is_read_only_without_evaluate_and_evaluation_is_notification_free_and_idempotent(): void
    {
        $this->seed(AccoladeDemoSeeder::class);
        Notification::fake();
        Event::fake([AccoladeAwarded::class]);
        $email = 'accolade.supported.exact@example.test';

        $this->artisan("accolades:test-subject --email={$email} --show-source-records --show-earned --show-progress")
            ->expectsOutputToContain('Mode: READ ONLY')
            ->expectsOutputToContain('Authoritative metric value: 5')
            ->expectsOutputToContain('Missing accolades at current value: Hiking Boots, Trail Map')
            ->expectsOutputToContain('Qualifying record IDs:')
            ->assertSuccessful();
        $this->assertSame(0, UserAccolade::count());
        $this->assertSame(0, AccoladeProgress::count());

        $this->artisan("accolades:test-subject --email={$email} --evaluate --show-earned --show-progress")
            ->expectsOutputToContain('Mode: EVALUATE (mutations enabled)')->assertSuccessful();
        $awardCount = UserAccolade::count();
        $this->artisan("accolades:test-subject --email={$email} --evaluate")->assertSuccessful();
        $this->assertSame($awardCount, UserAccolade::count());
        $this->assertGreaterThan(0, AccoladeProgress::count());
        Notification::assertNothingSent();
        Event::assertNotDispatched(AccoladeAwarded::class);
    }

    public function test_demo_seeder_is_idempotent_locally_and_refuses_production(): void
    {
        $this->seed(AccoladeDemoSeeder::class);
        $userCount = User::where('email', 'like', 'accolade.%@example.test')->count();
        $creatorCount = Creator::where('slug', 'like', 'accolade-%')->count();
        $this->seed(AccoladeDemoSeeder::class);
        $this->assertSame($userCount, User::where('email', 'like', 'accolade.%@example.test')->count());
        $this->assertSame($creatorCount, Creator::where('slug', 'like', 'accolade-%')->count());

        $previous = $this->app['env'];
        $this->app['env'] = 'production';
        try {
            $this->expectException(RuntimeException::class);
            (new AccoladeDemoSeeder)->run();
        } finally {
            $this->app['env'] = $previous;
        }
    }
}
