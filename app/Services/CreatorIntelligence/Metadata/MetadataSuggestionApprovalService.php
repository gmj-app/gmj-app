<?php

namespace App\Services\CreatorIntelligence\Metadata;

use App\Models\ContentItem;
use App\Models\CreatorVideo;
use App\Models\MetadataSuggestion;
use App\Services\CreatorIntelligence\Analytics\AnalyticsCache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class MetadataSuggestionApprovalService
{
    public function __construct(private readonly MetadataCompletionService $completion, private readonly AnalyticsCache $cache, private readonly NameNormalizer $names) {}

    public function approve(array $ids, int $userId, bool $replacePrimary = false): int
    {
        $updated = DB::transaction(function () use ($ids, $userId, $replacePrimary): int {
            $suggestions = MetadataSuggestion::with(['video', 'suggestedSubject', 'suggestedContentItem'])->whereKey($ids)->lockForUpdate()->get();
            if ($suggestions->count() !== count(array_unique($ids))) {
                throw new InvalidArgumentException('One or more suggestions are unavailable.');
            }
            foreach ($suggestions as $suggestion) {
                $video = $suggestion->video;
                if ($suggestion->suggestion_type === 'subject') {
                    $subject = $suggestion->suggestedSubject ?? throw new InvalidArgumentException('Subject suggestions cannot create arbitrary subjects.');
                    if ($subject->creator_channel_id !== $video->creator_channel_id) {
                        throw new InvalidArgumentException('Cross-channel subject assignment is not allowed.');
                    }
                    $primary = ! $video->primarySubject()->exists() || $replacePrimary;
                    if ($primary && $replacePrimary) {
                        $video->primarySubject()->get()->each(fn ($current) => $video->subjects()->updateExistingPivot($current->id, ['is_primary' => false, 'relationship_type' => 'featured']));
                    }
                    $video->subjects()->syncWithoutDetaching([$subject->id => ['relationship_type' => $primary ? 'primary' : 'featured', 'is_primary' => $primary]]);
                } else {
                    $item = $suggestion->suggestedContentItem;
                    if (! $item) {
                        $name = $this->names->display((string) $suggestion->suggested_display_value);
                        if ($name === '') {
                            throw new InvalidArgumentException('A content item name is required.');
                        }
                        $item = ContentItem::firstOrCreate(['creator_channel_id' => $video->creator_channel_id, 'normalized_name' => $this->names->normalize($name)], ['name' => $name, 'slug' => $this->uniqueSlug($video->creator_channel_id, $name), 'is_active' => true]);
                        $suggestion->suggested_content_item_id = $item->id;
                    }
                    if ($item->creator_channel_id !== $video->creator_channel_id) {
                        throw new InvalidArgumentException('Cross-channel content-item assignment is not allowed.');
                    }
                    $primary = ! $video->primaryContentItem()->exists() || $replacePrimary;
                    if ($primary && $replacePrimary) {
                        $video->primaryContentItem()->get()->each(fn ($current) => $video->contentItems()->updateExistingPivot($current->id, ['is_primary' => false]));
                    }
                    $video->contentItems()->syncWithoutDetaching([$item->id => ['is_primary' => $primary]]);
                    $video->forceFill(['content_item_not_applicable' => false, 'content_item_not_applicable_by_user_id' => null, 'content_item_not_applicable_at' => null])->save();
                }
                $suggestion->forceFill(['status' => 'applied', 'reviewed_by_user_id' => $userId, 'reviewed_at' => now()])->save();
                $this->completion->recalculate($video->fresh());
            }

            return $suggestions->count();
        });
        if ($updated) {
            $this->cache->invalidate();
        }

        return $updated;
    }

    public function reject(array $ids, int $userId): int
    {
        return MetadataSuggestion::whereKey($ids)->where('status', 'pending')->update(['status' => 'rejected', 'reviewed_by_user_id' => $userId, 'reviewed_at' => now(), 'updated_at' => now()]);
    }

    public function markNotApplicable(array $videoIds, int $userId): int
    {
        return DB::transaction(function () use ($videoIds, $userId): int {
            $videos = CreatorVideo::whereKey($videoIds)->lockForUpdate()->get();
            foreach ($videos as $video) {
                $video->forceFill(['content_item_not_applicable' => true, 'content_item_not_applicable_by_user_id' => $userId, 'content_item_not_applicable_at' => now()])->save();
                $this->completion->recalculate($video->fresh());
            }

            return $videos->count();
        });
    }

    private function uniqueSlug(int $channelId, string $name): string
    {
        $base = Str::slug($name) ?: 'content-item';
        $slug = $base;
        $i = 2;
        while (ContentItem::where('creator_channel_id', $channelId)->where('slug', $slug)->exists()) {
            $slug = $base.'-'.$i++;
        }

        return $slug;
    }
}
