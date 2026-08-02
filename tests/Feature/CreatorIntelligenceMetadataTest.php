<?php

namespace Tests\Feature;

use App\Models\ContentItem;
use App\Models\CreatorChannel;
use App\Models\CreatorVideo;
use App\Models\Subject;
use App\Models\User;
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

    public function test_metadata_queue_renders_accessible_dark_table_controls_and_channel_labels(): void
    {
        $channel = CreatorChannel::factory()->create(['subject_label' => 'Artist', 'content_item_label' => 'Song']);
        CreatorVideo::factory()->for($channel, 'channel')->count(2)->create();

        $response = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.metadata-queue.index', ['creator_channel_id' => $channel->id]));

        $response->assertOk()
            ->assertSee('Search video title, ID, subject, or content item')
            ->assertSee('Primary Artist')
            ->assertSee('Primary Song')
            ->assertSee('Create Artist')
            ->assertSee('Create Song')
            ->assertSee('Title Metadata Status')
            ->assertSee('Thumbnail Metadata Status')
            ->assertSee('Editorial Metadata Status')
            ->assertSee('Select All on Current Page')
            ->assertSee('Clear Selection')
            ->assertSee('Apply to Selected')
            ->assertSee('Edit Metadata')
            ->assertSee('bg-slate-900')
            ->assertSee('dark:border-slate-700')
            ->assertSee('focus-visible:ring-2');
        $this->assertSame(2, substr_count($response->getContent(), 'data-video-select'));
        $this->assertStringContainsString('0</span> selected on current page', $response->getContent());
        $this->assertSame(11, substr_count($response->getContent(), 'scope="col"'));
    }

    public function test_metadata_queue_has_distinct_empty_and_no_result_states(): void
    {
        $admin = $this->admin();
        $this->actingAs($admin)->get(route('superadmin.creator-intelligence.metadata-queue.index'))
            ->assertOk()->assertSee('No imported videos are available yet.');

        $channel = CreatorChannel::factory()->create();
        CreatorVideo::factory()->for($channel, 'channel')->create(['metadata_completion_status' => 'complete', 'metadata_completion_percentage' => 100]);

        $this->actingAs($admin)->get(route('superadmin.creator-intelligence.metadata-queue.index', ['creator_channel_id' => $channel->id, 'search' => 'not-a-real-video']))
            ->assertOk()->assertSee('No videos match the current Metadata Queue filters.');
        $this->actingAs($admin)->get(route('superadmin.creator-intelligence.metadata-queue.index', ['creator_channel_id' => $channel->id, 'status' => 'in_progress']))
            ->assertOk()->assertSee('No videos are missing the selected metadata.');
    }

    public function test_metadata_queue_searches_title_id_subject_and_content_item(): void
    {
        $channel = CreatorChannel::factory()->create();
        $titleVideo = CreatorVideo::factory()->for($channel, 'channel')->create(['title' => 'SB19 performance']);
        $idVideo = CreatorVideo::factory()->for($channel, 'channel')->create(['title' => 'Platform match', 'platform_video_id' => 'NiDToOrsUeI']);
        $subjectVideo = CreatorVideo::factory()->for($channel, 'channel')->create(['title' => 'Subject relation result']);
        $itemVideo = CreatorVideo::factory()->for($channel, 'channel')->create(['title' => 'Content relation result']);
        $subject = Subject::factory()->for($channel, 'creatorChannel')->create(['name' => 'Morissette']);
        $item = ContentItem::factory()->for($channel, 'creatorChannel')->create(['name' => 'Missioned Souls']);
        $subjectVideo->subjects()->attach($subject, ['relationship_type' => 'primary', 'is_primary' => true]);
        $itemVideo->contentItems()->attach($item, ['is_primary' => true]);

        foreach ([
            'SB19' => $titleVideo->title,
            'NiDToOrsUeI' => $idVideo->title,
            'Morissette' => $subjectVideo->title,
            'Missioned Souls' => $itemVideo->title,
        ] as $search => $expectedTitle) {
            $videos = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.metadata-queue.index', ['creator_channel_id' => $channel->id, 'search' => $search]))->viewData('videos');
            $this->assertSame([$expectedTitle], $videos->pluck('title')->all());
        }
    }

    public function test_metadata_queue_search_combines_with_missing_subject_and_preserves_pagination_sorting(): void
    {
        $channel = CreatorChannel::factory()->create();
        $subject = Subject::factory()->for($channel, 'creatorChannel')->create();
        $assigned = CreatorVideo::factory()->for($channel, 'channel')->create(['title' => 'SB19 assigned']);
        $assigned->subjects()->attach($subject, ['relationship_type' => 'primary', 'is_primary' => true]);
        CreatorVideo::factory()->for($channel, 'channel')->count(26)->create(['title' => 'SB19 unassigned']);

        $response = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.metadata-queue.index', [
            'creator_channel_id' => $channel->id,
            'search' => 'SB19',
            'missing_subject' => 1,
            'sort' => 'title',
            'direction' => 'desc',
        ]));
        $videos = $response->viewData('videos');

        $response->assertOk()->assertDontSee('SB19 assigned');
        $this->assertSame(26, $videos->total());
        $this->assertStringContainsString('search=SB19', $videos->nextPageUrl());
        $this->assertStringContainsString('missing_subject=1', $videos->nextPageUrl());
        $this->assertStringContainsString('sort=title', $videos->nextPageUrl());
        foreach ($videos->getCollection() as $video) {
            $this->assertTrue($video->relationLoaded('channel'));
            $this->assertTrue($video->relationLoaded('primarySubject'));
            $this->assertTrue($video->relationLoaded('primaryContentItem'));
            $this->assertTrue($video->relationLoaded('titleMetadata'));
            $this->assertTrue($video->relationLoaded('thumbnailMetadata'));
            $this->assertTrue($video->relationLoaded('editorialMetadata'));
        }
    }

    public function test_bulk_primary_subject_assignment_reports_results_and_preserves_filters(): void
    {
        $channel = CreatorChannel::factory()->create();
        $subject = Subject::factory()->for($channel, 'creatorChannel')->create(['name' => 'SB19']);
        $videos = CreatorVideo::factory()->for($channel, 'channel')->count(2)->create();
        $url = route('superadmin.creator-intelligence.metadata-queue.bulk-update', ['search' => 'SB19', 'missing_subject' => 1]);

        $response = $this->actingAs($this->admin())->post($url, [
            'video_ids' => $videos->pluck('id')->all(),
            'operation' => 'assign_primary_subject',
            'value' => $subject->id,
            'mode' => 'fill',
            'confirmed' => 1,
        ]);

        $response->assertRedirect(route('superadmin.creator-intelligence.metadata-queue.index', ['search' => 'SB19', 'missing_subject' => 1]))
            ->assertSessionHas('success', 'Bulk update complete: 2 videos updated; 0 skipped.');
        foreach ($videos as $video) {
            $this->assertSame($subject->id, $video->fresh()->primarySubject->first()->id);
            $this->assertSame(20, $video->fresh()->metadata_completion_percentage);
        }
    }

    public function test_fill_missing_primary_subject_preserves_existing_assignment(): void
    {
        $channel = CreatorChannel::factory()->create();
        $existing = Subject::factory()->for($channel, 'creatorChannel')->create();
        $replacement = Subject::factory()->for($channel, 'creatorChannel')->create();
        $assigned = CreatorVideo::factory()->for($channel, 'channel')->create();
        $missing = CreatorVideo::factory()->for($channel, 'channel')->create();
        $assigned->subjects()->attach($existing, ['relationship_type' => 'primary', 'is_primary' => true]);

        $this->actingAs($this->admin())->post(route('superadmin.creator-intelligence.metadata-queue.bulk-update'), [
            'video_ids' => [$assigned->id, $missing->id], 'operation' => 'assign_primary_subject', 'value' => $replacement->id, 'mode' => 'fill', 'confirmed' => 1,
        ])->assertSessionHas('success', 'Bulk update complete: 1 videos updated; 1 skipped.');

        $this->assertSame($existing->id, $assigned->fresh()->primarySubject->first()->id);
        $this->assertSame($replacement->id, $missing->fresh()->primarySubject->first()->id);
    }

    public function test_replace_primary_subject_requires_confirmation_and_only_updates_selected_videos(): void
    {
        $channel = CreatorChannel::factory()->create();
        $existing = Subject::factory()->for($channel, 'creatorChannel')->create();
        $replacement = Subject::factory()->for($channel, 'creatorChannel')->create();
        $selected = CreatorVideo::factory()->for($channel, 'channel')->create();
        $unselected = CreatorVideo::factory()->for($channel, 'channel')->create();
        foreach ([$selected, $unselected] as $video) {
            $video->subjects()->attach($existing, ['relationship_type' => 'primary', 'is_primary' => true]);
        }
        $payload = ['video_ids' => [$selected->id], 'operation' => 'assign_primary_subject', 'value' => $replacement->id, 'mode' => 'replace'];

        $this->actingAs($this->admin())->post(route('superadmin.creator-intelligence.metadata-queue.bulk-update'), $payload)
            ->assertSessionHasErrors('confirmed');
        $this->assertSame($existing->id, $selected->fresh()->primarySubject->first()->id);

        $this->actingAs($this->admin())->post(route('superadmin.creator-intelligence.metadata-queue.bulk-update'), $payload + ['confirmed' => 1])
            ->assertSessionHasNoErrors();
        $this->assertSame($replacement->id, $selected->fresh()->primarySubject->first()->id);
        $this->assertSame($existing->id, $unselected->fresh()->primarySubject->first()->id);
    }

    public function test_cross_channel_bulk_subject_assignment_is_rejected(): void
    {
        $firstChannel = CreatorChannel::factory()->create();
        $secondChannel = CreatorChannel::factory()->create();
        $subject = Subject::factory()->for($firstChannel, 'creatorChannel')->create();
        $videos = collect([
            CreatorVideo::factory()->for($firstChannel, 'channel')->create(),
            CreatorVideo::factory()->for($secondChannel, 'channel')->create(),
        ]);

        $this->actingAs($this->admin())->post(route('superadmin.creator-intelligence.metadata-queue.bulk-update'), [
            'video_ids' => $videos->pluck('id')->all(), 'operation' => 'assign_primary_subject', 'value' => $subject->id, 'mode' => 'replace', 'confirmed' => 1,
        ])->assertSessionHasErrors('video_ids');
        $this->assertFalse($videos[0]->primarySubject()->exists());
        $this->assertFalse($videos[1]->primarySubject()->exists());
    }

    private function admin(): User
    {
        return User::factory()->create(['can_manage_creator_intelligence' => true]);
    }
}
