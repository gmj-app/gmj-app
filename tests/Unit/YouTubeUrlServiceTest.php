<?php

namespace Tests\Unit;

use App\Services\YouTubeUrlService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class YouTubeUrlServiceTest extends TestCase
{
    #[DataProvider('playlistUrls')]
    public function test_it_classifies_and_canonicalizes_pure_playlist_urls(string $url): void
    {
        $parsed = (new YouTubeUrlService)->normalize($url);

        $this->assertSame('youtube', $parsed['provider']);
        $this->assertSame('playlist', $parsed['media_type']);
        $this->assertSame('PL1234567890', $parsed['youtube_playlist_id']);
        $this->assertNull($parsed['youtube_video_id']);
        $this->assertSame('https://www.youtube.com/playlist?list=PL1234567890', $parsed['canonical_url']);
    }

    public static function playlistUrls(): array
    {
        return [
            ['https://www.youtube.com/playlist?list=PL1234567890&si=tracking'],
            ['https://m.youtube.com/playlist?index=2&list=PL1234567890'],
        ];
    }

    #[DataProvider('mixedUrls')]
    public function test_mixed_video_playlist_urls_remain_videos(string $url): void
    {
        $parsed = (new YouTubeUrlService)->normalize($url);

        $this->assertSame('video', $parsed['media_type']);
        $this->assertSame('dQw4w9WgXcQ', $parsed['youtube_video_id']);
        $this->assertSame('PL1234567890', $parsed['youtube_playlist_id']);
        $this->assertSame('https://www.youtube.com/watch?v=dQw4w9WgXcQ', $parsed['canonical_url']);
    }

    public static function mixedUrls(): array
    {
        return [
            ['https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PL1234567890&index=3'],
            ['https://youtu.be/dQw4w9WgXcQ?list=PL1234567890&si=tracking'],
        ];
    }
}
