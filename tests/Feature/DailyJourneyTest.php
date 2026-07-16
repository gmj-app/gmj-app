<?php

namespace Tests\Feature;

use App\Models\GameDailyBest;
use App\Models\GameDailyChampion;
use App\Models\GameDay;
use App\Models\GameRun;
use App\Models\GameRunSession;
use App\Models\User;
use App\Services\DailyJourney\FinalizationService;
use App\Services\DailyJourney\GameDayService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class DailyJourneyTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_leaderboard_but_cannot_issue_a_run(): void
    {
        $this->get(route('game.leaderboard'))->assertOk()->assertSee('Leaderboards');
        $this->postJson(route('game.runs.issue'))->assertUnauthorized();
    }

    public function test_game_day_uses_the_manila_calendar_boundary(): void
    {
        Carbon::setTestNow('2026-07-16 16:30:00 UTC');
        $day = app(GameDayService::class)->current();

        $this->assertSame('2026-07-17', $day->local_date->toDateString());
        $this->assertSame('Asia/Manila', $day->timezone);
        $this->assertSame('2026-07-16T16:00:00+00:00', $day->starts_at->toIso8601String());
    }

    public function test_plausible_run_is_accepted_once_and_projects_the_daily_best(): void
    {
        $user = User::factory()->create(['public_display_name' => 'Runner', 'public_handle' => 'runner', 'public_profile_completed_at' => now()]);
        $issue = $this->actingAs($user)->postJson(route('game.runs.issue'))->assertCreated()->json();
        $payload = ['score' => 375, 'distance' => 375, 'duration_ms' => 10000, 'collectible_count' => 0, 'powerup_pickup_count' => 0, 'powerup_use_count' => 0, 'maximum_speed_tier' => 1, 'client_version' => config('daily_journey.version'), 'events' => [['t' => 0, 'e' => 'run_started']]];

        $this->postJson(route('game.runs.finish', $issue['token']), $payload)->assertOk()->assertJsonPath('status', 'accepted');
        $this->postJson(route('game.runs.finish', $issue['token']), $payload)->assertUnprocessable();

        $this->assertDatabaseCount('game_runs', 1);
        $this->assertSame(375, GameDailyBest::query()->sole()->score);
    }

    public function test_score_tampering_is_rejected_and_does_not_rank(): void
    {
        $user = User::factory()->create(['public_display_name' => 'Runner', 'public_handle' => 'runner', 'public_profile_completed_at' => now()]);
        $token = $this->actingAs($user)->postJson(route('game.runs.issue'))->json('token');
        $payload = ['score' => 999999, 'distance' => 375, 'duration_ms' => 10000, 'collectible_count' => 0, 'powerup_pickup_count' => 0, 'powerup_use_count' => 0, 'maximum_speed_tier' => 1, 'client_version' => config('daily_journey.version')];

        $this->postJson(route('game.runs.finish', $token), $payload)->assertUnprocessable()->assertJsonPath('status', 'rejected');
        $this->assertDatabaseCount('game_daily_bests', 0);
        $this->assertSame('rejected', GameRun::query()->sole()->validation_status);
    }

    public function test_finalization_is_idempotent_and_notifies_one_champion(): void
    {
        $user = User::factory()->create();
        $day = GameDay::query()->create(['game_key' => config('daily_journey.key'), 'local_date' => '2026-07-15', 'timezone' => 'Asia/Manila', 'starts_at' => now()->subDays(2), 'ends_at' => now()->subDay(), 'status' => 'open']);
        $session = $day->hasMany(GameRunSession::class)->create(['public_token' => fake()->uuid(), 'user_id' => $user->id, 'game_key' => config('daily_journey.key'), 'status' => 'accepted', 'random_seed' => 123, 'game_version' => config('daily_journey.version'), 'issued_at' => now()->subDay(), 'expires_at' => now()->subDay(), 'submitted_at' => now()->subDay(), 'consumed_at' => now()->subDay()]);
        $run = GameRun::query()->create(['game_run_session_id' => $session->id, 'user_id' => $user->id, 'game_day_id' => $day->id, 'score' => 500, 'distance' => 500, 'duration_ms' => 12000, 'validation_status' => 'accepted', 'client_version' => config('daily_journey.version'), 'submitted_at' => now()->subDay(), 'accepted_at' => now()->subDay()]);
        GameDailyBest::query()->create(['game_day_id' => $day->id, 'user_id' => $user->id, 'game_run_id' => $run->id, 'score' => 500, 'distance' => 500, 'duration_ms' => 12000, 'accepted_at' => $run->accepted_at]);

        $service = app(FinalizationService::class);
        $service->deliver($service->finalize($day));
        $service->deliver($service->finalize($day));

        $this->assertDatabaseCount('game_daily_champions', 1);
        $this->assertDatabaseCount('notifications', 1);
        $this->assertSame(1, GameDailyChampion::query()->count());
    }
}
