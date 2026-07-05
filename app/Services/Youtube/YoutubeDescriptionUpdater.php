<?php

namespace App\Services\Youtube;

use App\Models\User;
use App\Models\VideoToolAuditLog;
use App\Models\YoutubeChannelToken;
use App\Models\YoutubeDescriptionBackup;
use Throwable;

class YoutubeDescriptionUpdater
{
    public function __construct(
        private readonly YoutubeApiClient $client,
    ) {}

    public function applyChange(
        User $user,
        YoutubeChannelToken $token,
        DescriptionChange $change,
        string $operationBatchId,
    ): void {
        if (! $change->changed()) {
            $this->audit($user, $change, $operationBatchId, 'skipped', $change->message);

            return;
        }

        YoutubeDescriptionBackup::query()->create([
            'user_id' => $user->id,
            'video_id' => $change->videoId,
            'video_title' => $change->videoTitle,
            'original_description' => $change->oldDescription,
            'new_description' => $change->newDescription,
            'operation_batch_id' => $operationBatchId,
        ]);

        try {
            $video = $this->client->videoSnippet($token, $change->videoId);
            $snippet = $video->snippet;

            $snippet['title'] = $snippet['title'] ?? $change->videoTitle;
            $snippet['categoryId'] = $snippet['categoryId'] ?? '';

            $this->client->updateDescription($token, $change->videoId, $snippet, $change->newDescription);
            $this->audit($user, $change, $operationBatchId, 'updated');
        } catch (Throwable $throwable) {
            $this->audit($user, $change, $operationBatchId, 'failed', $throwable->getMessage());
        }
    }

    private function audit(
        User $user,
        DescriptionChange $change,
        string $operationBatchId,
        string $status,
        ?string $message = null,
    ): void {
        VideoToolAuditLog::query()->create([
            'user_id' => $user->id,
            'operation_batch_id' => $operationBatchId,
            'video_id' => $change->videoId,
            'video_title' => $change->videoTitle,
            'action' => $change->action,
            'status' => $status,
            'message' => $message,
            'metadata' => [
                'old_description_hash' => hash('sha256', $change->oldDescription),
                'new_description_hash' => hash('sha256', $change->newDescription),
            ],
        ]);
    }
}
