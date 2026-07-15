<?php

namespace App\Services;

use App\Exceptions\CreatorProfileUpdateException;
use App\Models\Creator;
use Illuminate\Support\Facades\DB;
use Throwable;

class CreatorProfileUpdateService
{
    public function __construct(private readonly CreatorTagService $tags, private readonly CreatorMediaReplacementService $media, private readonly CreatorCacheInvalidator $cache) {}

    /** @return array{before:array,after:array,assets:array<int,string>} */
    public function update(Creator $creator, array $validated, array $files = []): array
    {
        $publicFields = ['display_name', 'slug', 'youtube_channel_url', 'bio', 'submission_instructions', 'submissions_open', 'recommendation_approval_mode', 'avatar_path', 'hero_path'];
        $before = $creator->only($publicFields);
        try {
            $replacement = $this->media->stage($creator, $files);
        } catch (Throwable $exception) {
            throw new CreatorProfileUpdateException('media_staging', $exception);
        }
        $newPaths = $replacement['newPaths'];
        $oldPaths = $replacement['oldPaths'];
        $assets = $replacement['assets'];

        $updates = [...collect($validated)->only($publicFields)->all(), ...$replacement['updates']];
        $updates['channel_url'] = $validated['youtube_channel_url'] ?? null;
        $updates['submissions_open'] = (bool) $validated['submissions_open'];

        try {
            $stage = 'database_update';
            DB::transaction(function () use ($creator, $updates, $validated, &$stage): void {
                $creator->forceFill($updates)->save();
                if (array_key_exists('tags', $validated)) {
                    $stage = 'tag_synchronization';
                    $wanted = $this->tags->resolve($creator, explode(',', (string) $validated['tags']))->pluck('id');
                    $creator->creatorTags()->whereNotIn('id', $wanted)->whereDoesntHave('recommendations')->delete();
                }
            });
        } catch (Throwable $exception) {
            try {
                $this->media->delete($newPaths);
            } catch (Throwable $cleanupException) {
                report($cleanupException);
            }

            throw new CreatorProfileUpdateException($stage, $exception);
        }

        // The replacement has committed. Cleanup and cache failures must not turn a
        // successful profile save into a misleading rollback message.
        try {
            $this->media->delete($oldPaths);
        } catch (Throwable $exception) {
            report(new CreatorProfileUpdateException('old_media_cleanup', $exception));
        }
        $creator->refresh();
        try {
            $this->cache->forget($creator);
        } catch (Throwable $exception) {
            report(new CreatorProfileUpdateException('cache_invalidation', $exception));
        }

        return ['before' => $before, 'after' => $creator->only($publicFields), 'assets' => $assets];
    }
}
