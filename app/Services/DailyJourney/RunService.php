<?php

namespace App\Services\DailyJourney;

use App\Models\GameDailyBest;
use App\Models\GameRun;
use App\Models\GameRunSession;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class RunService
{
    public function __construct(private GameDayService $days, private RunValidationService $validator, private LeaderboardService $leaderboard) {}

    public function issue(User $user): GameRunSession
    {
        $day = $this->days->current();

        return GameRunSession::query()->create(['public_token' => (string) Str::uuid(), 'user_id' => $user->id, 'game_day_id' => $day->id, 'game_key' => config('daily_journey.key'), 'status' => 'issued', 'random_seed' => random_int(1, PHP_INT_MAX), 'game_version' => config('daily_journey.version'), 'issued_at' => now(), 'expires_at' => now()->addMinutes((int) config('daily_journey.session_minutes'))]);
    }

    public function start(GameRunSession $s, User $user): GameRunSession
    {
        $this->assertOwned($s, $user);
        if ($s->status !== 'issued' || $s->expires_at->isPast()) {
            throw ValidationException::withMessages(['run' => 'This run session is no longer available.']);
        }$s->update(['status' => 'started', 'started_at' => now()]);

        return $s;
    }

    public function finish(GameRunSession $s, User $user, array $data): GameRun
    {
        return DB::transaction(function () use ($s, $user, $data) {
            $s = GameRunSession::query()->lockForUpdate()->findOrFail($s->id);
            $this->assertOwned($s, $user);
            if (! in_array($s->status, ['issued', 'started'], true) || $s->consumed_at) {
                throw ValidationException::withMessages(['run' => 'This run has already been submitted.']);
            }if ($s->expires_at->isPast() || ($s->day->ends_at->copy()->addMinutes((int) config('daily_journey.grace_minutes'))->isPast())) {
                throw ValidationException::withMessages(['run' => 'Your run expired before it could be submitted.']);
            }if ($data['client_version'] !== $s->game_version || ! in_array($data['client_version'], config('daily_journey.supported_versions'), true)) {
                throw ValidationException::withMessages(['client_version' => 'Please reload to use the current game version.']);
            }[$status,$flags,$score] = $this->validator->validate($data, $s);
            $run = GameRun::query()->create(['game_run_session_id' => $s->id, 'user_id' => $user->id, 'game_day_id' => $s->game_day_id, 'score' => $score, 'distance' => $data['distance'], 'duration_ms' => $data['duration_ms'], 'collectible_count' => $data['collectible_count'], 'powerup_pickup_count' => $data['powerup_pickup_count'], 'powerup_use_count' => $data['powerup_use_count'], 'maximum_speed_tier' => $data['maximum_speed_tier'], 'event_digest' => $data['events'] ?? null, 'validation_status' => $status, 'validation_flags' => $flags ?: null, 'rejection_reason' => $status === 'accepted' ? null : implode(', ', $flags), 'client_version' => $data['client_version'], 'submitted_at' => now(), 'accepted_at' => $status === 'accepted' ? now() : null]);
            $s->update(['status' => $status, 'submitted_at' => now(), 'consumed_at' => now()]);
            if ($status === 'accepted') {
                $this->project($run);
            }

            return $run;
        });
    }

    public function project(GameRun $run): void
    {
        $current = GameDailyBest::query()->where(['game_day_id' => $run->game_day_id, 'user_id' => $run->user_id])->lockForUpdate()->first();
        $better = ! $current || [$run->score, $run->distance, -$run->accepted_at->timestamp] > [$current->score, $current->distance, -$current->accepted_at->timestamp];
        if ($better) {
            GameDailyBest::query()->updateOrCreate(['game_day_id' => $run->game_day_id, 'user_id' => $run->user_id], ['game_run_id' => $run->id, 'score' => $run->score, 'distance' => $run->distance, 'duration_ms' => $run->duration_ms, 'accepted_at' => $run->accepted_at]);
        }$this->leaderboard->forget($run->day);
    }

    public function recalculateBest(int $dayId, int $userId): void
    {
        $best = GameRun::query()->where('user_id', $userId)->where('game_day_id', $dayId)->where('validation_status', 'accepted')->whereNull('invalidated_at')->orderByDesc('score')->orderByDesc('distance')->orderBy('accepted_at')->first();
        GameDailyBest::query()->where(['game_day_id' => $dayId, 'user_id' => $userId])->delete();
        if ($best) {
            $this->project($best);
        }
    }

    private function assertOwned(GameRunSession $s, User $u): void
    {
        if ($s->user_id !== $u->id) {
            abort(404);
        }
    }
}
