<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use App\Services\RecommendationStatusTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicClosedRequestsTest extends TestCase
{
    use RefreshDatabase;

    public function test_closed_requests_are_archived_and_removed_from_active_queue(): void
    {
        $creator = Creator::factory()->create(['slug' => 'archive-test', 'status' => 'active']);
        $alreadySeen = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'already_seen', 'title' => 'Already covered', 'resolved_at' => now()->subDay()]);
        $passed = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'passed', 'title' => 'Not a fit', 'resolved_at' => now()]);
        Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'approved', 'title' => 'Still active']);

        $this->get(route('creator.queue', $creator))
            ->assertOk()->assertSee('Still active')->assertDontSee('Already covered')->assertDontSee('Not a fit');

        $this->get(route('creators.closed', $creator))
            ->assertOk()->assertSeeInOrder(['Not a fit', 'Already covered'])->assertSee('Closed Requests');

        $this->get(route('creators.closed', ['creator' => $creator, 'status' => 'passed']))
            ->assertOk()->assertSee('Not a fit')->assertDontSee('Already covered');
    }

    public function test_archive_shows_historical_support_and_public_notes_but_not_private_reasons(): void
    {
        $creator = Creator::factory()->create(['status' => 'active']);
        $request = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'already_seen',
            'title' => 'A familiar song',
            'resolved_at' => now(),
            'public_resolution_note' => 'Covered during a livestream.',
            'private_resolution_reason' => 'Internal planning detail.',
            'prior_coverage_url' => 'https://example.com/coverage',
            'prior_coverage_title' => 'Watch the earlier coverage',
        ]);
        UserPick::factory()->create(['creator_id' => $creator->id, 'recommendation_id' => $request->id, 'vote_count' => 4, 'released_at' => now(), 'release_reason' => 'request_closed']);

        $this->get(route('creators.closed', $creator))
            ->assertOk()->assertSee('4 historical votes')->assertSee('Covered during a livestream.')
            ->assertSee('Watch the earlier coverage')->assertDontSee('Internal planning detail.');
    }

    public function test_closing_a_request_releases_resources_once_and_records_resolution_time(): void
    {
        $creator = Creator::factory()->create(['status' => 'active']);
        $actor = User::factory()->create();
        $request = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'approved']);
        $pick = UserPick::factory()->create(['creator_id' => $creator->id, 'recommendation_id' => $request->id, 'vote_count' => 3]);

        $service = app(RecommendationStatusTransitionService::class);
        $first = $service->transition($request, 'passed', $actor, ['public_resolution_note' => 'Not planned.']);
        $resolvedAt = $request->fresh()->resolved_at;
        $second = $service->transition($request->fresh(), 'passed', $actor);

        $this->assertSame(3, $first['released_votes']);
        $this->assertSame(0, $second['released_votes']);
        $this->assertNotNull($pick->fresh()->released_at);
        $this->assertSame('request_closed', $pick->fresh()->release_reason);
        $this->assertTrue($resolvedAt->equalTo($request->fresh()->resolved_at));
    }
}
