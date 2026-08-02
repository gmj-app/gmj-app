<?php

namespace App\Services\CreatorIntelligence\Import;

use App\Models\CreatorVideo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Str;
use InvalidArgumentException;

class YoutubeVideoIdentityService
{
    public function validPlatformId(mixed $value): ?string
    {
        $value = trim((string) $value);
        if ($value === '' || str_starts_with($value, 'fingerprint:')) {
            return null;
        }
        if (! preg_match('/^[A-Za-z0-9_-]{6,32}$/', $value)) {
            throw new InvalidArgumentException('The YouTube Video value is not a valid platform video ID.');
        }

        return $value;
    }

    public function findFingerprintMatch(int $channelId, string $title, CarbonImmutable $published): ?CreatorVideo
    {
        $normalizedTitle = $this->normalizedTitle($title);
        $matches = CreatorVideo::query()->where('creator_channel_id', $channelId)
            ->where('platform_video_id', 'like', 'fingerprint:%')
            ->whereDate('published_at', $published->toDateString())
            ->get()->filter(fn (CreatorVideo $candidate) => $this->normalizedTitle($candidate->title) === $normalizedTitle);
        if ($matches->count() > 1) {
            throw new InvalidArgumentException('Multiple fingerprint videos match the title and published date; the row is ambiguous.');
        }

        return $matches->first();
    }

    public function thumbnailUrl(string $platformId): string
    {
        return "https://i.ytimg.com/vi/{$platformId}/hqdefault.jpg";
    }

    public function normalizedTitle(string $title): string
    {
        return Str::lower(trim(preg_replace('/\s+/u', ' ', $title) ?? $title));
    }
}
