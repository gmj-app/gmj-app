<?php

namespace App\Services;

use App\Models\Recommendation;

class RequestIdentityComparator
{
    /** @var list<string> */
    public const IMMUTABLE_INPUTS = [
        'creator_id', 'creator', 'submitted_by', 'submission_source',
        'recommendation_type', 'type', 'media_type', 'youtube_url', 'url',
        'normalized_url', 'youtube_video_id', 'video_id', 'youtube_playlist_id', 'playlist_id',
        'source_title', 'source_channel', 'channel_title', 'thumbnail_url', 'source_metadata',
        'title', 'topic', 'description', 'status', 'moderation_status', 'published_at',
        'published_reaction_url', 'published_title', 'votes', 'vote_count', 'is_pinned',
        'category', 'artist', 'tags', 'created_at', 'updated_at',
    ];

    /** @param array<string, mixed> $input */
    public function containsIdentityInput(array $input): bool
    {
        return collect(self::IMMUTABLE_INPUTS)->contains(fn (string $key): bool => array_key_exists($key, $input));
    }

    /** @param array<string, mixed> $proposed */
    public function isSameIdentity(Recommendation $recommendation, array $proposed): bool
    {
        $current = [
            'creator_id' => $recommendation->creator_id,
            'recommendation_type' => $recommendation->recommendation_type,
            'media_type' => $recommendation->media_type,
            'normalized_url' => $recommendation->normalized_url,
            'youtube_video_id' => $recommendation->youtube_video_id,
            'youtube_playlist_id' => $recommendation->youtube_playlist_id,
            'title' => $recommendation->title,
        ];

        foreach ($proposed as $key => $value) {
            if (array_key_exists($key, $current) && (string) ($current[$key] ?? '') !== (string) ($value ?? '')) {
                return false;
            }
        }

        return true;
    }
}
