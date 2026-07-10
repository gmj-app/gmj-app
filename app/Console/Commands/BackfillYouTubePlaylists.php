<?php

namespace App\Console\Commands;

use App\Models\Recommendation;
use App\Services\YouTubePlaylistMetadataService;
use App\Services\YouTubeUrlService;
use Illuminate\Console\Command;

class BackfillYouTubePlaylists extends Command
{
    protected $signature = 'recommendations:backfill-youtube-playlists
        {--dry-run : Report changes without saving}
        {--refresh : Refresh metadata for playlists already classified}
        {--limit= : Maximum recommendations to inspect}
        {--recommendation= : Process one recommendation ID}';

    protected $description = 'Classify and populate stored metadata for YouTube playlist recommendations';

    public function handle(YouTubeUrlService $urls, YouTubePlaylistMetadataService $metadata): int
    {
        $query = Recommendation::query()
            ->whereNotNull('youtube_url')
            ->when($this->option('recommendation'), fn ($query, $id) => $query->whereKey($id))
            ->when(! $this->option('refresh'), fn ($query) => $query->where(function ($query): void {
                $query->whereNull('media_type')->orWhere('media_type', '!=', 'playlist');
            }))
            ->orderBy('id');

        if ($limit = (int) $this->option('limit')) {
            $query->limit($limit);
        }

        $found = 0;
        $updated = 0;
        $failed = 0;

        foreach ($query->cursor() as $recommendation) {
            $parsed = $urls->normalize($recommendation->youtube_url);

            if ($parsed['media_type'] !== 'playlist' || ! $parsed['youtube_playlist_id']) {
                continue;
            }

            $found++;
            $playlist = $metadata->fetch($parsed['youtube_playlist_id'], (bool) $this->option('refresh'));
            $attributes = [
                'media_type' => 'playlist',
                'youtube_video_id' => null,
                'youtube_playlist_id' => $parsed['youtube_playlist_id'],
                'normalized_url' => $parsed['canonical_url'],
                'thumbnail_url' => $playlist['thumbnail_url'] ?? null,
                'source_title' => $playlist['title'] ?? null,
                'source_channel' => $playlist['channel_title'] ?? null,
                'source_item_count' => $playlist['item_count'] ?? null,
                'source_metadata' => $playlist,
            ];

            if ($this->option('dry-run')) {
                $this->line("Would update recommendation {$recommendation->id} ({$parsed['youtube_playlist_id']}).");

                continue;
            }

            $recommendation->update($attributes);
            $updated++;

            if (! ($playlist['available'] ?? false)) {
                $failed++;
                $this->warn("Recommendation {$recommendation->id} classified; metadata unavailable.");
            } else {
                $this->info("Updated recommendation {$recommendation->id}.");
            }
        }

        $this->newLine();
        $this->info("Playlist records found: {$found}; updated: {$updated}; metadata unavailable: {$failed}.");

        return self::SUCCESS;
    }
}
