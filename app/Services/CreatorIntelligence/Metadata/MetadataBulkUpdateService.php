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

    public function apply(array $ids, string $operation, mixed $value, string $mode, int $userId): void
    {
        DB::transaction(function () use ($ids, $operation, $value, $mode, $userId) {
            $videos = CreatorVideo::whereKey($ids)->lockForUpdate()->get();
            if ($videos->count() !== count(array_unique($ids))) {
                throw new InvalidArgumentException('One or more videos are unavailable.');
            }foreach ($videos as $video) {
                $this->applyOne($video, $operation, $value, $mode, $userId);
                $this->completion->recalculate($video->fresh());
            }Log::info('Creator Intelligence bulk metadata update.', ['operation' => $operation, 'video_count' => $videos->count(), 'user_id' => $userId, 'fields' => [$operation]]);
            $this->analyticsCache->invalidate();
        });
    }

    private function applyOne(CreatorVideo $video, string $operation, mixed $value, string $mode, int $userId): void
    {
        if (in_array($operation, ['assign_subject', 'assign_primary_subject'], true)) {
            $subject = Subject::findOrFail((int) $value);
            if ($subject->creator_channel_id !== $video->creator_channel_id) {
                throw new InvalidArgumentException('Bulk subjects must belong to every selected video channel.');
            }if ($mode === 'fill' && $operation === 'assign_primary_subject' && $video->primarySubject()->exists()) {
                return;
            }if ($operation === 'assign_primary_subject') {
                $video->subjects()->updateExistingPivot($video->subjects()->pluck('subjects.id'), ['is_primary' => false]);
            }$video->subjects()->syncWithoutDetaching([$subject->id => ['relationship_type' => $operation === 'assign_primary_subject' ? 'primary' : 'featured', 'is_primary' => $operation === 'assign_primary_subject']]);

            return;
        }
        if (in_array($operation, ['content_type', 'copyright_status', 'is_monetized'], true)) {
            if ($mode === 'fill' && $video->{$operation} !== null && ! ($operation === 'copyright_status' && $video->copyright_status->value === 'unknown')) {
                return;
            }$video->update([$operation => $value]);

            return;
        }
        if (in_array($operation, ['creator_sentiment', 'reaction_style'], true)) {
            $metadata = $video->editorialMetadata()->firstOrCreate();
            if ($mode === 'fill' && $metadata->{$operation} !== null) {
                return;
            }$metadata->update([$operation => $value, 'classified_by_user_id' => $userId, 'classified_at' => now()]);

            return;
        }
        if (str_starts_with($operation, 'review_')) {
            $relation = match ($operation) {
                'review_title' => 'titleMetadata','review_thumbnail' => 'thumbnailMetadata','review_editorial' => 'editorialMetadata'
            };
            $metadata = $video->{$relation}()->firstOrCreate();
            $metadata->update(['reviewed_at' => now(), 'reviewed_by_user_id' => $userId]);
        }
    }
}
