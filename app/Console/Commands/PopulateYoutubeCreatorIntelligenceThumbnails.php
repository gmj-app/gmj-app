<?php

namespace App\Console\Commands;

use App\Models\CreatorVideo;
use App\Services\CreatorIntelligence\Import\YoutubeVideoIdentityService;
use Illuminate\Console\Command;
use InvalidArgumentException;

class PopulateYoutubeCreatorIntelligenceThumbnails extends Command
{
    protected $signature = 'creator-intelligence:populate-youtube-thumbnails
        {--channel= : Limit to one creator channel ID}
        {--video= : Limit to one creator video ID}
        {--dry-run : Report changes without writing them}
        {--force : Replace existing YouTube-hosted thumbnail variants; custom URLs remain preserved}';

    protected $description = 'Populate canonical YouTube thumbnail URLs for Creator Intelligence videos';

    public function handle(YoutubeVideoIdentityService $identities): int
    {
        foreach (['channel', 'video'] as $option) {
            if ($this->option($option) !== null && (! ctype_digit((string) $this->option($option)) || (int) $this->option($option) < 1)) {
                $this->error("The --{$option} option must be a positive ID.");

                return self::INVALID;
            }
        }

        $counts = ['populated' => 0, 'preserved' => 0, 'invalid IDs' => 0, 'skipped' => 0];
        CreatorVideo::query()->with('channel:id,platform')->when($this->option('channel'), fn ($query) => $query->where('creator_channel_id', (int) $this->option('channel')))
            ->when($this->option('video'), fn ($query) => $query->whereKey((int) $this->option('video')))
            ->orderBy('id')->chunkById(250, function ($videos) use ($identities, &$counts): void {
                foreach ($videos as $video) {
                    if (strtolower((string) $video->channel?->platform) !== 'youtube') {
                        $counts['skipped']++;

                        continue;
                    }
                    try {
                        $platformId = $identities->validPlatformId($video->platform_video_id);
                    } catch (InvalidArgumentException) {
                        $counts['invalid IDs']++;

                        continue;
                    }
                    if (! $platformId) {
                        $counts['invalid IDs']++;

                        continue;
                    }

                    $canonical = $identities->thumbnailUrl($platformId);
                    $existing = trim((string) $video->thumbnail_url);
                    $replaceable = $existing === '' || ($this->option('force') && $this->isYoutubeHosted($existing));
                    if (! $replaceable || $existing === $canonical) {
                        $counts['preserved']++;

                        continue;
                    }
                    if (! $this->option('dry-run')) {
                        $video->thumbnail_url = $canonical;
                        $video->save();
                    }
                    $counts['populated']++;
                }
            });

        foreach ($counts as $label => $count) {
            $this->line(ucfirst($label).": {$count}");
        }
        if ($this->option('dry-run')) {
            $this->info('Dry run only; no thumbnail URLs were changed.');
        }

        return self::SUCCESS;
    }

    private function isYoutubeHosted(string $url): bool
    {
        $host = strtolower((string) parse_url($url, PHP_URL_HOST));

        return in_array($host, ['i.ytimg.com', 'img.youtube.com'], true);
    }
}
