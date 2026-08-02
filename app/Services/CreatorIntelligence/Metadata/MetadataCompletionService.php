<?php

namespace App\Services\CreatorIntelligence\Metadata;

use App\Enums\MetadataCompletionStatus;
use App\Models\CreatorVideo;

class MetadataCompletionService
{
    public function calculate(CreatorVideo $video): array
    {
        $video->loadMissing(['primarySubject', 'primaryContentItem', 'titleMetadata', 'thumbnailMetadata', 'editorialMetadata']);
        $score = 0;
        if ($video->primarySubject->isNotEmpty()) {
            $score += 20;
        } if ($video->primaryContentItem->isNotEmpty()) {
            $score += 15;
        }
        if ($video->titleMetadata?->reviewed_at && $video->titleMetadata?->title_template) {
            $score += 20;
        }
        if ($video->thumbnailMetadata?->reviewed_at) {
            $score += 25;
        }
        if ($video->editorialMetadata?->reviewed_at && $video->editorialMetadata?->creator_sentiment && $video->editorialMetadata?->reaction_style) {
            $score += 15;
        }
        if ($video->copyright_status->value !== 'unknown' && $video->is_monetized !== null) {
            $score += 5;
        }
        $status = $score === 0 ? MetadataCompletionStatus::NotStarted : ($score === 100 ? MetadataCompletionStatus::Complete : MetadataCompletionStatus::InProgress);

        return ['percentage' => $score, 'status' => $status->value];
    }

    public function recalculate(CreatorVideo $video): array
    {
        $result = $this->calculate($video);
        $video->forceFill(['metadata_completion_percentage' => $result['percentage'], 'metadata_completion_status' => $result['status'], 'metadata_completion_calculated_at' => now()])->saveQuietly();

        return $result;
    }
}
