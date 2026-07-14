<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use App\Services\RecommendationStatusTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecordedVoteHistoryRegressionTest extends TestCase
{
    use RefreshDatabase;

    public function test_recorded_snapshots_support_releases_allocations_and_keeps_effective_rank_idempotently(): void
    {
        $creator = Creator::factory()->create(['slug' => 'snapshot-test']);
        $actor = User::factory()->create();
        $supporters = User::factory()->count(3)->create();
        $request = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'approved']);
        $lower = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'approved']);
        foreach ([5, 4, 5] as $index => $quantity) {
            UserPick::factory()->create(['creator_id' => $creator->id, 'recommendation_id' => $request->id, 'user_id' => $supporters[$index]->id, 'vote_count' => $quantity]);
        }
        UserPick::factory()->create(['creator_id' => $creator->id, 'recommendation_id' => $lower->id, 'user_id' => $actor->id, 'vote_count' => 10]);

        $service = app(RecommendationStatusTransitionService::class);
        $first = $service->transition($request, 'recorded', $actor);
        $service->transition($first['recommendation'], 'recorded', $actor);

        $request->refresh();
        $this->assertSame(14, $request->vote_total_at_close);
        $this->assertSame(3, $request->supporter_count_at_close);
        $this->assertNotNull($request->voting_closed_at);
        $this->assertSame(14, $request->totalVotes());
        $this->assertSame(0, $request->userPicks()->count());
        $this->assertSame(3, $request->allUserPicks()->count());
        $this->assertSame(14, (int) $request->allUserPicks()->sum('vote_count'));
        $this->assertSame(['request_recorded'], $request->allUserPicks()->pluck('release_reason')->unique()->all());

        $ranked = $creator->recommendations()->activePubliclyVisible()->withEffectiveVoteTotal()
            ->orderByDesc('user_picks_count')->pluck('id')->all();
        $this->assertSame([$request->id, $lower->id], $ranked);
    }

    public function test_repair_command_dry_run_and_apply_are_safe_and_idempotent(): void
    {
        $request = Recommendation::factory()->create(['status' => 'recorded']);
        UserPick::factory()->create(['creator_id' => $request->creator_id, 'recommendation_id' => $request->id, 'vote_count' => 7, 'released_at' => now(), 'release_reason' => 'request_recorded']);

        $this->artisan("requests:repair-vote-history --request={$request->id} --dry-run")->assertSuccessful();
        $this->assertNull($request->fresh()->vote_total_at_close);
        $this->artisan("requests:repair-vote-history --request={$request->id} --apply")->assertSuccessful();
        $this->artisan("requests:repair-vote-history --request={$request->id} --apply")->assertSuccessful();

        $this->assertSame(7, $request->fresh()->vote_total_at_close);
        $this->assertNotNull($request->fresh()->allUserPicks()->sole()->released_at);
    }
}
