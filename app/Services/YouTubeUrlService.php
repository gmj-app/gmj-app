<?php

namespace App\Services;

class YouTubeUrlService
{
    /**
     * @return array{provider: string|null, media_type: string|null, canonical_url: string|null, original_url: string|null, youtube_video_id: string|null, youtube_playlist_id: string|null}
     */
    public function normalize(?string $url): array
    {
        $url = trim((string) $url);

        if ($url === '') {
            return [
                'provider' => null,
                'media_type' => null,
                'canonical_url' => null,
                'original_url' => null,
                'youtube_video_id' => null,
                'youtube_playlist_id' => null,
            ];
        }

        $videoId = $this->extractVideoId($url);
        $playlistId = $this->extractPlaylistId($url);
        $path = rtrim((string) (parse_url($url, PHP_URL_PATH) ?? ''), '/');
        $isPurePlaylist = strtolower($path) === '/playlist' && $playlistId !== null;

        if ($isPurePlaylist) {
            return [
                'provider' => 'youtube',
                'media_type' => 'playlist',
                'canonical_url' => "https://www.youtube.com/playlist?list={$playlistId}",
                'original_url' => $url,
                'youtube_video_id' => null,
                'youtube_playlist_id' => $playlistId,
            ];
        }

        if ($videoId) {
            return [
                'provider' => 'youtube',
                'media_type' => 'video',
                'canonical_url' => "https://www.youtube.com/watch?v={$videoId}",
                'original_url' => $url,
                'youtube_video_id' => $videoId,
                'youtube_playlist_id' => $playlistId,
            ];
        }

        $parts = parse_url($url);

        if (! is_array($parts) || blank($parts['host'] ?? null)) {
            return [
                'provider' => null,
                'media_type' => 'link',
                'canonical_url' => $url,
                'original_url' => $url,
                'youtube_video_id' => null,
                'youtube_playlist_id' => null,
            ];
        }

        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        $host = strtolower((string) $parts['host']);
        $path = rtrim((string) ($parts['path'] ?? ''), '/');
        $query = [];

        parse_str((string) ($parts['query'] ?? ''), $query);

        $trackingPrefixes = ['utm_'];
        $trackingParameters = ['fbclid', 'gclid', 'mc_cid', 'mc_eid', 'si', 'feature'];
        $query = collect($query)
            ->reject(fn (mixed $value, mixed $key): bool => in_array((string) $key, $trackingParameters, true)
                || str((string) $key)->startsWith($trackingPrefixes))
            ->sortKeys()
            ->all();

        $canonicalUrl = "{$scheme}://{$host}{$path}";
        $queryString = http_build_query($query);

        if ($queryString !== '') {
            $canonicalUrl .= "?{$queryString}";
        }

        return [
            'provider' => $this->isYoutubeHost($host) ? 'youtube' : null,
            'media_type' => 'link',
            'canonical_url' => $canonicalUrl,
            'original_url' => $url,
            'youtube_video_id' => null,
            'youtube_playlist_id' => $playlistId,
        ];
    }

    public function extractVideoId(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');
        $videoId = null;

        if ($host === 'youtu.be' || $host === 'www.youtu.be') {
            $videoId = trim(parse_url($url, PHP_URL_PATH) ?? '', '/');
            $videoId = explode('/', $videoId)[0];
        } elseif ($host === 'youtube.com' || str_ends_with($host, '.youtube.com')) {
            parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
            $videoId = $query['v'] ?? null;

            if (! $videoId) {
                $pathParts = explode('/', trim(parse_url($url, PHP_URL_PATH) ?? '', '/'));

                if (in_array($pathParts[0] ?? null, ['shorts', 'embed', 'live'], true)) {
                    $videoId = $pathParts[1] ?? null;
                }
            }
        }

        return is_string($videoId) && preg_match('/^[A-Za-z0-9_-]{11}$/', $videoId)
            ? $videoId
            : null;
    }

    public function extractPlaylistId(?string $url): ?string
    {
        if (blank($url)) {
            return null;
        }

        $host = strtolower(parse_url($url, PHP_URL_HOST) ?? '');

        if (! $this->isYoutubeHost($host)) {
            return null;
        }

        parse_str(parse_url($url, PHP_URL_QUERY) ?? '', $query);
        $playlistId = $query['list'] ?? null;

        return is_string($playlistId) && preg_match('/^[A-Za-z0-9_-]{10,100}$/', $playlistId)
            ? $playlistId
            : null;
    }

    private function isYoutubeHost(string $host): bool
    {
        return in_array($host, ['youtu.be', 'www.youtu.be', 'youtube.com'], true)
            || str_ends_with($host, '.youtube.com');
    }
}
