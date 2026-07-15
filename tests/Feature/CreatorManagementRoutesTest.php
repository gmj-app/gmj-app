<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\CreatorOwner;
use App\Models\CreatorTag;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CreatorManagementRoutesTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['filesystems.default' => 'public']);
    }

    public function test_creator_management_pages_require_owner_access(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $nonOwner = User::factory()->create();

        $routes = [
            route('creators.dashboard', $creator),
            route('creators.recommendations.index', $creator),
            route('creators.followers', $creator),
            route('creators.settings.edit', $creator),
        ];

        foreach ($routes as $route) {
            $this->get($route)->assertRedirect('/login');
        }

        foreach ($routes as $route) {
            $this->actingAs($nonOwner)->get($route)->assertForbidden();
        }
    }

    public function test_owner_can_view_dashboard_recommendations_followers_and_settings(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Dashboard recommendation',
        ]);
        $fan = User::factory()->create([
            'name' => 'Following Fan',
            'public_display_name' => 'Following Fan',
            'public_handle' => 'followingfan',
        ]);
        $favorite = CreatorFavorite::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $fan->id,
        ]);
        $favorite->timestamps = false;
        $favorite->forceFill([
            'created_at' => '2026-06-01 12:00:00',
            'updated_at' => '2026-06-01 12:00:00',
        ])->save();
        UserPick::factory()->create([
            'user_id' => $fan->id,
            'creator_id' => $creator->id,
            'recommendation_id' => $recommendation->id,
        ]);

        $this->actingAs($owner)
            ->get(route('creators.dashboard', $creator))
            ->assertOk()
            ->assertSee('Requests received')
            ->assertSee('Total votes');

        $this->get(route('creators.recommendations.index', $creator))
            ->assertOk()
            ->assertSee('Manage Requests')
            ->assertSee('Dashboard recommendation')
            ->assertSee('Title')
            ->assertSee('Artist/channel')
            ->assertSee('Submitted date')
            ->assertSee('Pending Review');

        $this->get(route('creators.followers', $creator))
            ->assertOk()
            ->assertSee('Following Fan')
            ->assertSee('1 follower')
            ->assertSee('Followed Jun 1, 2026');

        $this->get(route('creators.settings.edit', $creator))
            ->assertOk()
            ->assertSee('settings')
            ->assertSee('Branding')
            ->assertSee('Recommended: square image, at least 512x512.')
            ->assertSee('Recommended: wide image, around 1600x500 or larger.')
            ->assertSee('Review requests before they appear')
            ->assertSee('On — review first')
            ->assertSee('Off — appear immediately')
            ->assertSee('enctype="multipart/form-data"', false);
    }

    public function test_creator_management_headers_link_identity_to_public_page(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();

        foreach ([
            route('creators.dashboard', $creator),
            route('creators.recommendations.index', $creator),
            route('creators.followers', $creator),
            route('creators.settings.edit', $creator),
        ] as $url) {
            $this->actingAs($owner)
                ->get($url)
                ->assertOk()
                ->assertSee('aria-label="View JFragment public page"', false)
                ->assertSee('href="'.route('creator.queue', $creator).'"', false)
                ->assertSee('href="'.route('creators.settings.edit', $creator).'"', false)
                ->assertDontSee('View public page');
        }
    }

    public function test_dashboard_shows_requested_stats_and_latest_five_pending_recommendations(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $followers = User::factory()->count(2)->create();

        foreach ($followers as $follower) {
            CreatorFavorite::query()->create([
                'creator_id' => $creator->id,
                'user_id' => $follower->id,
            ]);
        }

        $visibleRecommendations = collect([
            Recommendation::factory()->create([
                'creator_id' => $creator->id,
                'status' => 'approved',
            ]),
            Recommendation::factory()->create([
                'creator_id' => $creator->id,
                'status' => 'coming_soon',
            ]),
            Recommendation::factory()->create([
                'creator_id' => $creator->id,
                'status' => 'scheduled',
            ]),
            Recommendation::factory()->create([
                'creator_id' => $creator->id,
                'status' => 'recorded',
            ]),
            Recommendation::factory()->create([
                'creator_id' => $creator->id,
                'status' => 'published',
            ]),
        ]);

        foreach ($visibleRecommendations as $recommendation) {
            $fan = User::factory()->create();
            UserPick::factory()->create([
                'user_id' => $fan->id,
                'creator_id' => $creator->id,
                'recommendation_id' => $recommendation->id,
            ]);
        }

        $hidden = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'hidden',
        ]);
        UserPick::factory()->create([
            'user_id' => User::factory(),
            'creator_id' => $creator->id,
            'recommendation_id' => $hidden->id,
        ]);

        foreach (range(1, 6) as $number) {
            Recommendation::factory()->create([
                'creator_id' => $creator->id,
                'title' => "Pending request {$number}",
                'artist' => "Artist {$number}",
                'status' => 'pending',
                'created_at' => now()->addMinutes($number),
            ]);
        }

        $this->actingAs($owner)
            ->get(route('creators.dashboard', $creator))
            ->assertOk()
            ->assertSeeInOrder([
                'Requests received',
                '12',
                'Pending review',
                '6',
                'Total votes',
                '5',
                'Followers',
                '2',
                'Published requests',
                '1',
            ])
            ->assertSee('Needs action')
            ->assertSeeInOrder([
                'Pending request 6',
                'Pending request 5',
                'Pending request 4',
                'Pending request 3',
                'Pending request 2',
            ])
            ->assertDontSee('Pending request 1')
            ->assertSee('Approve')
            ->assertSee('Pass')
            ->assertSee('Hide')
            ->assertSee('Open YouTube')
            ->assertSee('aria-label="View JFragment public page"', false)
            ->assertSee(route('creator.queue', $creator), false)
            ->assertDontSee('View public page')
            ->assertSee('Settings');
    }

    public function test_owner_can_update_and_hide_a_scoped_recommendation(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patch(route('creators.recommendations.status', [$creator, $recommendation]), [
                'status' => 'scheduled',
                'scheduled_for' => '2026-07-04T19:30',
            ])
            ->assertRedirect();

        $recommendation->refresh();
        $this->assertSame('scheduled', $recommendation->status);
        $this->assertSame('2026-07-04 19:30', $recommendation->scheduled_for->format('Y-m-d H:i'));
        $this->assertSame($owner->id, $recommendation->moderated_by);
        $this->assertNotNull($recommendation->moderated_at);

        $this->patch(route('creators.recommendations.hide', [$creator, $recommendation]), [
            'moderation_reason' => 'inappropriate',
            'moderation_note' => 'Hidden by the creator.',
        ])->assertRedirect();

        $this->assertDatabaseHas('recommendations', [
            'id' => $recommendation->id,
            'status' => 'hidden',
            'moderation_reason' => 'inappropriate',
            'moderation_note' => 'Hidden by the creator.',
            'moderated_by' => $owner->id,
        ]);
    }

    public function test_recommendation_management_search_filters_and_sorts(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $olderLowVote = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Quiet folk ballad',
            'artist' => 'Needle North',
            'category' => 'music',
            'status' => 'approved',
            'youtube_url' => 'https://www.youtube.com/watch?v=FOLK0000001',
            'created_at' => now()->subDays(3),
        ]);
        $matchingHighVote = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Massive synth anthem',
            'channel_title' => 'Retro Channel',
            'category' => 'music',
            'status' => 'approved',
            'youtube_url' => 'https://www.youtube.com/watch?v=SYNTH00001',
            'created_at' => now()->subDay(),
        ]);
        $documentary = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Ocean documentary',
            'category' => 'documentary',
            'status' => 'hidden',
            'created_at' => now(),
        ]);
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Already watched performance',
            'category' => 'music',
            'status' => 'already_seen',
            'created_at' => now()->subHours(2),
        ]);

        $this->addPicks($creator, $olderLowVote, 1);
        $this->addPicks($creator, $matchingHighVote, 3);

        $this->actingAs($owner)
            ->get(route('creators.recommendations.index', [
                'creator' => $creator,
                'q' => 'synth',
                'status' => 'approved',
                'category' => 'music',
                'sort' => 'votes',
            ]))
            ->assertOk()
            ->assertSee('Massive synth anthem')
            ->assertDontSee('Quiet folk ballad')
            ->assertDontSee('Ocean documentary')
            ->assertSee('value="synth"', false)
            ->assertSee('Most votes');

        $this->get(route('creators.recommendations.index', [
            'creator' => $creator,
            'sort' => 'newest',
        ]))
            ->assertOk()
            ->assertSeeInOrder([
                'Ocean documentary',
                'Massive synth anthem',
                'Quiet folk ballad',
            ]);

        $this->get(route('creators.recommendations.index', [
            'creator' => $creator,
            'status' => 'already_seen',
        ]))
            ->assertOk()
            ->assertSee('Already watched performance')
            ->assertSee('Already Seen')
            ->assertDontSee('Massive synth anthem');

        $this->assertSame(4, $documentary->creator->recommendations()->count());
    }

    public function test_recommendation_management_table_shows_thumbnails_only_for_youtube_videos(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $youtubeRecommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_type' => 'youtube',
            'title' => 'Video with thumbnail',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'youtube_video_id' => 'dQw4w9WgXcQ',
        ]);
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_type' => 'topic',
            'title' => 'Topic without thumbnail',
            'youtube_url' => null,
            'youtube_video_id' => null,
        ]);

        $this->actingAs($owner)
            ->get(route('creators.recommendations.index', $creator))
            ->assertOk()
            ->assertSee('Video with thumbnail')
            ->assertSee('Topic without thumbnail')
            ->assertSee($youtubeRecommendation->youtubeThumbnailUrl(), false)
            ->assertSee('alt="Thumbnail for Video with thumbnail"', false)
            ->assertSee('onerror="this.parentElement.remove()"', false)
            ->assertDontSee('alt="Thumbnail for Topic without thumbnail"', false);
    }

    public function test_status_update_publishes_with_reaction_fields_and_default_published_at(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        Http::fake([
            '*youtube.com/oembed*' => Http::response([
                'title' => 'Creator published video',
                'author_name' => 'Creator Channel',
            ]),
        ]);
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'recorded',
            'published_at' => null,
            'published_reaction_url' => null,
        ]);

        $this->actingAs($owner)
            ->patch(route('creators.recommendations.status', [$creator, $recommendation]), [
                'status' => 'published',
                'published_reaction_url' => 'https://www.youtube.com/watch?v=REACTION001',
            ])
            ->assertRedirect();

        $recommendation->refresh();
        $this->assertSame('published', $recommendation->status);
        $this->assertSame('https://www.youtube.com/watch?v=REACTION001', $recommendation->published_reaction_url);
        $this->assertSame('Creator published video', $recommendation->published_title);
        $this->assertSame('Creator Channel', $recommendation->published_channel);
        $this->assertSame('REACTION001', $recommendation->published_video_id);
        $this->assertSame('https://img.youtube.com/vi/REACTION001/hqdefault.jpg', $recommendation->published_thumbnail_url);
        $this->assertNotNull($recommendation->published_at);
        $this->assertSame($owner->id, $recommendation->moderated_by);
    }

    public function test_leaving_the_active_upvote_pool_releases_upvotes_for_every_closed_status(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();

        foreach (['coming_soon', 'scheduled', 'recorded', 'published', 'already_seen', 'passed', 'hidden'] as $status) {
            $recommendation = Recommendation::factory()->create([
                'creator_id' => $creator->id,
                'status' => 'approved',
            ]);
            $this->addPicks($creator, $recommendation, 2);

            $response = $this->actingAs($owner)
                ->patch(route('creators.recommendations.status', [$creator, $recommendation]), [
                    'status' => $status,
                ]);

            $response
                ->assertRedirect()
                ->assertSessionHas(
                    'success',
                    'Status updated. 2 votes are no longer active and returned to Guides.',
                );

            $this->assertDatabaseHas('user_picks', [
                'recommendation_id' => $recommendation->id,
            ]);
        }
    }

    public function test_active_status_transitions_keep_existing_upvotes(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'pending',
        ]);
        $this->addPicks($creator, $recommendation, 2);

        $this->actingAs($owner)
            ->patch(route('creators.recommendations.status', [$creator, $recommendation]), [
                'status' => 'approved',
            ])
            ->assertSessionHas('success', 'Status updated.');

        $this->assertSame(2, $recommendation->userPicks()->count());

        $this->patch(route('creators.recommendations.status', [$creator, $recommendation]), [
            'status' => 'pending',
        ])->assertSessionHas('success', 'Status updated.');

        $this->assertSame(2, $recommendation->userPicks()->count());
    }

    public function test_full_edit_and_hide_paths_release_upvotes(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $editedRecommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);
        $hiddenRecommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);
        $this->addPicks($creator, $editedRecommendation, 1);
        $this->addPicks($creator, $hiddenRecommendation, 1);

        $this->actingAs($owner)
            ->patch(route('creators.recommendations.update', [$creator, $editedRecommendation]), [
                'title' => $editedRecommendation->title,
                'status' => 'passed',
            ])
            ->assertSessionHas(
                'success',
                'Request updated. 1 vote is no longer active and returned to Guides.',
            );

        $this->patch(route('creators.recommendations.hide', [$creator, $hiddenRecommendation]))
            ->assertSessionHas(
                'success',
                'Request hidden. 1 vote is no longer active and returned to Guides.',
            );

        $this->assertDatabaseHas('user_picks', [
            'recommendation_id' => $editedRecommendation->id,
        ]);
        $this->assertDatabaseHas('user_picks', [
            'recommendation_id' => $hiddenRecommendation->id,
        ]);
    }

    public function test_owner_can_update_details_and_permanently_delete_a_recommendation(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Old title',
        ]);

        $this->actingAs($owner)
            ->patch(route('creators.recommendations.update', [$creator, $recommendation]), [
                'title' => 'Updated title',
                'artist' => 'Updated artist',
                'channel_title' => 'Updated channel',
                'category' => 'music',
                'reason' => 'Updated fan note.',
                'youtube_url' => 'https://www.youtube.com/watch?v=UPDATED0001',
                'status' => 'approved',
                'moderation_note' => 'Looks good.',
                'is_pinned' => true,
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('recommendations', [
            'id' => $recommendation->id,
            'title' => 'Updated title',
            'artist' => 'Updated artist',
            'channel_title' => 'Updated channel',
            'category' => 'music',
            'reason' => 'Updated fan note.',
            'youtube_url' => 'https://www.youtube.com/watch?v=UPDATED0001',
            'status' => 'approved',
            'moderation_note' => 'Looks good.',
            'is_pinned' => true,
            'moderated_by' => $owner->id,
        ]);

        $this->delete(route('creators.recommendations.destroy', [$creator, $recommendation]))
            ->assertRedirect(route('creators.recommendations.index', $creator));

        $this->assertDatabaseMissing('recommendations', ['id' => $recommendation->id]);
    }

    public function test_owner_can_create_normalize_and_assign_creator_scoped_tags(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $otherCreator = Creator::factory()->create();
        CreatorTag::query()->create([
            'creator_id' => $otherCreator->id,
            'name' => 'Live Performance',
            'slug' => 'live-performance',
        ]);
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Tagged recommendation',
            'status' => 'approved',
        ]);

        $this->actingAs($owner)
            ->patch(route('creators.recommendations.update', [$creator, $recommendation]), [
                'title' => $recommendation->title,
                'status' => $recommendation->status,
                'tags' => ' OPM, live   performance, OPM ',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('creator_tags', [
            'creator_id' => $creator->id,
            'name' => 'OPM',
            'slug' => 'opm',
        ]);
        $this->assertDatabaseHas('creator_tags', [
            'creator_id' => $creator->id,
            'name' => 'live performance',
            'slug' => 'live-performance',
        ]);
        $this->assertSame(2, $recommendation->fresh()->creatorTags()->count());
        $this->assertSame(
            2,
            CreatorTag::query()->where('slug', 'live-performance')->count(),
        );

        $this->get(route('creators.recommendations.index', [
            'creator' => $creator,
            'tag' => 'opm',
        ]))
            ->assertOk()
            ->assertSee('Tagged recommendation')
            ->assertSee('OPM');
    }

    public function test_non_owner_cannot_assign_or_create_tags(): void
    {
        [$creator] = $this->creatorWithOwner();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);

        $this->actingAs(User::factory()->create())
            ->patch(route('creators.recommendations.update', [$creator, $recommendation]), [
                'title' => $recommendation->title,
                'status' => $recommendation->status,
                'tags' => 'Unauthorized Tag',
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('creator_tags', [
            'creator_id' => $creator->id,
            'slug' => 'unauthorized-tag',
        ]);
    }

    public function test_recommendation_tag_assignment_is_limited_to_five(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);

        $this->actingAs($owner)
            ->patch(route('creators.recommendations.update', [$creator, $recommendation]), [
                'title' => $recommendation->title,
                'status' => $recommendation->status,
                'tags' => 'One, Two, Three, Four, Five, Six',
            ])
            ->assertSessionHasErrors([
                'tags' => 'Add no more than 5 tags to a request.',
            ]);

        $this->assertDatabaseCount('creator_tags', 0);
        $this->assertDatabaseCount('recommendation_tag', 0);
    }

    public function test_scoped_binding_prevents_mutating_another_creators_recommendation(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $otherRecommendation = Recommendation::factory()->create();

        $this->actingAs($owner)
            ->patch(route('creators.recommendations.status', [$creator, $otherRecommendation]), [
                'status' => 'approved',
            ])
            ->assertNotFound();
    }

    public function test_owner_can_update_settings_and_deactivate_without_deleting_creator(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();

        $this->actingAs($owner)
            ->patch(route('creators.settings.update', $creator), [
                'display_name' => 'Updated Creator',
                'slug' => 'updated-creator',
                'youtube_channel_url' => 'https://www.youtube.com/@updatedcreator',
                'bio' => 'Updated creator bio.',
                'submission_instructions' => 'Keep suggestions concise.',
                'submissions_open' => true,
                'recommendation_approval_mode' => 'auto',
            ])
            ->assertRedirect(route('creators.settings.edit', $creator->fresh()));

        $creator->refresh();
        $this->assertSame('updated-creator', $creator->slug);
        $this->assertSame('https://www.youtube.com/@updatedcreator', $creator->youtube_channel_url);
        $this->assertSame('https://www.youtube.com/@updatedcreator', $creator->channel_url);
        $this->assertSame('Updated creator bio.', $creator->bio);
        $this->assertSame('Keep suggestions concise.', $creator->submission_instructions);
        $this->assertTrue($creator->submissions_open);
        $this->assertSame('auto', $creator->recommendation_approval_mode);

        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
        ]);
        $fan = User::factory()->create();
        $favorite = CreatorFavorite::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $fan->id,
        ]);
        $pick = UserPick::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_id' => $recommendation->id,
            'user_id' => $fan->id,
        ]);

        $this->patch(route('creators.deactivate', $creator))
            ->assertRedirect(route('creators.dashboard', $creator));

        $this->assertDatabaseHas('creators', [
            'id' => $creator->id,
            'status' => 'inactive',
            'submissions_open' => false,
        ]);
        $this->assertNotNull($creator->fresh()->deactivated_at);
        $this->assertDatabaseHas('recommendations', ['id' => $recommendation->id]);
        $this->assertDatabaseHas('user_picks', ['id' => $pick->id]);
        $this->assertDatabaseHas('creator_favorites', ['id' => $favorite->id]);
        $this->assertDatabaseHas('creator_owners', [
            'creator_id' => $creator->id,
            'user_id' => $owner->id,
        ]);
    }

    public function test_creator_settings_validate_required_unique_and_limited_fields(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        Creator::factory()->create(['slug' => 'taken-slug']);

        $this->actingAs($owner)
            ->patch(route('creators.settings.update', $creator), [
                'display_name' => '',
                'slug' => 'taken-slug',
                'youtube_channel_url' => 'not-a-url',
                'bio' => str_repeat('a', 2001),
                'submission_instructions' => str_repeat('b', 2001),
                'submissions_open' => 'not-boolean',
                'recommendation_approval_mode' => 'sometimes',
            ])
            ->assertSessionHasErrors([
                'display_name',
                'slug',
                'youtube_channel_url',
                'bio',
                'submission_instructions',
                'submissions_open',
                'recommendation_approval_mode',
            ]);
    }

    public function test_changing_approval_mode_does_not_change_existing_recommendations(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $existingRecommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'pending',
        ]);

        $this->actingAs($owner)
            ->patch(route('creators.settings.update', $creator), [
                ...$this->validSettingsPayload($creator),
                'recommendation_approval_mode' => 'auto',
            ])
            ->assertRedirect();

        $this->assertSame('auto', $creator->fresh()->recommendation_approval_mode);
        $this->assertSame('pending', $existingRecommendation->fresh()->status);
    }

    public function test_owner_can_upload_and_replace_creator_branding_without_removal(): void
    {
        Storage::fake('creator_uploads');

        [$creator, $owner] = $this->creatorWithOwner();

        $this->actingAs($owner)
            ->patch(route('creators.settings.update', $creator), [
                ...$this->validSettingsPayload($creator),
                'avatar' => UploadedFile::fake()->image('avatar.jpg', 800, 800),
                'hero' => UploadedFile::fake()->image('hero.jpg', 1600, 500),
            ])
            ->assertRedirect(route('creators.settings.edit', $creator));

        $creator->refresh();
        $firstAvatarPath = $creator->avatar_path;
        $firstHeroPath = $creator->hero_path;

        $this->assertStringStartsWith("creators/{$creator->id}/avatars/", $firstAvatarPath);
        $this->assertStringStartsWith("creators/{$creator->id}/heroes/", $firstHeroPath);
        Storage::disk('creator_uploads')->assertExists([$firstAvatarPath, $firstHeroPath]);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee($creator->avatar_url, false);

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee($creator->avatar_url, false)
            ->assertSee($creator->hero_url, false);

        $this->patch(route('creators.settings.update', $creator), [
            ...$this->validSettingsPayload($creator),
            'avatar' => UploadedFile::fake()->image('replacement.png', 600, 600),
            'hero' => UploadedFile::fake()->image('replacement-hero.png', 1800, 600),
        ])->assertRedirect();

        $creator->refresh();
        Storage::disk('creator_uploads')->assertMissing([$firstAvatarPath, $firstHeroPath]);
        Storage::disk('creator_uploads')->assertExists([$creator->avatar_path, $creator->hero_path]);

        $this->patch(route('creators.settings.update', $creator), [
            ...$this->validSettingsPayload($creator),
            'remove_avatar' => true,
            'remove_hero' => true,
        ])->assertRedirect();

        $creator->refresh();
        $this->assertNotNull($creator->avatar_path);
        $this->assertNotNull($creator->hero_path);
        Storage::disk('creator_uploads')->assertExists([$creator->avatar_path, $creator->hero_path]);
    }

    public function test_creator_branding_uploads_are_validated(): void
    {
        Storage::fake('creator_uploads');

        [$creator, $owner] = $this->creatorWithOwner();

        $this->actingAs($owner)
            ->patch(route('creators.settings.update', $creator), [
                ...$this->validSettingsPayload($creator),
                'avatar' => UploadedFile::fake()->create('avatar.svg', 10, 'image/svg+xml'),
                'hero' => UploadedFile::fake()->create('hero.jpg', 5121, 'image/jpeg'),
            ])
            ->assertSessionHasErrors(['avatar', 'hero']);

        $this->assertNull($creator->fresh()->avatar_path);
        $this->assertNull($creator->fresh()->hero_path);
    }

    public function test_failed_replacement_preserves_existing_creator_media(): void
    {
        Storage::fake('creator_uploads');
        [$creator, $owner] = $this->creatorWithOwner();

        $this->actingAs($owner)->patch(route('creators.settings.update', $creator), [
            ...$this->validSettingsPayload($creator),
            'avatar' => UploadedFile::fake()->image('existing.jpg', 512, 512),
        ])->assertRedirect();
        $existingPath = $creator->fresh()->avatar_path;

        $this->patch(route('creators.settings.update', $creator), [
            ...$this->validSettingsPayload($creator),
            'avatar' => UploadedFile::fake()->create('replacement.svg', 10, 'image/svg+xml'),
        ])->assertSessionHasErrors('avatar');

        $this->assertSame($existingPath, $creator->fresh()->avatar_path);
        Storage::disk('creator_uploads')->assertExists($existingPath);
    }

    public function test_creator_branding_upload_works_when_windows_realpath_resolution_fails(): void
    {
        Storage::fake('creator_uploads');

        [$creator, $owner] = $this->creatorWithOwner();
        $source = UploadedFile::fake()->image('avatar.jpg', 512, 512);
        $avatar = new class($source->getPathname(), 'avatar.jpg', 'image/jpeg', UPLOAD_ERR_OK, true) extends UploadedFile
        {
            public function getRealPath(): string|false
            {
                return false;
            }
        };

        $this->actingAs($owner)
            ->patch(route('creators.settings.update', $creator), [
                ...$this->validSettingsPayload($creator),
                'avatar' => $avatar,
            ])
            ->assertRedirect(route('creators.settings.edit', $creator));

        $creator->refresh();

        $this->assertNotNull($creator->avatar_path);
        Storage::disk('creator_uploads')->assertExists($creator->avatar_path);
    }

    public function test_creator_branding_uploads_use_dedicated_creator_disk(): void
    {
        Storage::fake('creator_uploads');

        [$creator, $owner] = $this->creatorWithOwner();

        $this->actingAs($owner)
            ->patch(route('creators.settings.update', $creator), [
                ...$this->validSettingsPayload($creator),
                'avatar' => UploadedFile::fake()->image('avatar.jpg', 512, 512),
            ])
            ->assertRedirect(route('creators.settings.edit', $creator));

        $creator->refresh();

        $this->assertNotNull($creator->avatar_path);
        $this->assertStringStartsWith("creators/{$creator->id}/avatars/", $creator->avatar_path);
        Storage::disk('creator_uploads')->assertExists($creator->avatar_path);
        $this->assertSame(Storage::disk('creator_uploads')->url($creator->avatar_path), $creator->avatar_url);
    }

    public function test_non_owner_cannot_update_creator_branding(): void
    {
        Storage::fake('creator_uploads');

        [$creator] = $this->creatorWithOwner();
        $nonOwner = User::factory()->create();

        $this->actingAs($nonOwner)
            ->patch(route('creators.settings.update', $creator), [
                ...$this->validSettingsPayload($creator),
                'avatar' => UploadedFile::fake()->image('avatar.jpg'),
            ])
            ->assertForbidden();

        $this->assertNull($creator->fresh()->avatar_path);
        Storage::disk('creator_uploads')->assertDirectoryEmpty("creators/{$creator->id}");
    }

    public function test_creator_branding_helpers_prioritize_local_files_and_generate_initials(): void
    {
        Storage::fake('creator_uploads');

        Storage::disk('creator_uploads')->put('creators/1/avatars/avatar.jpg', 'avatar');
        Storage::disk('creator_uploads')->put('creators/1/heroes/hero.jpg', 'hero');

        $creator = Creator::factory()->create([
            'id' => 1,
            'display_name' => 'Culture Curious',
            'avatar_path' => 'creators/1/avatars/avatar.jpg',
            'hero_path' => 'creators/1/heroes/hero.jpg',
            'youtube_thumbnail_url' => 'https://example.com/youtube-avatar.jpg',
            'youtube_banner_url' => 'https://example.com/youtube-banner.jpg',
        ]);

        $this->assertSame('CC', $creator->initials);
        $this->assertSame(
            Storage::disk('creator_uploads')->url($creator->avatar_path),
            $creator->avatar_url,
        );
        $this->assertSame(
            Storage::disk('creator_uploads')->url($creator->hero_path),
            $creator->hero_url,
        );

        Storage::disk('creator_uploads')->delete([$creator->avatar_path, $creator->hero_path]);

        $this->assertSame('https://example.com/youtube-avatar.jpg', $creator->avatar_url);
        $this->assertSame('https://example.com/youtube-banner.jpg', $creator->hero_url);

        $creator->forceFill([
            'display_name' => 'JFragment',
            'youtube_thumbnail_url' => null,
            'youtube_banner_url' => null,
        ]);

        $this->assertSame('JF', $creator->initials);
        $this->assertNull($creator->avatar_url);
        $this->assertNull($creator->hero_url);
    }

    /**
     * @return array{Creator, User}
     */
    private function creatorWithOwner(): array
    {
        $creator = Creator::factory()->create([
            'slug' => 'jfragment',
            'display_name' => 'JFragment',
        ]);
        $owner = User::factory()->create();

        CreatorOwner::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        return [$creator, $owner];
    }

    private function addPicks(Creator $creator, Recommendation $recommendation, int $count): void
    {
        User::factory()
            ->count($count)
            ->create()
            ->each(fn (User $user) => UserPick::factory()->create([
                'user_id' => $user->id,
                'creator_id' => $creator->id,
                'recommendation_id' => $recommendation->id,
            ]));
    }

    /**
     * @return array<string, mixed>
     */
    private function validSettingsPayload(Creator $creator): array
    {
        return [
            'display_name' => $creator->display_name,
            'slug' => $creator->slug,
            'youtube_channel_url' => $creator->youtube_channel_url,
            'bio' => $creator->bio,
            'submission_instructions' => $creator->submission_instructions,
            'submissions_open' => true,
            'recommendation_approval_mode' => $creator->recommendation_approval_mode,
        ];
    }
}
