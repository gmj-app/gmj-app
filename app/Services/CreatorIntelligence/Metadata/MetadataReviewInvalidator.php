<?php

namespace App\Services\CreatorIntelligence\Metadata;

use App\Models\CreatorVideo;

class MetadataReviewInvalidator
{
    public function __construct(private readonly TitleMetadataParser $titles, private readonly MetadataCompletionService $completion) {}

    public function apply(CreatorVideo $video, bool $titleChanged, bool $thumbnailChanged): void
    {
        if ($titleChanged) {
            if ($video->titleMetadata()->exists()) {
                $this->titles->recalculate($video);
                $video->titleMetadata()->update(['reviewed_at' => null, 'reviewed_by_user_id' => null]);
            }
        }
        if ($thumbnailChanged && $video->thumbnailMetadata()->exists()) {
            $video->thumbnailMetadata()->update(['reviewed_at' => null, 'reviewed_by_user_id' => null]);
        }
        if ($titleChanged || $thumbnailChanged) {
            $this->completion->recalculate($video->fresh());
        }
    }
}
