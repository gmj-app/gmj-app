<?php

namespace Tests\Feature;

use App\Models\GameDailyBest;
use App\Models\GameDailyChampion;
use App\Models\GameDay;
use App\Models\GameRun;
use App\Models\GameRunSession;
use App\Models\User;
use App\Notifications\BaseDatabaseNotification;
use App\Services\DailyJourney\FinalizationService;
use App\Services\DailyJourney\GameDayService;
use App\Services\DailyJourney\RunService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Tests\TestCase;

class DailyJourneyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['daily_journey.public_enabled' => false, 'super_admin.emails' => ['admin@example.com']]);
    }

    public function test_guest_cannot_view_leaderboard_or_issue_a_run(): void
    {
        $this->get(route('game.leaderboard'))->assertRedirect(route('login'));
        $this->postJson(route('game.runs.issue'))->assertUnauthorized();
    }

    public function test_game_is_absent_from_unauthorized_homepages_and_visible_to_a_super_admin(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertDontSee('Daily Journey Challenge')
            ->assertDontSee('data-daily-journey', false)
            ->assertDontSee(route('game.runs.issue'));

        $ordinary = User::factory()->create();
        $this->actingAs($ordinary)->get(route('home'))->assertOk()->assertDontSee('Daily Journey Challenge');

        $admin = User::factory()->create(['email' => ' ADMIN@example.com ']);
        $this->actingAs($admin)->get(route('home'))
            ->assertOk()
            ->assertSee('Preparing your run')
            ->assertSee('Start run')
            ->assertSee('Pause run (P or Escape)')
            ->assertSee('Shield')
            ->assertSee('EMPTY')
            ->assertSee('Tap')
            ->assertSee('Hold')
            ->assertDontSee('Landscape is recommended.');
    }

    public function test_authenticated_non_admin_receives_not_found_for_all_game_endpoints(): void
    {
        $user = User::factory()->create(['public_profile_completed_at' => now()]);
        $this->actingAs($user)->get(route('game.leaderboard'))->assertNotFound();
        $this->getJson(route('game.today'))->assertNotFound();
        $this->getJson(route('game.champions'))->assertNotFound();
        $this->postJson(route('game.runs.issue'))->assertNotFound();
    }

    public function test_run_service_rechecks_access_without_route_middleware(): void
    {
        $this->expectException(NotFoundHttpException::class);

        app(RunService::class)->issue(User::factory()->create(['email' => 'ordinary@example.com']));
    }

    public function test_existing_game_notifications_are_hidden_from_unauthorized_users(): void
    {
        $user = User::factory()->create(['email' => 'ordinary@example.com']);
        $user->notify(new BaseDatabaseNotification('game.daily_champion_awarded:99', 'Private game winner', 'Private game details'));
        $user->notify(new BaseDatabaseNotification('account.welcome:99', 'Visible account update', 'Normal details'));

        $this->actingAs($user)->get(route('notifications.index'))
            ->assertOk()
            ->assertSee('Visible account update')
            ->assertDontSee('Private game winner');
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Visible account update')
            ->assertDontSee('Private game winner');
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
        $user = User::factory()->create(['email' => 'admin@example.com', 'public_display_name' => 'Runner', 'public_handle' => 'runner', 'public_profile_completed_at' => now()]);
        $issue = $this->actingAs($user)->postJson(route('game.runs.issue'))->assertCreated()->json();
        $payload = ['score' => 375, 'distance' => 375, 'duration_ms' => 10000, 'collectible_count' => 0, 'powerup_pickup_count' => 0, 'powerup_use_count' => 0, 'maximum_speed_tier' => 1, 'client_version' => config('daily_journey.version'), 'events' => [['t' => 0, 'e' => 'run_started']]];

        $this->postJson(route('game.runs.finish', $issue['token']), $payload)->assertOk()->assertJsonPath('status', 'accepted');
        $this->postJson(route('game.runs.finish', $issue['token']), $payload)->assertUnprocessable();

        $this->assertDatabaseCount('game_runs', 1);
        $this->assertSame(375, GameDailyBest::query()->sole()->score);
    }

    public function test_score_tampering_is_rejected_and_does_not_rank(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com', 'public_display_name' => 'Runner', 'public_handle' => 'runner', 'public_profile_completed_at' => now()]);
        $token = $this->actingAs($user)->postJson(route('game.runs.issue'))->json('token');
        $payload = ['score' => 999999, 'distance' => 375, 'duration_ms' => 10000, 'collectible_count' => 0, 'powerup_pickup_count' => 0, 'powerup_use_count' => 0, 'maximum_speed_tier' => 1, 'client_version' => config('daily_journey.version')];

        $this->postJson(route('game.runs.finish', $token), $payload)->assertUnprocessable()->assertJsonPath('status', 'rejected');
        $this->assertDatabaseCount('game_daily_bests', 0);
        $this->assertSame('rejected', GameRun::query()->sole()->validation_status);
    }

    public function test_finalization_is_idempotent_and_notifies_one_champion(): void
    {
        $user = User::factory()->create(['email' => 'admin@example.com']);
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

    public function test_private_finalization_excludes_unauthorized_stale_best(): void
    {
        $ordinary = User::factory()->create(['email' => 'ordinary@example.com']);
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $day = GameDay::factory()->create(['status' => 'open']);

        foreach ([[$ordinary, 900], [$admin, 500]] as [$user, $score]) {
            $session = GameRunSession::factory()->create(['game_day_id' => $day->id, 'user_id' => $user->id]);
            $run = GameRun::factory()->create(['game_run_session_id' => $session->id, 'game_day_id' => $day->id, 'user_id' => $user->id, 'score' => $score, 'distance' => $score, 'validation_status' => 'accepted', 'accepted_at' => now()]);
            GameDailyBest::factory()->create(['game_run_id' => $run->id, 'game_day_id' => $day->id, 'user_id' => $user->id, 'score' => $score, 'distance' => $score, 'accepted_at' => now()]);
        }

        $champion = app(FinalizationService::class)->finalize($day);

        $this->assertSame($admin->id, $champion?->user_id);
    }
}
