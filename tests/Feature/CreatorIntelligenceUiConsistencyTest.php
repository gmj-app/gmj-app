<?php

namespace Tests\Feature;

use App\Models\ContentItem;
use App\Models\CreatorChannel;
use App\Models\CreatorVideo;
use App\Models\Subject;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            ->assertSessionHasErrors(['name' => 'An Artist with this normalized name already exists for the channel.']);
        $this->actingAs($this->admin())->post(route('superadmin.creator-intelligence.content-items.store'), ['creator_channel_id' => $channel->id, 'name' => 'Release', 'is_active' => 1])
            ->assertSessionHasErrors(['name' => 'A Song with this normalized name already exists for the channel.']);
    }

    private function admin(): User
    {
        return User::factory()->create(['can_manage_creator_intelligence' => true]);
    }
}
