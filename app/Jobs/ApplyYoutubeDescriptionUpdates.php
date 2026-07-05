<?php

namespace App\Jobs;

use App\Models\User;
use App\Models\YoutubeChannelToken;
use App\Services\Youtube\DescriptionChange;
use App\Services\Youtube\YoutubeDescriptionUpdater;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ApplyYoutubeDescriptionUpdates implements ShouldQueue
{
    use Queueable;

    /**
     * @param  array<int, array<string, string|null>>  $changes
     */
    public function __construct(
        public readonly int $userId,
        public readonly array $changes,
        public readonly string $operationBatchId,
    ) {}

    public function handle(YoutubeDescriptionUpdater $updater): void
    {
        $user = User::query()->findOrFail($this->userId);
        $token = YoutubeChannelToken::query()->where('user_id', $user->id)->firstOrFail();

        foreach ($this->changes as $change) {
            $updater->applyChange($user, $token, DescriptionChange::fromArray($change), $this->operationBatchId);
        }
    }
}
