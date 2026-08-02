<?php

namespace App\Services\CreatorIntelligence\Metadata;

use App\Enums\SubjectRelationshipType;
use App\Models\ContentItem;
use App\Models\CreatorVideo;
use App\Models\Subject;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class VideoClassificationService
{
    public function __construct(private readonly MetadataCompletionService $completion) {}

    public function subjects(CreatorVideo $video, array $rows): void
    {
        DB::transaction(function () use ($video, $rows) {
            $video = CreatorVideo::lockForUpdate()->findOrFail($video->id);
            $sync = [];
            $primary = 0;
            foreach ($rows as $row) {
                $subject = Subject::findOrFail($row['id']);
                if ($subject->creator_channel_id !== $video->creator_channel_id) {
                    throw new InvalidArgumentException('Subjects must belong to the video channel.');
                }$isPrimary = (bool) ($row['is_primary'] ?? false);
                $primary += $isPrimary ? 1 : 0;
                $type = $isPrimary ? 'primary' : ($row['relationship_type'] ?? 'featured');
                if (! SubjectRelationshipType::tryFrom($type)) {
                    throw new InvalidArgumentException('Invalid subject relationship type.');
                }$sync[$subject->id] = ['relationship_type' => $type, 'is_primary' => $isPrimary];
            }if ($primary > 1) {
                throw new InvalidArgumentException('Only one primary subject is allowed.');
            }$video->subjects()->sync($sync);
            $this->completion->recalculate($video->fresh());
        });
    }

    public function contentItems(CreatorVideo $video, array $rows): void
    {
        DB::transaction(function () use ($video, $rows) {
            $video = CreatorVideo::lockForUpdate()->findOrFail($video->id);
            $sync = [];
            $primary = 0;
            foreach ($rows as $row) {
                $item = ContentItem::findOrFail($row['id']);
                if ($item->creator_channel_id !== $video->creator_channel_id) {
                    throw new InvalidArgumentException('Content items must belong to the video channel.');
                }$isPrimary = (bool) ($row['is_primary'] ?? false);
                $primary += $isPrimary ? 1 : 0;
                $sync[$item->id] = ['is_primary' => $isPrimary];
            }if ($primary > 1) {
                throw new InvalidArgumentException('Only one primary content item is allowed.');
            }$video->contentItems()->sync($sync);
            $this->completion->recalculate($video->fresh());
        });
    }
}
