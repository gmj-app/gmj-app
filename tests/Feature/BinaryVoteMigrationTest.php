<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use App\Services\RecommendationStatusTransitionService;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
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

    public function test_migration_normalizes_all_positive_active_values_and_preserves_history_and_other_fields(): void
    {
        $this->dropBinaryTriggers();
        $request = Recommendation::factory()->create();
        $active = collect([1, 2, 3, 9])->map(fn (int $quantity) => UserPick::factory()->create([
            'recommendation_id' => $request->id,
            'creator_id' => $request->creator_id,
            'vote_count' => $quantity,
            'rank' => $quantity + 10,
        ]));
        $released = collect([1, 2, 3])->map(fn (int $quantity) => UserPick::factory()->create([
            'recommendation_id' => $request->id,
            'creator_id' => $request->creator_id,
            'vote_count' => $quantity,
            'rank' => $quantity + 20,
            'released_at' => now(),
            'release_reason' => 'request_published',
        ]));

        $this->binaryMigration()->up();

        $this->assertSame([1, 1, 1, 1], $active->map(fn (UserPick $pick) => $pick->fresh()->vote_count)->all());
        $this->assertSame([11, 12, 13, 19], $active->map(fn (UserPick $pick) => $pick->fresh()->rank)->all());
        $this->assertSame([1, 2, 3], $released->map(fn (UserPick $pick) => $pick->fresh()->vote_count)->all());
        $this->assertSame([21, 22, 23], $released->map(fn (UserPick $pick) => $pick->fresh()->rank)->all());
        $this->assertTrue(Schema::hasIndex('user_picks', 'user_picks_request_active_index'));

        $this->binaryMigration()->up();
        $this->assertSame([1, 1, 1, 1], $active->map(fn (UserPick $pick) => $pick->fresh()->vote_count)->all());
    }

    public function test_partial_prior_index_state_is_safe_and_constraint_is_recreated(): void
    {
        $this->dropBinaryTriggers();
        $this->assertTrue(Schema::hasIndex('user_picks', 'user_picks_request_active_index'));

        $this->binaryMigration()->up();

        $this->expectException(QueryException::class);
        UserPick::factory()->create(['vote_count' => 2]);
    }

    #[DataProvider('corruptActiveValues')]
    public function test_corrupt_active_values_fail_before_positive_rows_are_mutated(?int $corrupt): void
    {
        $this->dropBinaryTriggers();
        $request = Recommendation::factory()->create();
        $positive = UserPick::factory()->create([
            'recommendation_id' => $request->id,
            'creator_id' => $request->creator_id,
            'vote_count' => 3,
        ]);

        if ($corrupt === null) {
            Schema::table('user_picks', fn ($table) => $table->unsignedInteger('vote_count')->nullable()->change());
        }
        DB::table('user_picks')->insert([
            'user_id' => User::factory()->create()->id,
            'creator_id' => $request->creator_id,
            'recommendation_id' => Recommendation::factory()->create(['creator_id' => $request->creator_id])->id,
            'vote_count' => $corrupt,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        try {
            $this->binaryMigration()->up();
            $this->fail('Expected corrupt active vote values to stop the migration.');
        } catch (\RuntimeException $exception) {
            $this->assertStringContainsString('Binary vote migration stopped', $exception->getMessage());
        }

        $this->assertSame(3, $positive->fresh()->vote_count);
    }

    public static function corruptActiveValues(): array
    {
        return ['zero' => [0], 'negative' => [-1], 'null' => [null]];
    }

    private function dropBinaryTriggers(): void
    {
        DB::unprepared('DROP TRIGGER IF EXISTS user_picks_active_vote_binary_insert; DROP TRIGGER IF EXISTS user_picks_active_vote_binary_update;');
    }

    private function binaryMigration(): Migration
    {
        return require database_path('migrations/2026_08_13_000000_enforce_binary_active_votes.php');
    }
}
