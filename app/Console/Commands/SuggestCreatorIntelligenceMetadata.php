<?php

namespace App\Console\Commands;

use App\Models\CreatorChannel;
use App\Models\CreatorVideo;
use App\Services\CreatorIntelligence\Metadata\MetadataSuggestionGenerator;
use Illuminate\Console\Command;

class SuggestCreatorIntelligenceMetadata extends Command
{
    protected $signature = 'creator-intelligence:suggest-metadata {--channel=} {--video=} {--only-missing} {--dry-run} {--minimum-confidence=low} {--chunk=100}';

    protected $description = 'Generate deterministic subject and content-item suggestions from video titles.';

    public function handle(MetadataSuggestionGenerator $generator): int
    {
        $minimum = (string) $this->option('minimum-confidence');
        if (! in_array($minimum, ['high', 'medium', 'low'], true) || (int) $this->option('chunk') < 1) {
            $this->error('Confidence must be high, medium, or low and chunk must be positive.');

            return self::INVALID;
        }
        $query = CreatorVideo::query();
        if ($channel = $this->option('channel')) {
            $channelId = is_numeric($channel) ? (int) $channel : CreatorChannel::where('handle', $channel)->orWhere('channel_name', $channel)->value('id');
            if (! $channelId) {
                $this->error('Creator channel not found.');

                return self::FAILURE;
            }
            $query->where('creator_channel_id', $channelId);
        }
        if ($video = $this->option('video')) {
            $query->where(fn ($q) => $q->whereKey($video)->orWhere('platform_video_id', $video));
        }
        if ($this->option('only-missing') || ! $this->option('video')) {
            $query->where(fn ($q) => $q->whereDoesntHave('primarySubject')->orWhere(fn ($q) => $q->whereDoesntHave('primaryContentItem')->where('content_item_not_applicable', false)));
        }
        $result = $generator->generate($query, (bool) $this->option('dry-run'), $minimum, (int) $this->option('chunk'));
        $this->table(['Type', 'High', 'Medium', 'Low'], [
            ['Subject', ...array_values($result['subject'])], ['Content item', ...array_values($result['content_item'])],
        ]);
        $this->info(sprintf('%s: processed %d; created %d; updated %d; skipped %d; unresolved %d.', $this->option('dry-run') ? 'PENDING dry-run' : 'COMPLETE', $result['processed'], $result['created'], $result['updated'], $result['skipped'], $result['unresolved']));

        return self::SUCCESS;
    }
}
