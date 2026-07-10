<?php

namespace Tests\Unit;

use App\Services\YouTubePlaylistMetadataService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class YouTubePlaylistMetadataServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['services.youtube.api_key' => 'test-key']);
        Cache::flush();
    }

    public function test_playlist_metadata_uses_the_best_snippet_thumbnail(): void
    {
        Http::fake([
            '*youtube/v3/playlists*' => Http::response(['items' => [[
                'id' => 'PL1234567890',
                'snippet' => [
                    'title' => 'Deep Cuts',
                    'channelTitle' => 'Guide Channel',
                    'channelId' => 'channel-1',
                    'thumbnails' => ['high' => ['url' => 'https://i.ytimg.com/vi/example/hqdefault.jpg']],
                ],
                'contentDetails' => ['itemCount' => 18],
                'status' => ['privacyStatus' => 'public'],
            ]]]),
        ]);

        $metadata = app(YouTubePlaylistMetadataService::class)->fetch('PL1234567890');

        $this->assertTrue($metadata['available']);
        $this->assertSame('Deep Cuts', $metadata['title']);
        $this->assertSame('Guide Channel', $metadata['channel_title']);
        $this->assertSame(18, $metadata['item_count']);
        $this->assertSame('https://i.ytimg.com/vi/example/hqdefault.jpg', $metadata['thumbnail_url']);
    }

    public function test_missing_playlist_thumbnail_uses_first_available_public_item(): void
    {
        Http::fake([
            '*youtube/v3/playlists*' => Http::response(['items' => [[
                'snippet' => ['title' => 'Fallback List', 'channelTitle' => 'Owner', 'thumbnails' => []],
                'contentDetails' => ['itemCount' => 2],
            ]]]),
            '*youtube/v3/playlistItems*' => Http::response(['items' => [
                ['snippet' => ['title' => 'Private video', 'thumbnails' => []]],
                ['snippet' => ['title' => 'Available', 'thumbnails' => ['medium' => ['url' => 'https://i.ytimg.com/vi/fallback/mqdefault.jpg']]]],
            ]]),
        ]);

        $metadata = app(YouTubePlaylistMetadataService::class)->fetch('PL1234567890');

        $this->assertSame('https://i.ytimg.com/vi/fallback/mqdefault.jpg', $metadata['thumbnail_url']);
    }

    public function test_api_failure_returns_an_intentional_unavailable_state(): void
    {
        Http::fake(['*' => Http::response([], 500)]);

        $metadata = app(YouTubePlaylistMetadataService::class)->fetch('PL1234567890');

        $this->assertFalse($metadata['available']);
        $this->assertTrue($metadata['metadata_unavailable']);
        $this->assertNull($metadata['thumbnail_url']);
    }
}
