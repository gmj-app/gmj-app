<?php

namespace App\Services\Youtube;

use App\Models\YoutubeChannelToken;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class YoutubeApiClient
{
    public function refreshTokenIfNeeded(YoutubeChannelToken $token): YoutubeChannelToken
    {
        if ($token->expires_at === null || $token->expires_at->isFuture()) {
            return $token;
        }

        if (blank($token->refresh_token)) {
            throw new RuntimeException('The YouTube authorization expired and no refresh token is available.');
        }

        $response = Http::asForm()->post('https://oauth2.googleapis.com/token', [
            'client_id' => config('services.google.client_id'),
            'client_secret' => config('services.google.client_secret'),
            'refresh_token' => $token->refresh_token,
            'grant_type' => 'refresh_token',
        ]);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to refresh YouTube authorization.');
        }

        $payload = $response->json();

        $token->forceFill([
            'access_token' => (string) $payload['access_token'],
            'expires_at' => Carbon::now()->addSeconds((int) ($payload['expires_in'] ?? 3600) - 60),
        ])->save();

        return $token->fresh();
    }

    /**
     * @return array{channel_id: string|null, channel_title: string|null, videos: array<int, YoutubeVideoSnippet>}
     */
    public function uploadedVideos(YoutubeChannelToken $token): array
    {
        $token = $this->refreshTokenIfNeeded($token);

        $channel = $this->request($token)->get('https://www.googleapis.com/youtube/v3/channels', [
            'part' => 'contentDetails,snippet',
            'mine' => 'true',
            'maxResults' => 1,
        ])->throw()->json('items.0');

        if (! is_array($channel)) {
            return [
                'channel_id' => null,
                'channel_title' => null,
                'videos' => [],
            ];
        }

        $uploadsPlaylistId = data_get($channel, 'contentDetails.relatedPlaylists.uploads');
        $videoIds = $this->playlistVideoIds($token, (string) $uploadsPlaylistId);

        return [
            'channel_id' => (string) data_get($channel, 'id'),
            'channel_title' => (string) data_get($channel, 'snippet.title'),
            'videos' => $this->videoSnippets($token, $videoIds),
        ];
    }

    /**
     * @param  array<string, mixed>  $snippet
     */
    public function updateDescription(YoutubeChannelToken $token, string $videoId, array $snippet, string $description): void
    {
        $token = $this->refreshTokenIfNeeded($token);
        $updatedSnippet = $snippet;
        $updatedSnippet['description'] = $description;

        $this->request($token)
            ->put('https://www.googleapis.com/youtube/v3/videos?part=snippet', [
                'id' => $videoId,
                'snippet' => $updatedSnippet,
            ])
            ->throw();
    }

    public function videoSnippet(YoutubeChannelToken $token, string $videoId): YoutubeVideoSnippet
    {
        $token = $this->refreshTokenIfNeeded($token);

        $item = $this->request($token)
            ->get('https://www.googleapis.com/youtube/v3/videos', [
                'part' => 'snippet',
                'id' => $videoId,
                'maxResults' => 1,
            ])
            ->throw()
            ->json('items.0');

        if (! is_array($item)) {
            throw new RuntimeException("YouTube video {$videoId} was not found.");
        }

        return new YoutubeVideoSnippet((string) $item['id'], (array) $item['snippet']);
    }

    private function request(YoutubeChannelToken $token): PendingRequest
    {
        return Http::withToken($token->access_token)
            ->acceptJson()
            ->asJson();
    }

    /**
     * @return array<int, string>
     */
    private function playlistVideoIds(YoutubeChannelToken $token, string $playlistId): array
    {
        if ($playlistId === '') {
            return [];
        }

        $videoIds = [];
        $pageToken = null;

        do {
            $payload = $this->request($token)
                ->get('https://www.googleapis.com/youtube/v3/playlistItems', array_filter([
                    'part' => 'contentDetails',
                    'playlistId' => $playlistId,
                    'maxResults' => 50,
                    'pageToken' => $pageToken,
                ]))
                ->throw()
                ->json();

            foreach ($payload['items'] ?? [] as $item) {
                $videoId = data_get($item, 'contentDetails.videoId');

                if (filled($videoId)) {
                    $videoIds[] = (string) $videoId;
                }
            }

            $pageToken = $payload['nextPageToken'] ?? null;
        } while ($pageToken);

        return $videoIds;
    }

    /**
     * @param  array<int, string>  $videoIds
     * @return array<int, YoutubeVideoSnippet>
     */
    private function videoSnippets(YoutubeChannelToken $token, array $videoIds): array
    {
        $videos = [];

        foreach (array_chunk($videoIds, 50) as $chunk) {
            $payload = $this->request($token)
                ->get('https://www.googleapis.com/youtube/v3/videos', [
                    'part' => 'snippet',
                    'id' => implode(',', $chunk),
                    'maxResults' => 50,
                ])
                ->throw()
                ->json();

            foreach ($payload['items'] ?? [] as $item) {
                $videos[] = new YoutubeVideoSnippet((string) $item['id'], (array) $item['snippet']);
            }
        }

        return $videos;
    }
}
