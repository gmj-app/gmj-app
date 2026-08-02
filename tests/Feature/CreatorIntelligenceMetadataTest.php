<?php

namespace Tests\Feature;

use App\Models\ContentItem;
use App\Models\CreatorChannel;
use App\Models\CreatorVideo;
use App\Models\Subject;
use App\Services\CreatorIntelligence\Metadata\MetadataCompletionService;
use App\Services\CreatorIntelligence\Metadata\NameNormalizer;
use App\Services\CreatorIntelligence\Metadata\TitleMetadataParser;
use App\Services\CreatorIntelligence\Metadata\VideoClassificationService;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorIntelligenceMetadataTest extends TestCase
{
    use RefreshDatabase;

    public function test_names_are_normalized_and_unique_within_a_channel(): void
    {
        $channel = CreatorChannel::factory()->create();
        $normalizer = app(NameNormalizer::class);

        $this->assertSame('sb19', $normalizer->normalize('  SB19  '));
        Subject::factory()->create(['creator_channel_id' => $channel->id, 'name' => 'SB19', 'normalized_name' => 'sb19', 'slug' => 'sb19']);

        $this->expectException(QueryException::class);
        Subject::factory()->create(['creator_channel_id' => $channel->id, 'name' => ' sb19 ', 'normalized_name' => 'sb19', 'slug' => 'sb19-2']);
    }

    public function test_classification_enforces_one_primary_and_same_channel(): void
    {
        $video = CreatorVideo::factory()->create();
        $first = Subject::factory()->create(['creator_channel_id' => $video->creator_channel_id]);
        $second = Subject::factory()->create(['creator_channel_id' => $video->creator_channel_id]);
        $item = ContentItem::factory()->create(['creator_channel_id' => $video->creator_channel_id]);

        app(VideoClassificationService::class)->subjects($video, [
            ['id' => $first->id, 'relationship_type' => 'featured', 'is_primary' => false],
            ['id' => $second->id, 'relationship_type' => 'primary', 'is_primary' => true],
        ]);
        app(VideoClassificationService::class)->contentItems($video, [['id' => $item->id, 'is_primary' => true]]);

        $this->assertSame($second->id, $video->fresh()->primarySubject->first()->id);
        $this->assertSame(1, $video->subjects()->wherePivot('is_primary', true)->count());
    }

    public function test_title_parser_is_unicode_safe_and_completion_is_persisted(): void
    {
        $video = CreatorVideo::factory()->create(['title' => 'WOW! SB19: New Song?']);
        app(TitleMetadataParser::class)->recalculate($video);
        $metadata = $video->fresh()->titleMetadata;

        $this->assertSame(mb_strlen($video->title), $metadata->character_count);
        $this->assertTrue($metadata->contains_exclamation);
        $this->assertTrue($metadata->contains_question);

        $completion = app(MetadataCompletionService::class)->recalculate($video->fresh());
        $this->assertSame(0, $completion['percentage']);
        $this->assertSame($completion['percentage'], $video->fresh()->metadata_completion_percentage);
    }
}
