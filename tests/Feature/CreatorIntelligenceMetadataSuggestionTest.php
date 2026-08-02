<?php

namespace Tests\Feature;

use App\Models\CreatorChannel;
use App\Models\CreatorVideo;
use App\Models\MetadataSuggestion;
use App\Models\Subject;
use App\Models\User;
use App\Services\CreatorIntelligence\Metadata\MetadataCompletionService;
use App\Services\CreatorIntelligence\Metadata\MetadataSuggestionApprovalService;
use App\Services\CreatorIntelligence\Metadata\MetadataSuggestionGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use InvalidArgumentException;
use Tests\TestCase;

class CreatorIntelligenceMetadataSuggestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_alias_and_token_boundary_subject_matching(): void
    {
        $channel = CreatorChannel::factory()->create();
        $sb19 = Subject::factory()->create(['creator_channel_id' => $channel->id, 'name' => 'SB19', 'normalized_name' => 'sb19', 'slug' => 'sb19']);
        $pablo = Subject::factory()->create(['creator_channel_id' => $channel->id, 'name' => 'Pablo Nase', 'aliases' => ['PABLO'], 'normalized_name' => 'pablo nase', 'slug' => 'pablo-nase']);
        CreatorVideo::factory()->for($channel, 'channel')->create(['title' => 'SB19 Went HARD']);
        CreatorVideo::factory()->for($channel, 'channel')->create(['title' => 'PABLO Is Cool As Hell']);
        CreatorVideo::factory()->for($channel, 'channel')->create(['title' => 'XS B19 compilation']);

        app(MetadataSuggestionGenerator::class)->generate(CreatorVideo::query());

        $this->assertDatabaseHas('metadata_suggestions', ['suggested_subject_id' => $sb19->id, 'confidence' => 'high']);
        $this->assertDatabaseHas('metadata_suggestions', ['suggested_subject_id' => $pablo->id, 'confidence' => 'high']);
        $this->assertSame(2, MetadataSuggestion::where('suggestion_type', 'subject')->count());
    }

    public function test_quoted_separator_and_channel_packaging_patterns_extract_content_items(): void
    {
        $channel = CreatorChannel::factory()->create(['handle' => 'jfragment']);
        $generator = app(MetadataSuggestionGenerator::class);

        $this->assertSame('Criminal', $generator->extractContentItem("FELIP 'Criminal' Metal Musician Reacts", $channel)['value']);
        $this->assertSame('GENTO', $generator->extractContentItem('SB19 - GENTO Official MV', $channel)['value']);
        $this->assertSame('LAWLESS', $generator->extractContentItem('Artist | LAWLESS | Metal Musician Reacts', $channel)['value']);
    }

    public function test_generation_is_idempotent_dry_run_safe_and_rejections_require_a_changed_fingerprint(): void
    {
        $channel = CreatorChannel::factory()->create();
        Subject::factory()->create(['creator_channel_id' => $channel->id, 'name' => 'Morissette', 'normalized_name' => 'morissette', 'slug' => 'morissette']);
        $video = CreatorVideo::factory()->for($channel, 'channel')->create(['title' => 'Morissette Went FULL Pop Diva']);
        $generator = app(MetadataSuggestionGenerator::class);

        $generator->generate(CreatorVideo::query(), true);
        $this->assertDatabaseCount('metadata_suggestions', 0);
        $generator->generate(CreatorVideo::query());
        $generator->generate(CreatorVideo::query());
        $this->assertSame(1, MetadataSuggestion::where('suggestion_type', 'subject')->count());
        $suggestion = MetadataSuggestion::where('suggestion_type', 'subject')->firstOrFail();
        $suggestion->update(['status' => 'rejected']);
        $generator->generate(CreatorVideo::query());
        $this->assertSame('rejected', $suggestion->fresh()->status);
        $video->update(['title' => 'Morissette Live']);
        $generator->generate(CreatorVideo::query());
        $this->assertSame('pending', $suggestion->fresh()->status);
    }

    public function test_approval_attaches_metadata_preserves_existing_primary_and_only_then_creates_new_content_item(): void
    {
        $channel = CreatorChannel::factory()->create();
        $video = CreatorVideo::factory()->for($channel, 'channel')->create();
        $existingPrimary = Subject::factory()->create(['creator_channel_id' => $channel->id]);
        $suggested = Subject::factory()->create(['creator_channel_id' => $channel->id]);
        $video->subjects()->attach($existingPrimary, ['relationship_type' => 'primary', 'is_primary' => true]);
        $subjectSuggestion = $this->suggestion($video, 'subject', ['suggested_subject_id' => $suggested->id, 'suggested_display_value' => $suggested->name]);
        $contentSuggestion = $this->suggestion($video, 'content_item', ['suggested_display_value' => 'New Approved Song']);
        $approval = app(MetadataSuggestionApprovalService::class);

        $this->assertDatabaseMissing('content_items', ['normalized_name' => 'new approved song']);
        $approval->approve([$subjectSuggestion->id, $contentSuggestion->id], User::factory()->create()->id);

        $this->assertSame($existingPrimary->id, $video->fresh()->primarySubject->first()->id);
        $this->assertTrue($video->subjects()->whereKey($suggested->id)->exists());
        $this->assertDatabaseHas('content_items', ['creator_channel_id' => $channel->id, 'normalized_name' => 'new approved song']);
        $this->assertTrue($video->fresh()->primaryContentItem()->exists());
    }

    public function test_cross_channel_approval_is_rejected_transactionally(): void
    {
        $video = CreatorVideo::factory()->create();
        $foreign = Subject::factory()->create();
        $suggestion = $this->suggestion($video, 'subject', ['suggested_subject_id' => $foreign->id]);

        try {
            app(MetadataSuggestionApprovalService::class)->approve([$suggestion->id], User::factory()->create()->id);
            $this->fail('Expected cross-channel approval to fail.');
        } catch (InvalidArgumentException $exception) {
            $this->assertStringContainsString('Cross-channel', $exception->getMessage());
        }
        $this->assertFalse($video->subjects()->exists());
        $this->assertSame('pending', $suggestion->fresh()->status);
    }

    public function test_explicit_not_applicable_counts_as_complete_content_item_metadata(): void
    {
        $video = CreatorVideo::factory()->create(['content_item_not_applicable' => true]);
        $this->assertSame(15, app(MetadataCompletionService::class)->calculate($video)['percentage']);
    }

    public function test_command_and_authorized_paginated_review_page_work(): void
    {
        $channel = CreatorChannel::factory()->create();
        Subject::factory()->create(['creator_channel_id' => $channel->id, 'name' => 'SB19', 'normalized_name' => 'sb19', 'slug' => 'sb19']);
        CreatorVideo::factory()->for($channel, 'channel')->create(['title' => 'SB19 Went HARD']);
        $this->artisan('creator-intelligence:suggest-metadata', ['--channel' => $channel->id, '--dry-run' => true])->assertSuccessful();
        $this->assertDatabaseCount('metadata_suggestions', 0);
        $this->artisan('creator-intelligence:suggest-metadata', ['--channel' => $channel->id])->assertSuccessful();
        $this->get(route('superadmin.creator-intelligence.metadata-suggestions.index'))->assertRedirect();
        $admin = User::factory()->create(['can_manage_creator_intelligence' => true]);
        $this->actingAs($admin)
            ->get(route('superadmin.creator-intelligence.metadata-suggestions.index'))
            ->assertOk()->assertSee('Generate Suggestions')->assertSee('Approve all high-confidence in current filter')->assertSee('SB19 Went HARD');
        $this->post(route('superadmin.creator-intelligence.metadata-suggestions.bulk'), [
            'action' => 'approve_high', 'confirmed' => 1, 'creator_channel_id' => $channel->id,
        ])->assertRedirect()->assertSessionHas('success');
        $this->assertTrue(CreatorVideo::firstOrFail()->primarySubject()->exists());
    }

    private function suggestion(CreatorVideo $video, string $type, array $overrides = []): MetadataSuggestion
    {
        return MetadataSuggestion::create([...['creator_video_id' => $video->id, 'suggestion_type' => $type, 'suggested_display_value' => null, 'confidence' => 'high', 'confidence_score' => .95, 'rule_name' => 'test', 'evidence' => [], 'status' => 'pending', 'source_fingerprint' => str_repeat('a', 64)], ...$overrides]);
    }
}
