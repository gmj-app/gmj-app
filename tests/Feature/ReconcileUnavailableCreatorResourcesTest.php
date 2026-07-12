<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReconcileUnavailableCreatorResourcesTest extends TestCase
{
    use RefreshDatabase;

    public function test_historical_resources_are_released_idempotently_while_history_remains(): void
    {
        $guide = User::factory()->create();
        $available = Creator::factory()->create();
        $removed = Creator::factory()->create();
        CreatorFavorite::create(['user_id' => $guide->id, 'creator_id' => $available->id]);
        $favorite = CreatorFavorite::create(['user_id' => $guide->id, 'creator_id' => $removed->id]);
        $recommendation = Recommendation::factory()->create(['creator_id' => $removed->id, 'submitted_by' => $guide->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN, 'status' => 'approved']);
        $pick = UserPick::factory()->create(['creator_id' => $removed->id, 'recommendation_id' => $recommendation->id,
            'user_id' => $guide->id, 'vote_count' => 3]);
        Creator::withoutEvents(fn () => $removed->delete());

        $this->artisan("guides:reconcile-unavailable-creator-resources --user={$guide->id} --dry-run")
            ->expectsOutputToContain('Dry run only; no data changed.')->assertSuccessful();
        $this->assertNull($favorite->fresh()->released_at);

        $this->artisan("guides:reconcile-unavailable-creator-resources --user={$guide->id} --apply")->assertSuccessful();
        $this->assertNotNull($favorite->fresh()->released_at);
        $this->assertNotNull($recommendation->fresh()->resource_released_at);
        $this->assertNotNull($pick->fresh()->released_at);
        $this->assertSame(1, $guide->fresh()->creatorFavoritesUsed());
        $this->assertDatabaseHas('recommendations', ['id' => $recommendation->id]);

        $releasedAt = $pick->fresh()->released_at->toISOString();
        $this->artisan("guides:reconcile-unavailable-creator-resources --user={$guide->id} --apply")->assertSuccessful();
        $this->assertSame($releasedAt, $pick->fresh()->released_at->toISOString());
    }

    public function test_lifecycle_releases_only_active_votes_and_restore_does_not_reconsume(): void
    {
        $guide = User::factory()->create();
        $creator = Creator::factory()->create();
        $active = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'approved']);
        $finished = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'published']);
        $activePick = UserPick::factory()->create(['creator_id' => $creator->id, 'recommendation_id' => $active->id, 'user_id' => $guide->id]);
        $finishedPick = UserPick::factory()->create(['creator_id' => $creator->id, 'recommendation_id' => $finished->id, 'user_id' => $guide->id]);
        CreatorFavorite::create(['creator_id' => $creator->id, 'user_id' => $guide->id]);

        $creator->delete();
        $this->assertNotNull($activePick->fresh()->released_at);
        $this->assertNull($finishedPick->fresh()->released_at);
        $creator->restore();
        $this->assertSame(0, $guide->fresh()->creatorFavoritesUsed());
        $this->assertSame(0, $guide->fresh()->votesUsedFor($creator->fresh()));
    }

    public function test_available_creator_activity_is_untouched(): void
    {
        $guide = User::factory()->create();
        $creator = Creator::factory()->create();
        $favorite = CreatorFavorite::create(['creator_id' => $creator->id, 'user_id' => $guide->id]);
        $this->artisan('guides:reconcile-unavailable-creator-resources --apply')->assertSuccessful();
        $this->assertNull($favorite->fresh()->released_at);
    }
}
