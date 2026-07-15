<?php

namespace App\Services;

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
        $replacement = $this->media->stage($creator, $files);
        $newPaths = $replacement['newPaths'];
        $oldPaths = $replacement['oldPaths'];
        $assets = $replacement['assets'];

        $updates = [...collect($validated)->only($publicFields)->all(), ...$replacement['updates']];
        $updates['channel_url'] = $validated['youtube_channel_url'] ?? null;
        $updates['submissions_open'] = (bool) $validated['submissions_open'];

        try {
            DB::transaction(function () use ($creator, $updates, $validated): void {
                $creator->update($updates);
                if (array_key_exists('tags', $validated)) {
                    $wanted = $this->tags->resolve($creator, explode(',', (string) $validated['tags']))->pluck('id');
                    $creator->creatorTags()->whereNotIn('id', $wanted)->whereDoesntHave('recommendations')->delete();
                }
            });
        } catch (Throwable $e) {
            $this->media->delete($newPaths);
            throw $e;
        }

        $this->media->delete($oldPaths);
        $creator->refresh();
        $this->cache->forget($creator);

        return ['before' => $before, 'after' => $creator->only($publicFields), 'assets' => $assets];
    }
}
