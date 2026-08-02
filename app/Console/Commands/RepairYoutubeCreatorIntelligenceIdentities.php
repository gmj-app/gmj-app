<?php

namespace App\Console\Commands;

use App\Models\CreatorVideo;
use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Services\CreatorIntelligence\Import\YoutubeVideoIdentityService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use InvalidArgumentException;

class RepairYoutubeCreatorIntelligenceIdentities extends Command
{
    protected $signature = 'creator-intelligence:repair-youtube-identities
        {--channel= : Limit repairs to a creator channel ID}
        {--batch= : Limit repairs to one import batch ID}
        {--dry-run : Report changes without writing them}';

    protected $description = 'Safely upgrade fingerprint video identities from stored YouTube import rows and populate missing thumbnails';

    public function handle(YoutubeVideoIdentityService $identities): int
    {
        foreach (['channel', 'batch'] as $option) {
            if ($this->option($option) !== null && (! ctype_digit((string) $this->option($option)) || (int) $this->option($option) < 1)) {
                $this->error("The --{$option} option must be a positive ID.");

                return self::INVALID;
            }
        }
        $channelId = $this->option('channel') ? (int) $this->option('channel') : null;
        $batchId = $this->option('batch') ? (int) $this->option('batch') : null;
        if ($batchId && ! ImportBatch::whereKey($batchId)->when($channelId, fn ($query) => $query->where('creator_channel_id', $channelId))->exists()) {
            $this->error('The selected import batch was not found in the requested channel.');

            return self::FAILURE;
        }

        $updated = 0;
        $thumbnails = 0;
        $skipped = 0;
        $ambiguous = 0;
        $touchedVideoIds = [];
        ImportBatchRow::query()->with('batch.channel')->when($batchId, fn ($query) => $query->where('import_batch_id', $batchId))
            ->when($channelId, fn ($query) => $query->whereHas('batch', fn ($batch) => $batch->where('creator_channel_id', $channelId)))
            ->orderBy('id')->chunkById(250, function ($rows) use ($identities, &$updated, &$thumbnails, &$skipped, &$ambiguous, &$touchedVideoIds): void {
                foreach ($rows as $row) {
                    $raw = $row->raw_data ?? [];
                    $normalized = $row->normalized_data ?? [];
                    try {
                        $platformId = $identities->validPlatformId($normalized['platform_video_id'] ?? $raw['Video'] ?? null);
                    } catch (InvalidArgumentException) {
                        $skipped++;

                        continue;
                    }
                    $title = $normalized['title'] ?? $raw['Video title'] ?? $raw['Title'] ?? null;
                    $publishedValue = $normalized['published_at'] ?? $raw['Video publish time'] ?? $raw['Publish time'] ?? null;
                    if (! $platformId || blank($title) || blank($publishedValue)) {
                        $skipped++;

                        continue;
                    }
                    try {
                        $published = CarbonImmutable::parse($publishedValue, $row->batch->channel->default_publish_timezone);
                    } catch (\Throwable) {
                        $skipped++;

                        continue;
                    }
                    try {
                        $fingerprint = $identities->findFingerprintMatch($row->batch->creator_channel_id, $title, $published);
                    } catch (InvalidArgumentException) {
                        $ambiguous++;

                        continue;
                    }
                    $real = CreatorVideo::where('creator_channel_id', $row->batch->creator_channel_id)->where('platform_video_id', $platformId)->first();
                    if ($fingerprint && $real && $fingerprint->id !== $real->id) {
                        $ambiguous++;

                        continue;
                    }
                    $video = $fingerprint ?? $real;
                    if (! $video) {
                        $skipped++;

                        continue;
                    }
                    $identityChanged = str_starts_with($video->platform_video_id, 'fingerprint:');
                    $thumbnailChanged = blank($video->thumbnail_url);
                    if ($identityChanged) {
                        $video->platform_video_id = $platformId;
                    }
                    if ($thumbnailChanged) {
                        $video->thumbnail_url = $identities->thumbnailUrl($platformId);
                    }
                    if (($identityChanged || $thumbnailChanged) && ! $this->option('dry-run')) {
                        $video->save();
                    }
                    if ($identityChanged) {
                        $updated++;
                    }
                    if ($thumbnailChanged) {
                        $thumbnails++;
                    }
                    $touchedVideoIds[$video->id] = true;
                }
            });

        CreatorVideo::query()->when($channelId, fn ($query) => $query->where('creator_channel_id', $channelId))
            ->when($batchId, fn ($query) => $query->whereHas('importRows', fn ($rows) => $rows->where('import_batch_id', $batchId)))
            ->where('platform_video_id', 'not like', 'fingerprint:%')->where(fn ($query) => $query->whereNull('thumbnail_url')->orWhere('thumbnail_url', ''))
            ->when($touchedVideoIds, fn ($query) => $query->whereNotIn('id', array_keys($touchedVideoIds)))
            ->orderBy('id')->chunkById(250, function ($videos) use ($identities, &$thumbnails): void {
                foreach ($videos as $video) {
                    $video->thumbnail_url = $identities->thumbnailUrl($video->platform_video_id);
                    if (! $this->option('dry-run')) {
                        $video->save();
                    }
                    $thumbnails++;
                }
            });

        $this->info(($this->option('dry-run') ? 'Dry run: ' : '')."updated identities {$updated}, populated thumbnails {$thumbnails}, skipped {$skipped}, ambiguous {$ambiguous}.");

        return self::SUCCESS;
    }
}
