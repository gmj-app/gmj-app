<?php

namespace App\Services;

class YouTubeUrlService
{
    /**
     * @return array{canonical_url: string|null, youtube_video_id: string|null}
     */
    public function normalize(?string $url): array
    {
        $url = trim((string) $url);

        if ($url === '') {
            return [
                'canonical_url' => null,
                'youtube_video_id' => null,
            ];
        }

        $videoId = $this->extractVideoId($url);

        if ($videoId) {
            return [
                'canonical_url' => "https://www.youtube.com/watch?v={$videoId}",
                'youtube_video_id' => $videoId,
            ];
        }

        $parts = parse_url($url);

        if (! is_array($parts) || blank($parts['host'] ?? null)) {
            return [
                'canonical_url' => $url,
                'youtube_video_id' => null,
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
            'canonical_url' => $canonicalUrl,
            'youtube_video_id' => null,
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
}
