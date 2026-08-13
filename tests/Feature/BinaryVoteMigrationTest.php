<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use App\Services\RecommendationStatusTransitionService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class BinaryVoteMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_database_enforces_unique_binary_active_support_and_allows_legacy_history(): void
    {
        $request = Recommendation::factory()->create();
        $user = User::factory()->create();
        UserPick::factory()->create(['recommendation_id' => $request->id, 'creator_id' => $request->creator_id, 'user_id' => $user->id]);

        $this->expectException(QueryException::class);
        UserPick::factory()->create(['recommendation_id' => $request->id, 'creator_id' => $request->creator_id, 'user_id' => $user->id]);
    }

    public function test_database_rejects_active_quantity_above_one(): void
    {
        $this->expectException(QueryException::class);
        UserPick::factory()->create(['vote_count' => 2]);
    }

    public function test_released_legacy_quantity_and_frozen_total_are_preserved(): void
    {
        $request = Recommendation::factory()->create([
            'status' => 'published',
            'vote_total_at_close' => 7,
            'supporter_count_at_close' => 1,
        ]);
        UserPick::factory()->create([
            'recommendation_id' => $request->id,
            'creator_id' => $request->creator_id,
            'vote_count' => 7,
            'released_at' => now(),
            'release_reason' => 'request_published',
        ]);

        $this->artisan('votes:migrate-to-binary --apply')->assertSuccessful();

        $this->assertSame(7, UserPick::query()->sole()->vote_count);
        $this->assertSame(7, $request->fresh()->vote_total_at_close);
    }

    public function test_dry_run_does_not_mutate_and_apply_is_idempotent(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            $this->markTestSkipped('Legacy active rows are injected by temporarily disabling SQLite test triggers.');
        }

        DB::unprepared('DROP TRIGGER IF EXISTS user_picks_active_vote_binary_insert; DROP TRIGGER IF EXISTS user_picks_active_vote_binary_update;');
        $request = Recommendation::factory()->create(['status' => 'approved']);
        $pick = UserPick::factory()->create([
            'recommendation_id' => $request->id,
            'creator_id' => $request->creator_id,
            'vote_count' => 3,
        ]);

        $this->artisan('votes:migrate-to-binary --dry-run')
            ->expectsOutputToContain('Dry run only. No data was changed.')
            ->assertSuccessful();
        $this->assertSame(3, $pick->fresh()->vote_count);

        $this->artisan('votes:migrate-to-binary --apply')->assertSuccessful();
        $this->artisan('votes:migrate-to-binary --apply')->assertSuccessful();
        $this->assertSame(1, $pick->fresh()->vote_count);
        $this->assertSame(1, $request->fresh()->totalVotes());
    }

    public function test_future_close_snapshots_unique_supporters_once(): void
    {
        $creator = Creator::factory()->create();
        $request = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'approved']);
        UserPick::factory()->count(3)->create(['recommendation_id' => $request->id, 'creator_id' => $creator->id]);
        $actor = User::factory()->create();

        $result = app(RecommendationStatusTransitionService::class)
            ->transition($request, 'passed', $actor);

        $this->assertSame(3, $result['closed_supports']);
        $this->assertSame(3, $request->fresh()->vote_total_at_close);
        $this->assertSame(3, $request->fresh()->supporter_count_at_close);
        $this->assertSame(3, $request->fresh()->historicalUserPicks()->count());
    }
}
