<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorOwner;
use App\Models\User;
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
            ->assertSee('Make a recommendation for JFragment')
            ->assertSee('YouTube link')
            ->assertSee('Topic')
            ->assertSee('YouTube URL')
            ->assertSee('Suggest an idea or YouTube link for something this creator could make, cover, explore, or discover.')
            ->assertSee('Why should JFragment make, cover, or explore this?')
            ->assertSee('Submit recommendation');
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
            ->assertSee('This creator is not accepting new recommendations right now.')
            ->assertDontSee('Submit recommendation');
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
                'submissions' => 'This creator is not accepting new recommendations right now.',
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

    public function test_manual_mode_holds_a_recommendation_for_review_and_hides_it_from_the_public_queue(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
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
            ->assertSessionHas('success', 'Recommendation submitted and waiting for creator review.');

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
            ->assertSee('Recommendation submitted and waiting for creator review.')
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
            ->assertSessionHas('success', 'Recommendation submitted and added to the journey.');

        $this->assertDatabaseHas('recommendations', [
            'creator_id' => $creator->id,
            'submitted_by' => $user->id,
            'title' => 'Auto-approved recommendation',
            'status' => 'approved',
        ]);

        $this->actingAs($user)
            ->get('/jfragment')
            ->assertOk()
            ->assertSee('Recommendation submitted and added to the journey.')
            ->assertSee('Auto-approved recommendation');

        $this->actingAs($owner)
            ->get(route('creators.dashboard', $creator))
            ->assertOk()
            ->assertSeeInOrder(['Pending review', '0']);
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
}
