<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\CreatorOwner;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SubmitRecommendationTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_or_submit_recommendations(): void
    {
        $this->get('/jfragment/submit')->assertRedirect('/login');
        $this->post('/jfragment/submit')->assertRedirect('/login');
    }

    public function test_authenticated_users_can_view_the_submission_form(): void
    {
        Creator::factory()->create([
            'slug' => 'jfragment',
            'display_name' => 'JFragment',
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/jfragment/submit')
            ->assertOk()
            ->assertSee('Submit a request for JFragment')
            ->assertSee('YouTube link')
            ->assertSee('Topic')
            ->assertSee('YouTube URL')
            ->assertSee('Suggest an idea or YouTube link for something this creator could make, cover, explore, or discover.')
            ->assertSee('Submitting this request will use 1 of your request slots for this creator. Voting is separate.')
            ->assertSee('Why should JFragment make, cover, or explore this?')
            ->assertSee('maxlength="1000"', false)
            ->assertSee('Optional, up to 1,000 characters.')
            ->assertSee('0 / 1000')
            ->assertSee('Submit request');
    }

    public function test_submission_form_uses_a_normal_post_and_visible_favorite_notice(): void
    {
        Creator::factory()->create([
            'slug' => 'jfragment',
            'display_name' => 'JFragment',
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/jfragment/submit')
            ->assertOk()
            ->assertSee('method="POST"', false)
            ->assertSee('action="'.route('recommendations.store', ['creator' => 'jfragment']).'"', false)
            ->assertSee('type="submit"', false)
            ->assertSee('form="recommendation-submit"', false)
            ->assertSee('name="confirm_favorite"', false)
            ->assertSee('value="1"', false)
            ->assertSee('Submitting to this journey will add JFragment to your favorites and use 1 creator favorite slot.')
            ->assertDontSee('request-participation-confirmation', false);
    }

    public function test_submission_button_still_submits_when_suggestion_limit_is_exhausted(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'jfragment',
            'display_name' => 'JFragment',
        ]);
        $user = User::factory()->create();

        CreatorFavorite::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);

        Recommendation::factory()
            ->count(3)
            ->create([
                'creator_id' => $creator->id,
                'submitted_by' => $user->id,
                'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            ]);

        $this->actingAs($user)
            ->get('/jfragment/submit')
            ->assertOk()
            ->assertSee('Request limit reached')
            ->assertSee('type="submit"', false)
            ->assertSee('form="recommendation-submit"', false)
            ->assertDontSee('disabled', false);
    }

    public function test_closed_submissions_show_a_friendly_message_and_hide_the_form(): void
    {
        Creator::factory()->create([
            'slug' => 'jfragment',
            'display_name' => 'JFragment',
            'submissions_open' => false,
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/jfragment/submit')
            ->assertOk()
            ->assertSee('This creator is not accepting new requests right now.')
            ->assertDontSee('Submit request');
    }

    public function test_closed_submissions_cannot_be_posted_to(): void
    {
        Creator::factory()->create([
            'slug' => 'jfragment',
            'submissions_open' => false,
        ]);

        $this->actingAs(User::factory()->create())
            ->post('/jfragment/submit', [
                'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'title' => 'Never Gonna Give You Up',
            ])
            ->assertSessionHasErrors([
                'submissions' => 'This creator is not accepting new requests right now.',
            ]);

        $this->assertDatabaseCount('recommendations', 0);
    }

    public function test_it_validates_submission_fields(): void
    {
        Creator::factory()->create(['slug' => 'jfragment']);

        $this->actingAs(User::factory()->create())
            ->post('/jfragment/submit', [
                'youtube_url' => 'not-a-url',
                'title' => '',
                'artist' => str_repeat('a', 256),
                'category' => 'gaming',
                'reason' => str_repeat('a', 1001),
            ])
            ->assertSessionHasErrors([
                'youtube_url',
                'title',
                'artist',
                'category',
                'reason',
            ]);
    }

    public function test_validation_errors_are_visible_on_the_submission_form(): void
    {
        Creator::factory()->create(['slug' => 'jfragment']);

        $response = $this->actingAs(User::factory()->create())
            ->from('/jfragment/submit')
            ->post('/jfragment/submit', [
                'youtube_url' => 'not-a-url',
                'title' => '',
            ]);

        $response->assertRedirect('/jfragment/submit');

        $this->get('/jfragment/submit')
            ->assertOk()
            ->assertSee('Please fix the highlighted fields and submit again.')
            ->assertSee('youtube url', false)
            ->assertSee('title', false);
    }

    public function test_manual_mode_holds_a_recommendation_for_review_and_hides_it_from_the_public_queue(): void
    {
        $creator = Creator::factory()->moderated()->create(['slug' => 'jfragment']);
        $user = User::factory()->create();
        $owner = User::factory()->create();
        CreatorOwner::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($user)->post('/jfragment/submit', [
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'title' => 'Never Gonna Give You Up',
            'artist' => 'Rick Astley',
            'category' => 'music',
            'reason' => 'A classic reaction choice.',
            'confirm_favorite' => '1',
        ]);

        $response->assertRedirect('/jfragment')
            ->assertSessionHas('success', 'Request submitted and waiting for creator review.');

        $this->assertDatabaseHas('recommendations', [
            'creator_id' => $creator->id,
            'submitted_by' => $user->id,
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'title' => 'Never Gonna Give You Up',
            'artist' => 'Rick Astley',
            'category' => 'music',
            'reason' => 'A classic reaction choice.',
            'status' => 'pending',
        ]);

        $this->actingAs($user)
            ->get('/jfragment')
            ->assertOk()
            ->assertSee('Request submitted and waiting for creator review.')
            ->assertDontSee('Never Gonna Give You Up');

        $this->actingAs($owner)
            ->get(route('creators.dashboard', $creator))
            ->assertOk()
            ->assertSeeInOrder(['Pending review', '1']);
    }

    public function test_auto_mode_approves_a_recommendation_and_shows_it_publicly(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'jfragment',
            'recommendation_approval_mode' => 'auto',
        ]);
        $user = User::factory()->create();
        $owner = User::factory()->create();
        CreatorOwner::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $owner->id,
        ]);

        $response = $this->actingAs($user)->post('/jfragment/submit', [
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'title' => 'Auto-approved recommendation',
            'confirm_favorite' => '1',
        ]);

        $response->assertRedirect('/jfragment')
            ->assertSessionHas('success', 'Request submitted and added to the journey.');

        $this->assertDatabaseHas('recommendations', [
            'creator_id' => $creator->id,
            'submitted_by' => $user->id,
            'title' => 'Auto-approved recommendation',
            'status' => 'approved',
        ]);

        $this->actingAs($user)
            ->get('/jfragment')
            ->assertOk()
            ->assertSee('Request submitted and added to the journey.')
            ->assertSee('Auto-approved recommendation');

        $this->actingAs($owner)
            ->get(route('creators.dashboard', $creator))
            ->assertOk()
            ->assertSeeInOrder(['Pending review', '0']);
    }

    public function test_youtube_submission_allows_empty_category_and_reason(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'jfragment',
            'recommendation_approval_mode' => 'auto',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/jfragment/submit', [
                'recommendation_type' => 'youtube',
                'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                'title' => 'No category needed',
                'channel_title' => 'Example Channel',
                'category' => '',
                'reason' => '',
                'confirm_favorite' => '1',
            ])
            ->assertRedirect('/jfragment')
            ->assertSessionDoesntHaveErrors(['category', 'reason']);

        $this->assertDatabaseHas('recommendations', [
            'creator_id' => $creator->id,
            'submitted_by' => $user->id,
            'title' => 'No category needed',
            'category' => null,
            'reason' => null,
            'status' => 'approved',
        ]);
    }

    public function test_it_extracts_a_youtu_be_video_id_when_possible(): void
    {
        Creator::factory()->create([
            'slug' => 'jfragment',
            'recommendation_approval_mode' => 'auto',
        ]);

        $this->actingAs(User::factory()->create())
            ->post('/jfragment/submit', [
                'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ?t=42',
                'title' => 'Short YouTube URL',
                'confirm_favorite' => '1',
            ])
            ->assertRedirect('/jfragment');

        $this->assertDatabaseHas('recommendations', [
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'title' => 'Short YouTube URL',
            'status' => 'approved',
        ]);
    }

    public function test_it_accepts_a_topic_without_a_youtube_url(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'jfragment',
            'recommendation_approval_mode' => 'auto',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/jfragment/submit', [
                'recommendation_type' => 'topic',
                'title' => 'How sampling changed modern music',
                'description' => 'Explore landmark samples and how copyright shaped the art form.',
                'category' => 'culture',
                'reason' => 'It opens the door to several follow-up reactions.',
                'confirm_favorite' => '1',
            ])
            ->assertRedirect('/jfragment');

        $this->assertDatabaseHas('recommendations', [
            'creator_id' => $creator->id,
            'submitted_by' => $user->id,
            'recommendation_type' => 'topic',
            'youtube_url' => null,
            'youtube_video_id' => null,
            'title' => 'How sampling changed modern music',
            'description' => 'Explore landmark samples and how copyright shaped the art form.',
            'status' => 'approved',
        ]);
    }

    public function test_topic_submissions_require_a_description(): void
    {
        Creator::factory()->create(['slug' => 'jfragment']);

        $this->actingAs(User::factory()->create())
            ->post('/jfragment/submit', [
                'recommendation_type' => 'topic',
                'title' => 'An incomplete topic',
            ])
            ->assertSessionHasErrors('description')
            ->assertSessionDoesntHaveErrors('youtube_url');
    }

    public function test_youtube_metadata_lookup_returns_the_video_title_and_channel(): void
    {
        Http::fake([
            '*youtube.com/oembed*' => Http::response([
                'title' => 'Never Gonna Give You Up',
                'author_name' => 'Rick Astley',
            ]),
        ]);

        $creator = Creator::factory()->create(['slug' => 'jfragment']);

        $this->actingAs(User::factory()->create())
            ->getJson(route('recommendations.youtube-metadata', [
                'creator' => $creator,
                'url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            ]))
            ->assertOk()
            ->assertJson([
                'video_id' => 'dQw4w9WgXcQ',
                'title' => 'Never Gonna Give You Up',
                'channel_title' => 'Rick Astley',
            ]);
    }

    public function test_youtube_metadata_lookup_accepts_youtube_url_alias_and_underscore_video_ids(): void
    {
        Http::fake([
            '*youtube.com/oembed*' => Http::response([
                'title' => 'Example Underscore Video',
                'author_name' => 'JFragment',
            ]),
        ]);

        $creator = Creator::factory()->create(['slug' => 'jfragment']);

        $this->actingAs(User::factory()->create())
            ->getJson(route('recommendations.youtube-metadata', [
                'creator' => $creator,
                'youtube_url' => 'https://www.youtube.com/watch?v=_BTsmCm8UPE',
            ]))
            ->assertOk()
            ->assertJson([
                'video_id' => '_BTsmCm8UPE',
                'title' => 'Example Underscore Video',
                'channel_title' => 'JFragment',
            ]);
    }

    public function test_youtube_metadata_lookup_gracefully_handles_oembed_failures_for_valid_urls(): void
    {
        Http::fake([
            '*youtube.com/oembed*' => Http::response([], 404),
        ]);

        $creator = Creator::factory()->create(['slug' => 'jfragment']);

        $this->actingAs(User::factory()->create())
            ->getJson(route('recommendations.youtube-metadata', [
                'creator' => $creator,
                'youtube_url' => 'https://youtu.be/_BTsmCm8UPE',
            ]))
            ->assertOk()
            ->assertJson([
                'video_id' => '_BTsmCm8UPE',
                'title' => '',
                'channel_title' => '',
                'metadata_unavailable' => true,
            ]);
    }

    public function test_submission_form_uses_debounced_metadata_lookup_without_refetching_unchanged_urls(): void
    {
        Creator::factory()->create([
            'slug' => 'jfragment',
            'display_name' => 'JFragment',
        ]);

        $this->actingAs(User::factory()->create())
            ->get('/jfragment/submit')
            ->assertOk()
            ->assertSee('scheduleYouTubeDetailsLookup', false)
            ->assertSee('lookupCompletedUrl', false)
            ->assertSee('lookupFailedUrl', false)
            ->assertSee("url.searchParams.set('youtube_url', requestedUrl);", false)
            ->assertDontSee("url.searchParams.set('url', this.youtubeUrl);", false);
    }

    public function test_it_extracts_video_ids_from_youtube_shorts_urls(): void
    {
        Creator::factory()->create(['slug' => 'jfragment']);

        $this->actingAs(User::factory()->create())
            ->post('/jfragment/submit', [
                'recommendation_type' => 'youtube',
                'youtube_url' => 'https://www.youtube.com/shorts/dQw4w9WgXcQ',
                'title' => 'A YouTube Short',
                'channel_title' => 'Example Channel',
                'confirm_favorite' => '1',
            ])
            ->assertRedirect('/jfragment');

        $this->assertDatabaseHas('recommendations', [
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'channel_title' => 'Example Channel',
        ]);
    }

    public function test_original_suggester_can_withdraw_active_recommendation_and_resources_return(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'jfragment',
            'recommendation_approval_mode' => 'auto',
        ]);
        $suggester = User::factory()->create();
        $voter = User::factory()->create();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $suggester->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'status' => 'approved',
            'title' => 'Withdrawable suggestion',
        ]);

        UserPick::query()->create([
            'creator_id' => $creator->id,
            'recommendation_id' => $recommendation->id,
            'user_id' => $voter->id,
            'vote_count' => 2,
        ]);

        $this->assertSame(1, $suggester->suggestionsUsedFor($creator));
        $this->assertSame(2, $voter->votesUsedFor($creator));

        $this->actingAs($suggester)
            ->post(route('recommendations.withdraw', [$creator, $recommendation]))
            ->assertRedirect(route('creator.queue', $creator))
            ->assertSessionHas('success', 'Your request was withdrawn.');

        $recommendation->refresh();

        $this->assertSame('withdrawn', $recommendation->status);
        $this->assertNotNull($recommendation->withdrawn_at);
        $this->assertSame($suggester->id, $recommendation->withdrawn_by_user_id);
        $this->assertSame(0, $suggester->fresh()->suggestionsUsedFor($creator));
        $this->assertSame(0, $voter->fresh()->votesUsedFor($creator));
        $this->assertSame(1, $recommendation->userPicks()->count());

        $this->actingAs($suggester)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertDontSee('Withdrawable suggestion');
    }

    public function test_non_suggester_guest_and_published_recommendations_cannot_be_withdrawn(): void
    {
        $creator = Creator::factory()->create();
        $suggester = User::factory()->create();
        $otherUser = User::factory()->create();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $suggester->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'status' => 'approved',
        ]);

        $this->post(route('recommendations.withdraw', [$creator, $recommendation]))
            ->assertRedirect('/login');

        $this->actingAs($otherUser)
            ->post(route('recommendations.withdraw', [$creator, $recommendation]))
            ->assertSessionHasErrors([
                'withdraw' => 'This request can no longer be withdrawn.',
            ]);

        $recommendation->update(['status' => 'published']);

        $this->actingAs($suggester)
            ->post(route('recommendations.withdraw', [$creator, $recommendation]))
            ->assertSessionHasErrors([
                'withdraw' => 'This request can no longer be withdrawn.',
            ]);

        $this->assertSame('published', $recommendation->fresh()->status);
    }

    public function test_withdraw_action_only_appears_for_original_suggester_on_active_recommendations(): void
    {
        $creator = Creator::factory()->create(['recommendation_approval_mode' => 'auto']);
        $suggester = User::factory()->create();
        $otherUser = User::factory()->create();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $suggester->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'status' => 'approved',
            'title' => 'Visible suggestion',
        ]);

        $this->actingAs($suggester)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Withdraw request')
            ->assertSee('Withdraw this request?')
            ->assertSee(route('recommendations.withdraw', [$creator, $recommendation]), false);

        $this->actingAs($otherUser)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertDontSee('Withdraw request');

        $recommendation->update(['status' => 'published']);

        $this->actingAs($suggester)
            ->get(route('creators.published', $creator))
            ->assertOk()
            ->assertDontSee('Withdraw request');
    }

    public function test_duplicate_active_youtube_url_submission_is_blocked_with_helpful_link(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'jfragment',
            'recommendation_approval_mode' => 'auto',
        ]);
        $existing = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'normalized_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'youtube_video_id' => 'dQw4w9WgXcQ',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('recommendations.store', $creator), [
                'recommendation_type' => 'youtube',
                'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ?t=42&si=tracking',
                'title' => 'Duplicate active URL',
                'confirm_favorite' => '1',
            ])
            ->assertRedirect(route('recommendations.create', $creator))
            ->assertSessionHas('duplicate_recommendation.title', 'Already suggested')
            ->assertSessionHas('duplicate_recommendation.primary_url', route('creator.queue', $creator)."#recommendation-{$existing->id}");

        $this->get(route('recommendations.create', $creator))
            ->assertOk()
            ->assertSee('Already suggested')
            ->assertSee('This URL is already in the active request list for this creator.')
            ->assertSee('View request');

        $this->assertDatabaseMissing('recommendations', [
            'title' => 'Duplicate active URL',
        ]);
    }

    public function test_duplicate_published_url_submission_checks_published_video_fields(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'jfragment',
            'recommendation_approval_mode' => 'auto',
        ]);
        $existing = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'published',
            'published_reaction_url' => 'https://www.youtube.com/watch?v=_BTsmCm8UPE',
            'published_normalized_url' => 'https://www.youtube.com/watch?v=_BTsmCm8UPE',
            'published_video_id' => '_BTsmCm8UPE',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('recommendations.store', $creator), [
                'recommendation_type' => 'youtube',
                'youtube_url' => 'https://www.youtube.com/shorts/_BTsmCm8UPE?feature=share',
                'title' => 'Duplicate published URL',
                'confirm_favorite' => '1',
            ])
            ->assertRedirect(route('recommendations.create', $creator))
            ->assertSessionHas('duplicate_recommendation.title', 'Already published')
            ->assertSessionHas('duplicate_recommendation.primary_url', route('creators.published', $creator)."#recommendation-{$existing->id}")
            ->assertSessionHas('duplicate_recommendation.secondary_url', 'https://www.youtube.com/watch?v=_BTsmCm8UPE');

        $this->get(route('recommendations.create', $creator))
            ->assertOk()
            ->assertSee('Already published')
            ->assertSee('This creator has already published something for this request.')
            ->assertSee('View published request')
            ->assertSee('Open published video');

        $this->assertDatabaseMissing('recommendations', [
            'title' => 'Duplicate published URL',
        ]);
    }

    public function test_withdrawn_duplicate_can_be_submitted_again_when_not_published(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'jfragment',
            'recommendation_approval_mode' => 'auto',
        ]);

        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'withdrawn',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'normalized_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'youtube_video_id' => 'dQw4w9WgXcQ',
        ]);

        $this->actingAs(User::factory()->create())
            ->post(route('recommendations.store', $creator), [
                'recommendation_type' => 'youtube',
                'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
                'title' => 'Resubmitted after withdrawal',
                'confirm_favorite' => '1',
            ])
            ->assertRedirect(route('creator.queue', $creator));

        $this->assertDatabaseHas('recommendations', [
            'title' => 'Resubmitted after withdrawal',
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'normalized_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'status' => 'approved',
        ]);
    }

    public function test_playlist_metadata_lookup_and_submission_store_first_class_playlist_fields(): void
    {
        config(['services.youtube.api_key' => 'test-key']);
        Http::fake([
            '*youtube/v3/playlists*' => Http::response(['items' => [[
                'snippet' => [
                    'title' => 'Essential Performances',
                    'channelTitle' => 'Music Guide',
                    'thumbnails' => ['high' => ['url' => 'https://i.ytimg.com/vi/example/hqdefault.jpg']],
                ],
                'contentDetails' => ['itemCount' => 18],
            ]]]),
        ]);

        $creator = Creator::factory()->create(['slug' => 'playlist-guide']);
        $user = User::factory()->create();
        $url = 'https://m.youtube.com/playlist?list=PL1234567890&si=tracking';

        $this->actingAs($user)
            ->getJson(route('recommendations.youtube-metadata', [$creator, 'youtube_url' => $url]))
            ->assertOk()
            ->assertJsonPath('media_type', 'playlist')
            ->assertJsonPath('item_count', 18)
            ->assertJsonPath('canonical_url', 'https://www.youtube.com/playlist?list=PL1234567890');

        $this->actingAs($user)->post(route('recommendations.store', $creator), [
            'recommendation_type' => 'youtube',
            'youtube_url' => $url,
            'title' => 'Manual fallback title',
            'confirm_favorite' => '1',
        ])->assertRedirect(route('creator.queue', $creator));

        $this->assertDatabaseHas('recommendations', [
            'media_type' => 'playlist',
            'youtube_playlist_id' => 'PL1234567890',
            'youtube_video_id' => null,
            'normalized_url' => 'https://www.youtube.com/playlist?list=PL1234567890',
            'title' => 'Essential Performances',
            'source_channel' => 'Music Guide',
            'source_item_count' => 18,
        ]);
    }

    public function test_same_playlist_variant_is_blocked_but_a_video_inside_it_is_not(): void
    {
        $creator = Creator::factory()->create(['slug' => 'playlist-duplicates']);
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'media_type' => 'playlist',
            'youtube_url' => 'https://www.youtube.com/playlist?list=PL1234567890',
            'normalized_url' => 'https://www.youtube.com/playlist?list=PL1234567890',
            'youtube_video_id' => null,
            'youtube_playlist_id' => 'PL1234567890',
            'status' => 'approved',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)->post(route('recommendations.store', $creator), [
            'recommendation_type' => 'youtube',
            'youtube_url' => 'https://m.youtube.com/playlist?list=PL1234567890&index=2',
            'title' => 'Duplicate list',
        ])->assertSessionHas('duplicate_recommendation.body', 'This playlist has already been suggested.');

        $this->actingAs($user)->post(route('recommendations.store', $creator), [
            'recommendation_type' => 'youtube',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ&list=PL1234567890',
            'title' => 'A video within the list',
            'confirm_favorite' => '1',
        ])->assertRedirect(route('creator.queue', $creator));

        $this->assertDatabaseHas('recommendations', [
            'media_type' => 'video',
            'youtube_video_id' => 'dQw4w9WgXcQ',
        ]);
    }
}
