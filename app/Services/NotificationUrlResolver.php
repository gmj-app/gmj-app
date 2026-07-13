<?php

namespace App\Services;

class NotificationUrlResolver
{
    public function resolve(?string $url): string
    {
        $fallback = route('notifications.index', absolute: false);
        $url = trim((string) $url);

        if ($url === '' || ! str_starts_with($url, '/') || str_starts_with($url, '//') || str_contains($url, '\\') || preg_match('/[\x00-\x1F\x7F]/', $url)) {
            return $fallback;
        }

        return $url;
    }

    public function isSafe(?string $url): bool
    {
        return $this->resolve($url) === trim((string) $url);
    }
}
