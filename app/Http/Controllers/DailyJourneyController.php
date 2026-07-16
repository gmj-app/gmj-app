<?php

namespace App\Http\Controllers;

use App\Http\Requests\FinishGameRunRequest;
use App\Models\GameRunSession;
use App\Services\DailyJourney\GameDayService;
use App\Services\DailyJourney\LeaderboardService;
use App\Services\DailyJourney\RunService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DailyJourneyController extends Controller
{
    public function __construct(private GameDayService $days, private LeaderboardService $leaderboard) {}

    public function leaderboard(Request $request): View
    {
        $day = $this->days->current();
        $data = $this->leaderboard->rows($day, $request->user()?->id);

        return view('game.leaderboard', ['day' => $day, 'leaderboard' => $data, 'champions' => $this->leaderboard->champions()]);
    }

    public function today(Request $request): JsonResponse
    {
        $day = $this->days->current();

        return response()->json($this->payload($day, $request));
    }

    public function champions(): JsonResponse
    {
        return response()->json(['champions' => $this->leaderboard->champions()]);
    }

    public function issue(Request $request, RunService $runs): JsonResponse
    {
        $s = $runs->issue($request->user());

        return response()->json(['token' => $s->public_token, 'seed' => (string) $s->random_seed, 'version' => $s->game_version, 'expires_at' => $s->expires_at->toIso8601String(), 'config' => $this->clientConfig()], 201);
    }

    public function start(Request $request, GameRunSession $session, RunService $runs): JsonResponse
    {
        $runs->start($session, $request->user());

        return response()->json(['started_at' => $session->fresh()->started_at->toIso8601String()]);
    }

    public function finish(FinishGameRunRequest $request, GameRunSession $session, RunService $runs): JsonResponse
    {
        $run = $runs->finish($session, $request->user(), $request->validated());
        $day = $session->day;
        $rank = $this->leaderboard->rows($day, $request->user()->id)['me'];

        return response()->json(['status' => $run->validation_status, 'score' => $run->score, 'distance' => $run->distance, 'flags' => $run->validation_status === 'accepted' ? [] : null, 'personal_best' => $rank && $rank['score'] === $run->score, 'rank' => $rank, 'reference' => 'DJ'.str_pad((string) $run->id, 8, '0', STR_PAD_LEFT)], $run->validation_status === 'rejected' ? 422 : 200);
    }

    private function payload($day, Request $request): array
    {
        $board = $this->leaderboard->rows($day, $request->user()?->id);

        return ['title' => config('daily_journey.title'), 'game_day' => $day->local_date->toDateString(), 'timezone' => $day->timezone, 'ends_at' => $day->ends_at->toIso8601String(), 'leader' => $board['rows'][0] ?? null, 'me' => $board['me'], 'version' => config('daily_journey.version')];
    }

    private function clientConfig(): array
    {
        return ['width' => 1280, 'height' => 720, 'startingSpeed' => config('daily_journey.starting_speed'), 'maximumSpeed' => config('daily_journey.maximum_speed'), 'acceleration' => config('daily_journey.acceleration_per_second'), 'minGap' => config('daily_journey.minimum_obstacle_gap'), 'maxGap' => config('daily_journey.maximum_obstacle_gap'), 'collectibleBonus' => config('daily_journey.collectible_bonus'), 'ui' => [
            'countdownStepMs' => config('daily_journey.ui.countdown_step_ms'),
            'toastDurationMs' => config('daily_journey.ui.toast_duration_ms'),
            'toastQueueLimit' => config('daily_journey.ui.toast_queue_limit'),
            'hudUpdateMs' => config('daily_journey.ui.hud_update_ms'),
            'shieldBrokenMs' => config('daily_journey.ui.shield_broken_ms'),
            'scoreMilestones' => config('daily_journey.ui.score_milestones'),
            'resetWarningMinutes' => config('daily_journey.ui.reset_warning_minutes'),
            'overlayMaxWidth' => config('daily_journey.ui.overlay_max_width'),
        ]];
    }
}
