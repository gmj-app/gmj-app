<?php

namespace App\Services;

class YouTubeUrlService
{
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
