<?php

namespace App\Services\CreatorIntelligence\Metadata;

use App\Models\CreatorVideo;
use App\Models\Subject;
use App\Services\CreatorIntelligence\Analytics\AnalyticsCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class MetadataBulkUpdateService
{
    public function __construct(private readonly MetadataCompletionService $completion, private readonly AnalyticsCache $analyticsCache) {}

    /** @return array{updated: int, skipped: int} */
    public function apply(array $ids, string $operation, mixed $value, string $mode, int $userId): array
    {
        $result = DB::transaction(function () use ($ids, $operation, $value, $mode, $userId): array {
            $videos = CreatorVideo::whereKey($ids)->lockForUpdate()->get();
            if ($videos->count() !== count(array_unique($ids))) {
                throw new InvalidArgumentException('One or more videos are unavailable.');
            }

            $updated = 0;
            foreach ($videos as $video) {
                if (! $this->applyOne($video, $operation, $value, $mode, $userId)) {
                    continue;
                }
                $this->completion->recalculate($video->fresh());
                $updated++;
            }

            Log::info('Creator Intelligence bulk metadata update.', ['operation' => $operation, 'video_count' => $videos->count(), 'updated_count' => $updated, 'skipped_count' => $videos->count() - $updated, 'user_id' => $userId, 'fields' => [$operation]]);

            return ['updated' => $updated, 'skipped' => $videos->count() - $updated];
        });

        if ($result['updated'] > 0) {
            $this->analyticsCache->invalidate();
        }

        return $result;
    }

    private function applyOne(CreatorVideo $video, string $operation, mixed $value, string $mode, int $userId): bool
    {
        if (in_array($operation, ['assign_subject', 'assign_primary_subject'], true)) {
            $subject = Subject::findOrFail((int) $value);
            if ($subject->creator_channel_id !== $video->creator_channel_id) {
                throw new InvalidArgumentException('Bulk subjects must belong to every selected video channel.');
            }

            $current = $video->subjects()->whereKey($subject->id)->first();
            if ($operation === 'assign_primary_subject') {
                if ($mode === 'fill' && $video->primarySubject()->exists()) {
                    return false;
                }
                if ($current?->pivot->is_primary) {
                    return false;
                }
                $video->subjects()->wherePivot('is_primary', true)->get()->each(
                    fn (Subject $primary) => $video->subjects()->updateExistingPivot($primary->id, ['relationship_type' => 'featured', 'is_primary' => false])
                );
                $video->subjects()->syncWithoutDetaching([$subject->id => ['relationship_type' => 'primary', 'is_primary' => true]]);

                return true;
            }

            if (($mode === 'fill' && $video->subjects()->exists()) || $current) {
                return false;
            }
            $video->subjects()->syncWithoutDetaching([$subject->id => ['relationship_type' => 'featured', 'is_primary' => false]]);

            return true;
        }

        if ($operation === 'subject_relationship_type') {
            $subjects = $video->subjects()->wherePivot('is_primary', false)->get();
            if ($subjects->isEmpty() || $mode === 'fill') {
                return false;
            }
            $changed = false;
            foreach ($subjects as $subject) {
                if ($subject->pivot->relationship_type === $value) {
                    continue;
                }
                $video->subjects()->updateExistingPivot($subject->id, ['relationship_type' => $value]);
                $changed = true;
            }

            return $changed;
        }

        if (in_array($operation, ['content_type', 'copyright_status', 'is_monetized'], true)) {
            $current = $operation === 'copyright_status' ? $video->copyright_status->value : $video->{$operation};
            $missing = $current === null || ($operation === 'copyright_status' && $current === 'unknown');
            $normalizedValue = $operation === 'is_monetized' ? filter_var($value, FILTER_VALIDATE_BOOL) : $value;
            if (($mode === 'fill' && ! $missing) || $current === $normalizedValue) {
                return false;
            }
            $video->update([$operation => $normalizedValue]);

            return true;
        }

        if (in_array($operation, ['creator_sentiment', 'reaction_style'], true)) {
            $metadata = $video->editorialMetadata()->firstOrCreate();
            $current = $metadata->{$operation}?->value ?? $metadata->{$operation};
            if (($mode === 'fill' && $current !== null) || $current === $value) {
                return false;
            }
            $metadata->update([$operation => $value, 'classified_by_user_id' => $userId, 'classified_at' => now()]);

            return true;
        }

        if (str_starts_with($operation, 'review_')) {
            $relation = match ($operation) {
                'review_title' => 'titleMetadata',
                'review_thumbnail' => 'thumbnailMetadata',
                'review_editorial' => 'editorialMetadata',
            };
            $metadata = $video->{$relation}()->firstOrCreate();
            if ($mode === 'fill' && $metadata->reviewed_at !== null) {
                return false;
            }
            $metadata->update(['reviewed_at' => now(), 'reviewed_by_user_id' => $userId]);

            return true;
        }

        return false;
    }
}
