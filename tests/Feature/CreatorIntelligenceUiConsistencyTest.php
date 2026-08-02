<?php

namespace Tests\Feature;

use App\Models\ContentItem;
use App\Models\CreatorChannel;
use App\Models\CreatorVideo;
use App\Models\Subject;
use App\Models\User;
use App\Models\VideoPerformanceSnapshot;
use App\Support\CreatorIntelligenceLabels;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

class CreatorIntelligenceUiConsistencyTest extends TestCase
{
    use RefreshDatabase;

    public function test_shared_layout_exposes_scoped_dark_accessible_primitives(): void
    {
        $response = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.overview'));

        $response->assertOk()
            ->assertSee('data-creator-intelligence', false)
            ->assertSee('ci-page')
            ->assertSee('ci-tabs')
            ->assertSee('ci-empty-state');

        $css = file_get_contents(resource_path('css/app.css'));
        $this->assertStringContainsString('.ci-control', $css);
        $this->assertStringContainsString('dark:disabled:text-slate-400', $css);
        $this->assertStringContainsString('.ci-alert-danger', $css);
        $this->assertStringContainsString(':focus-visible', $css);
    }

    public function test_label_resolver_handles_defaults_irregular_plurals_and_count_grammar(): void
    {
        $defaults = CreatorIntelligenceLabels::for(null);
        $this->assertSame('Subjects', $defaults->subjectPlural());
        $this->assertSame('Content Items', $defaults->contentItemPlural());
        $this->assertSame('Categories', $defaults->categoryPlural());

        $labels = CreatorIntelligenceLabels::for(CreatorChannel::factory()->create(['subject_label' => 'Person', 'content_item_label' => 'Story']));
        $this->assertSame('People', $labels->subjectPlural());
        $this->assertSame('Stories', $labels->contentItemPlural());
        $this->assertSame('1 person', $labels->subjectCount(1));
        $this->assertSame('2 people', $labels->subjectCount(2));
    }

    public function test_channel_terminology_is_used_in_navigation_subject_and_content_item_forms(): void
    {
        $channel = CreatorChannel::factory()->create([
            'subject_label' => 'Artist',
            'content_item_label' => 'Song',
            'category_label' => 'Genre',
        ]);

        $subjectResponse = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.subjects.create', ['creator_channel_id' => $channel->id]));
        $subjectResponse->assertOk()->assertSee('Artists')->assertSee('Songs')->assertSee('Add Artist')->assertSee('Artist Name');

        $itemResponse = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.content-items.create', ['creator_channel_id' => $channel->id]));
        $itemResponse->assertOk()->assertSee('Add Song')->assertSee('Song Name')->assertSee('Artist');
    }

    public function test_subject_and_content_item_forms_have_explicit_labels_help_and_field_errors(): void
    {
        $channel = CreatorChannel::factory()->create();

        $this->actingAs($this->admin())->from(route('superadmin.creator-intelligence.subjects.create'))->post(route('superadmin.creator-intelligence.subjects.store'), [
            'creator_channel_id' => $channel->id,
            'name' => '',
            'is_active' => 1,
        ])->assertSessionHasErrors('name');

        $subject = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.subjects.create', ['creator_channel_id' => $channel->id]));
        $subject->assertSee('for="subject-name"', false)->assertSee('id="subject-name"', false)->assertSee('aria-describedby="subject-country-help"', false)->assertSee('ci-error');

        $item = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.content-items.create', ['creator_channel_id' => $channel->id]));
        $item->assertSee('for="content-name"', false)->assertSee('id="content-name"', false)->assertSee('name="content_item_type"', false);
    }

    public function test_import_and_video_filters_have_visible_labels_and_shared_containers(): void
    {
        $channel = CreatorChannel::factory()->create();
        CreatorVideo::factory()->for($channel, 'channel')->create();

        $imports = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.imports.index'));
        $imports->assertOk()->assertSee('for="import-channel"', false)->assertSee('ci-filter-panel')->assertSee('ci-table-container');

        $upload = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.imports.create'));
        $upload->assertOk()->assertSee('for="import-file"', false)->assertSee('aria-describedby="import-file-help"', false)->assertSee('ci-form-card');

        $videos = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.videos.index'));
        $videos->assertOk()->assertSee('for="video-search"', false)->assertSee('for="video-profile"', false)->assertSee('for="video-channel"', false)->assertSee('ci-filter-panel');
    }

    public function test_configured_labels_are_used_in_duplicate_validation_messages(): void
    {
        $channel = CreatorChannel::factory()->create(['subject_label' => 'Artist', 'content_item_label' => 'Song']);
        Subject::factory()->for($channel, 'creatorChannel')->create(['name' => 'Existing', 'normalized_name' => 'existing', 'slug' => 'existing']);
        ContentItem::factory()->for($channel, 'creatorChannel')->create(['name' => 'Release', 'normalized_name' => 'release', 'slug' => 'release']);

        $this->actingAs($this->admin())->post(route('superadmin.creator-intelligence.subjects.store'), ['creator_channel_id' => $channel->id, 'name' => 'Existing', 'is_active' => 1])
            ->assertSessionHasErrors(['name' => 'This artist already exists for the selected creator channel.']);
        $this->actingAs($this->admin())->post(route('superadmin.creator-intelligence.content-items.store'), ['creator_channel_id' => $channel->id, 'name' => 'Release', 'is_active' => 1])
            ->assertSessionHasErrors(['name' => 'This song already exists for the selected creator channel.']);
    }

    public function test_display_names_preserve_capitalization_while_normalization_remains_case_insensitive(): void
    {
        $channel = CreatorChannel::factory()->create(['subject_label' => 'Artist']);
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('superadmin.creator-intelligence.subjects.store'), [
            'creator_channel_id' => $channel->id,
            'name' => '  SB19  ',
            'is_active' => 1,
        ])->assertSessionHas('success', 'Artist created.');

        $subject = Subject::query()->sole();
        $this->assertSame('SB19', $subject->name);
        $this->assertSame('sb19', $subject->normalized_name);
        $this->actingAs($admin)->get(route('superadmin.creator-intelligence.subjects.show', $subject))->assertOk()->assertSee('SB19');

        $subject->update(['name' => 'sb19']);
        $this->actingAs($admin)->put(route('superadmin.creator-intelligence.subjects.update', $subject), [
            'creator_channel_id' => $channel->id,
            'name' => 'SB19',
            'is_active' => 1,
        ])->assertSessionHas('success', 'Artist updated.');
        $this->assertSame('SB19', $subject->fresh()->name);

        $this->actingAs($admin)->post(route('superadmin.creator-intelligence.subjects.store'), [
            'creator_channel_id' => $channel->id,
            'name' => 'sb19',
            'is_active' => 1,
        ])->assertSessionHasErrors(['name' => 'This artist already exists for the selected creator channel.']);

        $this->actingAs($admin)->post(route('superadmin.creator-intelligence.content-items.store'), [
            'creator_channel_id' => $channel->id,
            'name' => '  Akin Ka Na Lang  ',
            'is_active' => 1,
        ])->assertSessionHas('success', 'Content Item created.');
        $item = ContentItem::query()->sole();
        $this->assertSame('Akin Ka Na Lang', $item->name);
        $this->assertSame('akin ka na lang', $item->normalized_name);
    }

    public function test_detail_pages_use_record_channel_labels_and_singular_plural_counts(): void
    {
        $channel = CreatorChannel::factory()->create(['subject_label' => 'Artist', 'content_item_label' => 'Song']);
        $subject = Subject::factory()->for($channel, 'creatorChannel')->create(['name' => 'SB19', 'normalized_name' => 'sb19']);
        $song = ContentItem::factory()->for($channel, 'creatorChannel')->for($subject)->create(['name' => 'Gento', 'normalized_name' => 'gento']);

        $subjectPage = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.subjects.show', $subject));
        $subjectPage->assertOk()->assertSee('Artist')->assertSee('Edit Artist')->assertSee('Artist Aliases')->assertSee('0 videos')->assertSee('1 song')->assertSee('No videos assigned to this artist.');

        $songPage = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.content-items.show', $song));
        $songPage->assertOk()->assertSee('Song')->assertSee('Edit Song')->assertSee('Related Artist: SB19')->assertSee('0 videos')->assertSee('No videos assigned to this song.');
    }

    public function test_mixed_channel_indexes_use_generic_headings_and_channel_specific_row_actions(): void
    {
        $artistChannel = CreatorChannel::factory()->create(['subject_label' => 'Artist', 'content_item_label' => 'Song']);
        $topicChannel = CreatorChannel::factory()->create(['subject_label' => 'Topic', 'content_item_label' => 'Episode']);
        Subject::factory()->for($artistChannel, 'creatorChannel')->create();
        Subject::factory()->for($topicChannel, 'creatorChannel')->create();
        ContentItem::factory()->for($artistChannel, 'creatorChannel')->create();

        $subjects = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.subjects.index'));
        $subjects->assertOk()->assertSee('Subjects')->assertSee('Add Subject')->assertSee('Edit Artist')->assertSee('Edit Topic');

        $items = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.content-items.index'));
        $items->assertOk()->assertSee('Content Items')->assertSee('Add Content Item')->assertSee('Edit Song');
    }

    public function test_repair_command_is_dry_run_safe_deterministic_and_idempotent(): void
    {
        $channel = CreatorChannel::factory()->create();
        $subject = Subject::factory()->for($channel, 'creatorChannel')->create(['name' => 'sb19', 'normalized_name' => 'sb19']);
        $video = CreatorVideo::factory()->for($channel, 'channel')->create(['title' => 'SB19 performs live']);
        $video->subjects()->attach($subject, ['relationship_type' => 'primary', 'is_primary' => true]);

        $this->assertSame(0, Artisan::call('creator-intelligence:repair-display-names', ['--subject' => $subject->id, '--dry-run' => true]));
        $this->assertStringContainsString('Recoverable: 1', Artisan::output());
        $this->assertSame('sb19', $subject->fresh()->name);

        $this->assertSame(0, Artisan::call('creator-intelligence:repair-display-names', ['--subject' => $subject->id]));
        $this->assertStringContainsString('Updated: 1', Artisan::output());
        $this->assertSame('SB19', $subject->fresh()->name);
        $this->assertSame('sb19', $subject->fresh()->normalized_name);

        $this->assertSame(0, Artisan::call('creator-intelligence:repair-display-names', ['--subject' => $subject->id]));
        $this->assertStringContainsString('Updated: 0', Artisan::output());
        $this->assertSame('SB19', $subject->fresh()->name);
    }

    public function test_analytics_and_export_use_preserved_subject_display_name(): void
    {
        $channel = CreatorChannel::factory()->create();
        $subject = Subject::factory()->for($channel, 'creatorChannel')->create(['name' => '4TH IMPACT', 'normalized_name' => '4th impact']);
        $video = CreatorVideo::factory()->for($channel, 'channel')->create();
        $video->subjects()->attach($subject, ['relationship_type' => 'primary', 'is_primary' => true]);
        VideoPerformanceSnapshot::factory()->for($video, 'video')->create(['views' => 100]);

        $page = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.analytics.report', ['report' => 'subjects', 'creator_channel_id' => $channel->id, 'minimum_sample_size' => 1]));
        $page->assertOk()->assertSee('4TH IMPACT');

        $export = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.analytics.export', ['report' => 'subjects', 'creator_channel_id' => $channel->id, 'minimum_sample_size' => 1]));
        $this->assertStringContainsString('4TH IMPACT', $export->streamedContent());
    }

    public function test_repair_command_does_not_guess_between_capitalization_variants(): void
    {
        $channel = CreatorChannel::factory()->create();
        $subject = Subject::factory()->for($channel, 'creatorChannel')->create(['name' => 'sb19', 'normalized_name' => 'sb19']);
        foreach (['SB19 live', 'Sb19 interview'] as $title) {
            $video = CreatorVideo::factory()->for($channel, 'channel')->create(['title' => $title]);
            $video->subjects()->attach($subject, ['relationship_type' => 'featured', 'is_primary' => false]);
        }

        $this->assertSame(0, Artisan::call('creator-intelligence:repair-display-names', ['--subject' => $subject->id]));
        $this->assertStringContainsString('Ambiguous: 1', Artisan::output());
        $this->assertSame('sb19', $subject->fresh()->name);
    }

    public function test_repair_command_can_scope_and_restore_a_content_item_name(): void
    {
        $channel = CreatorChannel::factory()->create();
        $item = ContentItem::factory()->for($channel, 'creatorChannel')->create(['name' => 'akin ka na lang', 'normalized_name' => 'akin ka na lang']);
        $video = CreatorVideo::factory()->for($channel, 'channel')->create(['title' => 'Akin Ka Na Lang reaction']);
        $video->contentItems()->attach($item, ['is_primary' => true]);

        $this->assertSame(0, Artisan::call('creator-intelligence:repair-display-names', ['--channel' => $channel->id, '--content-item' => $item->id]));
        $this->assertStringContainsString('Updated: 1', Artisan::output());
        $this->assertSame('Akin Ka Na Lang', $item->fresh()->name);
        $this->assertSame('akin ka na lang', $item->fresh()->normalized_name);
    }

    private function admin(): User
    {
        return User::factory()->create(['can_manage_creator_intelligence' => true]);
    }
}
