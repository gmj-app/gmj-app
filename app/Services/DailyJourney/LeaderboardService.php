<?php

namespace App\Services\DailyJourney;

use App\Models\GameDailyBest;
use App\Models\GameDailyChampion;
use App\Models\GameDay;
use Illuminate\Support\Facades\Cache;

class LeaderboardService
{
    public function __construct(private readonly AccessService $access) {}

    public function rows(GameDay $day, ?int $userId = null): array
    {
        $all = Cache::remember(
            'daily-journey:leaderboard:'.$this->accessCacheKey().':'.$day->id,
            (int) config('daily_journey.cache_seconds'),
            fn () => GameDailyBest::query()
                ->where('game_day_id', $day->id)
                ->with('user')
                ->orderByDesc('score')
                ->orderByDesc('distance')
                ->orderBy('accepted_at')
                ->orderBy('id')
                ->get()
                ->filter(fn ($best) => ! $this->access->isPrivate() || $this->access->allows($best->user))
                ->values()
                ->map(fn ($best, $index) => $this->present($best, $index + 1))
                ->all(),
        );
        $rows = array_slice($all, 0, (int) config('daily_journey.leaderboard_size'));
        $me = null;
        if ($userId) {
            $bestId = GameDailyBest::query()->where(['game_day_id' => $day->id, 'user_id' => $userId])->value('game_run_id');
            $me = collect($all)->firstWhere('run_id', $bestId);
        }

        return compact('rows', 'me');
    }

    public function champions(): array
    {
        return Cache::remember('daily-journey:champions:'.$this->accessCacheKey(), (int) config('daily_journey.cache_seconds'), fn () => GameDailyChampion::query()->with('user')->latest('local_date')->limit(100)->get()->filter(fn ($champion) => ! $this->access->isPrivate() || $this->access->allows($champion->user))->values()->map(fn ($c) => ['date' => $c->local_date->toDateString(), 'score' => $c->score, 'distance' => $c->distance, 'guide' => $this->guide($c->user)])->all());
    }

    public function forget(GameDay $day): void
    {
        Cache::forget('daily-journey:leaderboard:'.$this->accessCacheKey().':'.$day->id);
        Cache::forget('daily-journey:champions:'.$this->accessCacheKey());
    }

    private function accessCacheKey(): string
    {
        return $this->access->isPrivate()
            ? 'private-'.sha1(implode('|', config('super_admin.emails', [])))
            : 'public';
    }

    private function present($best, int $rank): array
    {
        return ['rank' => $rank, 'run_id' => $best->game_run_id, 'score' => $best->score, 'distance' => $best->distance, 'guide' => $this->guide($best->user)];
    }

    private function guide($user): array
    {
        return ['name' => $user->publicName(), 'handle' => $user->formattedPublicHandle(), 'avatar_url' => $user->avatar_url, 'profile_url' => $user->publicGuideProfileUrl()];
    }
}
