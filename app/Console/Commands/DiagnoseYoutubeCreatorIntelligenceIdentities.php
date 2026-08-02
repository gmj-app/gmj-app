<?php

namespace App\Console\Commands;

use App\Models\CreatorVideo;
use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Services\CreatorIntelligence\Import\YoutubeVideoIdentityService;
use Carbon\CarbonImmutable;
use Illuminate\Console\Command;
use InvalidArgumentException;

class DiagnoseYoutubeCreatorIntelligenceIdentities extends Command
{
    protected $signature = 'creator-intelligence:diagnose-youtube-identities
        {--channel= : Limit diagnosis to a creator channel ID}
        {--batch= : Limit diagnosis to one import batch ID}';

    protected $description = 'Report Content and Video identity coverage in stored YouTube import rows without changing data';

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

        $counts = ['rows' => 0, 'content' => 0, 'video' => 0, 'valid' => 0, 'invalid' => 0, 'matchable' => 0, 'ambiguous' => 0, 'already_real' => 0, 'missing_context' => 0];
        ImportBatchRow::query()->with('batch.channel')->when($batchId, fn ($query) => $query->where('import_batch_id', $batchId))
            ->when($channelId, fn ($query) => $query->whereHas('batch', fn ($batch) => $batch->where('creator_channel_id', $channelId)))
            ->orderBy('id')->chunkById(250, function ($rows) use ($identities, &$counts): void {
                foreach ($rows as $row) {
                    $counts['rows']++;
                    $raw = $row->raw_data ?? [];
                    $normalized = $row->normalized_data ?? [];
                    if (array_key_exists('Content', $raw)) {
                        $counts['content']++;
                    } elseif (array_key_exists('Video', $raw)) {
                        $counts['video']++;
                    }
                    try {
                        $platformId = $identities->platformIdFromImportData($raw, $normalized);
                    } catch (InvalidArgumentException) {
                        $counts['invalid']++;

                        continue;
                    }
                    if (! $platformId) {
                        $counts['invalid']++;

                        continue;
                    }
                    $counts['valid']++;
                    if (CreatorVideo::where('creator_channel_id', $row->batch->creator_channel_id)->where('platform_video_id', $platformId)->exists()) {
                        $counts['already_real']++;

                        continue;
                    }
                    $title = $normalized['title'] ?? $raw['Video title'] ?? $raw['Title'] ?? null;
                    $publishedValue = $normalized['published_at'] ?? $raw['Video publish time'] ?? $raw['Publish time'] ?? null;
                    if (blank($title) || blank($publishedValue)) {
                        $counts['missing_context']++;

                        continue;
                    }
                    try {
                        $published = CarbonImmutable::parse($publishedValue, $row->batch->channel->default_publish_timezone);
                        $match = $identities->findFingerprintMatch($row->batch->creator_channel_id, $title, $published);
                    } catch (InvalidArgumentException) {
                        $counts['ambiguous']++;

                        continue;
                    } catch (\Throwable) {
                        $counts['missing_context']++;

                        continue;
                    }
                    $match ? $counts['matchable']++ : $counts['missing_context']++;
                }
            });

        foreach ($counts as $label => $count) {
            $this->line(str($label)->replace('_', ' ')->title().": {$count}");
        }

        return self::SUCCESS;
    }
}
