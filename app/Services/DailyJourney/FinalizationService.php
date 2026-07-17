<?php

namespace App\Services\DailyJourney;

use App\Models\GameDailyChampion;
use App\Models\GameDay;
use App\Notifications\BaseDatabaseNotification;
use App\Services\Accolades\AccoladeEvaluationService;
use App\Services\NotificationDispatchService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FinalizationService
{
    public function __construct(private AccoladeEvaluationService $accolades, private NotificationDispatchService $notifications, private GameDayService $days, private AccessService $access) {}

    public function finalize(GameDay $day): ?GameDailyChampion
    {
        return DB::transaction(function () use ($day) {
            $day = GameDay::query()->lockForUpdate()->findOrFail($day->id);
            if ($day->status === 'finalized') {
                return GameDailyChampion::query()->where('game_day_id', $day->id)->first();
            }$day->update(['status' => 'finalizing']);
            $best = $day->dailyBests()->with('user')->orderByDesc('score')->orderByDesc('distance')->orderBy('accepted_at')->orderBy('id')->get()
                ->first(function ($candidate) use ($day): bool {
                    $allowed = ! $this->access->isPrivate() || $this->access->allows($candidate->user);
                    if (! $allowed) {
                        Log::notice('Excluded unauthorized user from private Daily Journey finalization', ['game_day_id' => $day->id, 'user_id' => $candidate->user_id, 'game_run_id' => $candidate->game_run_id]);
                    }

                    return $allowed;
                });
            $champion = null;
            if ($best) {
                $champion = GameDailyChampion::query()->firstOrCreate(['game_day_id' => $day->id], ['user_id' => $best->user_id, 'game_run_id' => $best->game_run_id, 'local_date' => $day->local_date, 'score' => $best->score, 'distance' => $best->distance, 'finalized_at' => now()]);
                $day->update(['winner_user_id' => $best->user_id, 'winner_run_id' => $best->game_run_id, 'winner_score' => $best->score]);
            }$day->update(['status' => 'finalized', 'finalized_at' => now()]);
            Log::info('Daily Journey game day finalized', ['game_day_id' => $day->id, 'champion_id' => $champion?->id]);
            Cache::forget('daily-journey:champions');

            return $champion;
        });
    }

    public function deliver(?GameDailyChampion $champion): void
    {
        if (! $champion || $champion->notification_sent_at) {
            return;
        }
        if ($this->access->isPrivate() && ! $this->access->allows($champion->user)) {
            Log::notice('Skipped private Daily Journey award and notification for unauthorized user', ['game_daily_champion_id' => $champion->id, 'user_id' => $champion->user_id]);

            return;
        }
        $result = $this->accolades->evaluateGuide($champion->user, [config('daily_journey.accolade_track')], ['event_type' => 'game.daily_champion_awarded', 'event_id' => $champion->id, 'suppress_notifications' => true]);
        $earned = $result->newAwards->last()?->definition()['name'] ?? null;
        $this->notifications->send($champion->user, new BaseDatabaseNotification('game.daily_champion_awarded:'.$champion->id, 'You won today’s Daily Journey Challenge!', 'You finished #1 with '.number_format($champion->score).' points and earned Daily Challenge progress.', 'achievement', 'guide', route('game.leaderboard', ['tab' => 'champions']), 'View Daily Champions', 'trophy', 'success', ['metadata' => ['game_day_id' => $champion->game_day_id, 'score' => $champion->score, 'date' => $champion->local_date->toDateString(), 'accolade_key' => $earned]]));
        $champion->update(['notification_sent_at' => now()]);
    }
}
