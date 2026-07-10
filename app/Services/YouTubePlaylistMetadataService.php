<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Throwable;

class YouTubePlaylistMetadataService
{
    /**
     * @return array<string, mixed>
     */
    public function fetch(string $playlistId, bool $refresh = false): array
    {
        if (preg_match('/^[A-Za-z0-9_-]{10,100}$/', $playlistId) !== 1) {
            return $this->unavailable($playlistId, 'invalid_playlist_id');
        }

        $key = 'youtube-playlist:'.hash('sha256', $playlistId);

        if ($refresh) {
            Cache::forget($key);
        }

        return Cache::remember($key, now()->addHours(12), fn (): array => $this->request($playlistId));
    }

    /** @return array<string, mixed> */
    private function request(string $playlistId): array
    {
        $apiKey = trim((string) config('services.youtube.api_key'));

        if ($apiKey === '') {
            return $this->unavailable($playlistId, 'api_not_configured');
        }

        try {
            $item = Http::timeout(5)->acceptJson()->get('https://www.googleapis.com/youtube/v3/playlists', [
                'part' => 'snippet,contentDetails,status',
                'id' => $playlistId,
                'key' => $apiKey,
            ])->throw()->json('items.0');

            if (! is_array($item)) {
                return $this->unavailable($playlistId, 'playlist_unavailable');
            }

            $thumbnail = $this->bestThumbnail((array) data_get($item, 'snippet.thumbnails', []));

            if (! $thumbnail) {
                try {
                    $thumbnail = $this->firstPlaylistItemThumbnail($playlistId, $apiKey);
                } catch (Throwable) {
                    $thumbnail = null;
                }
            }

            return [
                'available' => true,
                'playlist_id' => $playlistId,
                'title' => (string) data_get($item, 'snippet.title', ''),
                'channel_title' => (string) data_get($item, 'snippet.channelTitle', ''),
                'channel_id' => (string) data_get($item, 'snippet.channelId', ''),
                'description' => (string) data_get($item, 'snippet.description', ''),
                'item_count' => (int) data_get($item, 'contentDetails.itemCount', 0),
                'thumbnail_url' => $thumbnail,
                'published_at' => data_get($item, 'snippet.publishedAt'),
                'privacy_status' => data_get($item, 'status.privacyStatus'),
            ];
        } catch (Throwable) {
            return $this->unavailable($playlistId, 'api_failure');
        }
    }

    private function firstPlaylistItemThumbnail(string $playlistId, string $apiKey): ?string
    {
        $items = Http::timeout(5)->acceptJson()->get('https://www.googleapis.com/youtube/v3/playlistItems', [
            'part' => 'snippet,contentDetails',
            'playlistId' => $playlistId,
            'maxResults' => 5,
            'key' => $apiKey,
        ])->throw()->json('items', []);

        foreach ($items as $item) {
            $title = strtolower((string) data_get($item, 'snippet.title', ''));

            if (in_array($title, ['deleted video', 'private video'], true)) {
                continue;
            }

            if ($thumbnail = $this->bestThumbnail((array) data_get($item, 'snippet.thumbnails', []))) {
                return $thumbnail;
            }
        }

        return null;
    }

    /** @param array<string, mixed> $thumbnails */
    private function bestThumbnail(array $thumbnails): ?string
    {
        foreach (['maxres', 'standard', 'high', 'medium', 'default'] as $quality) {
            $url = data_get($thumbnails, "{$quality}.url");

            if (is_string($url) && str_starts_with($url, 'https://') && $this->trustedThumbnailHost($url)) {
                return $url;
            }
        }

        return null;
    }

    private function trustedThumbnailHost(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, ['i.ytimg.com', 'img.youtube.com', 'yt3.ggpht.com'], true)
            || str_ends_with($host, '.googleusercontent.com');
    }

    /** @return array<string, mixed> */
    private function unavailable(string $playlistId, string $reason): array
    {
        return [
            'available' => false,
            'playlist_id' => $playlistId,
            'title' => '',
            'channel_title' => '',
            'item_count' => null,
            'thumbnail_url' => null,
            'metadata_unavailable' => true,
            'reason' => $reason,
        ];
    }
}
