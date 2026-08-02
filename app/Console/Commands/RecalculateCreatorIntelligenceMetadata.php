<?php

namespace App\Console\Commands;

use App\Models\CreatorVideo;
use App\Services\CreatorIntelligence\Metadata\MetadataCompletionService;
use App\Services\CreatorIntelligence\Metadata\TitleMetadataParser;
use Illuminate\Console\Command;

class RecalculateCreatorIntelligenceMetadata extends Command
{
    protected $signature = 'creator-intelligence:recalculate-metadata {--channel=} {--video=} {--chunk=100}';

    protected $description = 'Recalculate Creator Intelligence title structure and metadata completion.';

    public function handle(TitleMetadataParser $titles, MetadataCompletionService $completion): int
    {
        $q = CreatorVideo::query();
        if ($this->option('channel')) {
            $q->where('creator_channel_id', $this->option('channel'));
        }if ($this->option('video')) {
            $q->whereKey($this->option('video'));
        }$count = 0;
        $q->chunkById(max(1, (int) $this->option('chunk')), function ($videos) use ($titles, $completion, &$count) {
            foreach ($videos as $video) {
                if ($video->titleMetadata()->exists()) {
                    $titles->recalculate($video);
                }$completion->recalculate($video);
                $count++;
            }
        });
        $this->info("Recalculated {$count} videos.");

        return self::SUCCESS;
    }
}
