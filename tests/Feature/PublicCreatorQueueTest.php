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
use Illuminate\Support\MessageBag;
use Illuminate\Support\Str;
use Illuminate\Support\ViewErrorBag;
use Tests\TestCase;

class PublicCreatorQueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_page_uses_journey_wording_and_shows_creator_guidance(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'jfragment',
            'display_name' => 'JFragment',
            'bio' => 'Exploring music, culture, and first-listen discoveries.',
            'submission_instructions' => "Tell me why this recommendation matters to you.\n<script>alert('nope')</script>",
            'youtube_channel_url' => 'https://www.youtube.com/@jfragment',
        ]);

        $this->actingAs(User::factory()->create())
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('<title>JFragment | Guide My Journey</title>', false)
            ->assertSee('>JFragment</h1>', false)
            ->assertDontSee("JFragment's Journey", false)
            ->assertDontSee('Suggest ideas, vote with the community, and help guide what this creator makes next.')
            ->assertSee('Exploring music, culture, and first-listen discoveries.')
            ->assertSee('aria-label="Open creator actions"', false)
            ->assertSee('Biography')
            ->assertSee('Description')
            ->assertSee('More info')
            ->assertSee('Submission guidance')
            ->assertSee('Tell me why this recommendation matters to you.')
            ->assertSee('A note from JFragment')
            ->assertSee('aria-label="JFragment avatar"', false)
            ->assertSee('&lt;script&gt;alert(&#039;nope&#039;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert', false)
            ->assertSeeInOrder(['Biography', 'Submission guidance'])
            ->assertDontSee('<details', false)
            ->assertSee('Add Recommendation')
            ->assertSee('aria-label="Add a recommendation for JFragment"', false)
            ->assertSee('Visit Channel')
            ->assertSee('aria-label="Visit JFragment\'s YouTube channel"', false)
            ->assertDontSee('Submit a recommendation')
            ->assertDontSee('Visit YouTube channel')
            ->assertSeeInOrder([
                'Your limits',
                'Filter suggestions',
            ])
            ->assertSee('aria-expanded="false"', false)
            ->assertSee('aria-controls="creator-queue-filters"', false)
            ->assertDontSee('Search and filter suggestions')
            ->assertSee('x-cloak', false)
            ->assertDontSee('data-active-filter-count=', false)
            ->assertDontSee('Queue controls');
    }

    public function test_creator_menu_panels_show_empty_states_for_missing_bio_and_guidance(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'jfragment',
            'bio' => null,
            'submission_instructions' => null,
        ]);

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Biography')
            ->assertSee('Submission guidance')
            ->assertSee('No biography has been added for this creator yet.')
            ->assertSee('This creator has not added submission guidance yet.')
            ->assertDontSee('<details', false);
    }

    public function test_creator_biography_auto_links_safe_urls_without_trusting_html(): void
    {
        $longUrl = 'https://example.com/'.str_repeat('very-long-path-segment-', 12).'?ref=creator';
        $creator = Creator::factory()->create([
            'slug' => 'jfragment',
            'bio' => "Check out my old band: https://www.youtube.com/watch?v=abc123\n"
                .'More history at http://example.com/archive and https://example.org/social.'
                ."\nLong link: {$longUrl}\n"
                .'<script>alert(1)</script>'
                ."\nUnsafe scheme: javascript:alert(1)",
        ]);

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Check out my old band:')
            ->assertSee('href="https://www.youtube.com/watch?v=abc123"', false)
            ->assertSee('href="http://example.com/archive"', false)
            ->assertSee('href="https://example.org/social"', false)
            ->assertSee('href="'.$longUrl.'"', false)
            ->assertSee('target="_blank"', false)
            ->assertSee('rel="noopener noreferrer nofollow ugc"', false)
            ->assertSee('hover:underline', false)
            ->assertSee('[overflow-wrap:anywhere]', false)
            ->assertSee("\nLong link:", false)
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert', false)
            ->assertSee('javascript:alert(1)')
            ->assertDontSee('href="javascript:alert(1)"', false);
    }

    public function test_it_only_displays_public_queue_statuses_and_recommendation_details(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'jfragment',
            'display_name' => 'JFragment',
        ]);

        foreach (['approved', 'coming_soon', 'scheduled', 'recorded', 'passed'] as $status) {
            Recommendation::factory()->create([
                'creator_id' => $creator->id,
                'title' => ucfirst($status).' recommendation',
                'artist' => 'Example Artist',
                'category' => 'Music',
                'status' => $status,
                'youtube_url' => "https://www.youtube.com/watch?v={$status}",
            ]);
        }

        $published = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Published recommendation',
            'artist' => 'Example Artist',
            'category' => 'Music',
            'status' => 'published',
            'youtube_url' => 'https://www.youtube.com/watch?v=published',
        ]);

        foreach (['pending', 'hidden', 'planned', 'declined'] as $status) {
            Recommendation::factory()->create([
                'creator_id' => $creator->id,
                'title' => ucfirst($status).' recommendation',
                'status' => $status,
            ]);
        }

        $response = $this->get('/jfragment');

        $response->assertOk()
            ->assertSee('JFragment')
            ->assertSee('Example Artist')
            ->assertSee('Music')
            ->assertDontSee('Watch original')
            ->assertSee('rel="noopener noreferrer nofollow ugc"', false)
            ->assertSee('Submitted');

        foreach (['approved', 'coming_soon', 'scheduled', 'recorded', 'passed'] as $status) {
            $response->assertSee(ucfirst($status).' recommendation');
        }

        $response
            ->assertSee('Recently Published')
            ->assertSee('Published recommendation')
            ->assertDontSee('id="recommendation-'.$published->id.'"', false);

        foreach (['pending', 'hidden', 'planned', 'declined'] as $status) {
            $response->assertDontSee(ucfirst($status).' recommendation');
        }
    }

    public function test_it_orders_pinned_first_then_votes_then_newest(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);

        $pinned = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Pinned recommendation',
            'status' => 'approved',
            'is_pinned' => true,
            'created_at' => now()->subWeek(),
        ]);
        $mostVotes = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Most voted recommendation',
            'status' => 'coming_soon',
            'created_at' => now()->subDays(2),
        ]);
        $newest = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Newest recommendation',
            'status' => 'recorded',
            'created_at' => now(),
        ]);
        $oldest = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Oldest recommendation',
            'status' => 'passed',
            'created_at' => now()->subDay(),
        ]);

        $this->addPicks($creator, $pinned, 1);
        $this->addPicks($creator, $mostVotes, 3);
        $this->addPicks($creator, $newest, 2);
        $this->addPicks($creator, $oldest, 2);

        $this->get('/jfragment')
            ->assertOk()
            ->assertSeeInOrder([
                'Pinned recommendation',
                'Most voted recommendation',
                'Newest recommendation',
                'Oldest recommendation',
            ]);
    }

    public function test_recommendations_render_as_ranked_expandable_leaderboard_rows(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $first = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'First ranked request',
            'status' => 'approved',
        ]);
        $second = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Second ranked request',
            'status' => 'approved',
        ]);

        $this->addPicks($creator, $first, 2);
        $this->addPicks($creator, $second, 1);

        $response = $this
            ->withSession([
                'recommendation_action' => [
                    'recommendation_id' => $second->id,
                    'message' => 'Your upvote was added.',
                    'type' => 'added',
                ],
            ])
            ->get(route('creator.queue', $creator));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                '1st',
                'First ranked request',
                '2',
                'upvotes',
                '2nd',
                'Second ranked request',
                '1',
                'upvote',
            ])
            ->assertSee('id="recommendation-'.$first->id.'"', false)
            ->assertSee('id="recommendation-details-'.$first->id.'"', false)
            ->assertSee('aria-controls="recommendation-details-'.$first->id.'"', false)
            ->assertSee('x-bind:aria-expanded="open.toString()"', false)
            ->assertSee('x-show="open"', false)
            ->assertSee('x-data="{ open: false }"', false)
            ->assertSee('x-data="{ open: true }"', false)
            ->assertSee('data-recommendation-action-feedback', false)
            ->assertSee('Your upvote was added.');

        $this->assertSame(1, substr_count($response->getContent(), 'id="recommendation-'.$first->id.'"'));
        $this->assertSame(1, substr_count($response->getContent(), 'id="recommendation-'.$second->id.'"'));
    }

    public function test_recommendation_rows_show_requester_and_upvoter_avatar_stacks(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $requester = User::factory()->create([
            'name' => 'Original Fan',
            'email' => 'original@example.test',
            'avatar_url' => null,
        ]);
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $requester->id,
            'title' => 'Avatar-backed request',
            'status' => 'approved',
        ]);

        UserPick::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_id' => $recommendation->id,
            'user_id' => $requester->id,
        ]);

        User::factory()
            ->count(23)
            ->sequence(fn ($sequence) => [
                'name' => 'Voter '.str_pad((string) ($sequence->index + 1), 2, '0', STR_PAD_LEFT),
                'avatar_url' => 'https://example.test/avatar-'.$sequence->index.'.jpg',
            ])
            ->create()
            ->each(fn (User $user) => UserPick::factory()->create([
                'creator_id' => $creator->id,
                'recommendation_id' => $recommendation->id,
                'user_id' => $user->id,
            ]));

        $response = $this->get(route('creator.queue', $creator));

        $response
            ->assertOk()
            ->assertSee('Avatar-backed request')
            ->assertSee('title="Requested by Original Fan"', false)
            ->assertSee('aria-label="Requested by Original Fan"', false)
            ->assertSee('src="https://example.test/avatar-0.jpg"', false)
            ->assertSee('title="Upvoted by Voter 01"', false)
            ->assertSee('Community support')
            ->assertSee('title="14 more upvoters"', false)
            ->assertSee('title="19 more upvoters"', false)
            ->assertSee('title="3 more upvoters"', false)
            ->assertDontSee('this.nextElementSibling.hidden', false)
            ->assertDontSee('original@example.test');
    }

    public function test_public_queue_can_search_filter_and_sort_recommendations(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $music = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Quiet folk ballad',
            'artist' => 'Needle North',
            'category' => 'music',
            'status' => 'approved',
            'youtube_url' => 'https://www.youtube.com/watch?v=FOLK0000001',
            'created_at' => now()->subDays(3),
        ]);
        $documentary = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Ocean archive documentary',
            'channel_title' => 'Retro Archive',
            'category' => 'documentary',
            'status' => 'scheduled',
            'youtube_url' => 'https://www.youtube.com/watch?v=OCEAN000001',
            'scheduled_for' => now()->addWeek(),
            'created_at' => now(),
        ]);
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Hidden Retro Archive request',
            'category' => 'documentary',
            'status' => 'hidden',
        ]);

        $this->addPicks($creator, $music, 3);
        $this->addPicks($creator, $documentary, 1);

        foreach ([
            'Ocean archive' => 'Ocean archive documentary',
            'Needle North' => 'Quiet folk ballad',
            'Retro Archive' => 'Ocean archive documentary',
            'OCEAN000001' => 'Ocean archive documentary',
        ] as $search => $expectedTitle) {
            $this->get(route('creator.queue', ['creator' => $creator, 'q' => $search]))
                ->assertOk()
                ->assertSee($expectedTitle)
                ->assertDontSee('Hidden Retro Archive request');
        }

        $this->get(route('creator.queue', [
            'creator' => $creator,
            'status' => 'scheduled',
            'category' => 'documentary',
            'sort' => 'scheduled',
        ]))
            ->assertOk()
            ->assertSee('Ocean archive documentary')
            ->assertDontSee('Quiet folk ballad')
            ->assertSee('value="scheduled" selected', false)
            ->assertSee('value="documentary" selected', false)
            ->assertSee('value="scheduled" selected', false)
            ->assertSee('Most upvotes')
            ->assertSee('Newest')
            ->assertSee('Status')
            ->assertSee('Scheduled date')
            ->assertSee('Filter suggestions')
            ->assertDontSee('Search and filter suggestions')
            ->assertSee('Hide filters')
            ->assertSee('aria-controls="creator-queue-filters"', false)
            ->assertSee('x-bind:aria-expanded="open.toString()"', false)
            ->assertSee('x-show="open"', false)
            ->assertSee('data-active-filter-count="2"', false)
            ->assertDontSee('Queue controls');

        $this->get(route('creator.queue', ['creator' => $creator, 'sort' => 'newest']))
            ->assertOk()
            ->assertSeeInOrder([
                'Ocean archive documentary',
                'Quiet folk ballad',
            ]);
    }

    public function test_public_queue_shows_distinct_empty_states(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'jfragment',
            'submissions_open' => true,
        ]);

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('No recommendations yet. Be the first to suggest something for this journey.')
            ->assertSee('Submit recommendation');

        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Visible request',
            'status' => 'approved',
        ]);

        $this->get(route('creator.queue', ['creator' => $creator, 'q' => 'missing']))
            ->assertOk()
            ->assertSee('No recommendations found.')
            ->assertDontSee('No recommendations yet. Be the first to suggest something for this journey.');
    }

    public function test_it_displays_topic_descriptions_without_a_youtube_link(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);

        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_type' => 'topic',
            'youtube_url' => null,
            'youtube_video_id' => null,
            'channel_title' => null,
            'title' => 'The history of the Amen break',
            'description' => 'Trace one drum break across decades of music.',
            'status' => 'approved',
        ]);

        $this->get('/jfragment')
            ->assertOk()
            ->assertSee('Topic')
            ->assertSee('The history of the Amen break')
            ->assertSee('Trace one drum break across decades of music.')
            ->assertSee('Topic suggestion')
            ->assertDontSee('Watch on YouTube');
    }

    public function test_it_displays_human_readable_public_status_labels(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);

        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'New status recommendation',
            'status' => 'coming_soon',
        ]);
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Passed status recommendation',
            'status' => 'passed',
        ]);
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Already seen recommendation',
            'status' => 'already_seen',
        ]);

        $this->get('/jfragment')
            ->assertOk()
            ->assertSee('New status recommendation')
            ->assertSee('Passed status recommendation')
            ->assertSee('Already seen recommendation')
            ->assertSee('Coming Soon', false)
            ->assertSee('Already Seen', false)
            ->assertSee('The creator has already seen this.')
            ->assertSee('Passed', false);

        $this->get('/jfragment?status=already_seen')
            ->assertOk()
            ->assertSee('Already seen recommendation')
            ->assertDontSee('New status recommendation')
            ->assertDontSee('Passed status recommendation');
    }

    public function test_scheduled_recommendations_show_public_timing(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);

        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Scheduled recommendation',
            'status' => 'scheduled',
            'scheduled_for' => '2026-07-04 19:30:00',
        ]);

        $this->get('/jfragment')
            ->assertOk()
            ->assertSee('Scheduled for Jul 4, 2026 at 7:30 PM');
    }

    public function test_creator_page_shows_recently_published_sidebar_and_excludes_published_from_active_queue(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $active = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Active community request',
            'status' => 'approved',
        ]);
        $published = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Published community request',
            'status' => 'published',
            'category' => 'music',
            'published_at' => '2026-07-04 12:00:00',
            'published_reaction_url' => 'https://www.youtube.com/watch?v=PUBLISHED01',
            'published_title' => 'Creator finished video',
            'published_channel' => 'Creator Finished Channel',
        ]);

        $this->addPicks($creator, $published, 2);

        $response = $this->get(route('creator.queue', $creator));

        $response
            ->assertOk()
            ->assertSee('Recently Published')
            ->assertSee('View all published')
            ->assertSee('Published Jul 4, 2026')
            ->assertSee('Creator finished video')
            ->assertSee('Creator Finished Channel')
            ->assertSee(route('creators.published', $creator).'#recommendation-'.$published->id, false)
            ->assertSee('2 votes');

        $this->assertStringContainsString('Active community request', $response->getContent());
        $this->assertStringNotContainsString('Published community request', $response->getContent());
        $this->assertStringNotContainsString('id="recommendation-'.$published->id.'"', $response->getContent());
        $this->assertStringContainsString('id="recommendation-'.$active->id.'"', $response->getContent());
    }

    public function test_creator_page_shows_recently_published_empty_state(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Recently Published')
            ->assertSee('No published recommendations yet.');
    }

    public function test_exhausted_upvote_alert_explains_when_upvotes_return(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $user = User::factory()->create();

        $errors = (new ViewErrorBag)->put(
            'default',
            new MessageBag([
                'limit' => ['You’ve used all your upvotes for this creator.'],
            ]),
        );

        $this->actingAs($user)
            ->withSession(['errors' => $errors])
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('You’ve used all your upvotes for this creator.')
            ->assertSee('You’ll get upvotes back when recommendations you supported are published or closed.');
    }

    public function test_published_page_lists_searches_and_selects_published_recommendations(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $tag = CreatorTag::query()->create([
            'creator_id' => $creator->id,
            'name' => 'Deep Dive',
            'slug' => 'deep-dive',
        ]);
        $newer = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Newer published request',
            'status' => 'published',
            'channel_title' => 'Published Channel',
            'description' => 'A finished community outcome.',
            'category' => 'documentary',
            'youtube_url' => 'https://www.youtube.com/watch?v=SOURCE00001',
            'youtube_video_id' => 'SOURCE00001',
            'reason' => 'Published context should stay plain: https://example.com <script>alert(1)</script>',
            'published_at' => '2026-07-04 12:00:00',
            'published_reaction_url' => 'https://www.youtube.com/watch?v=REACTION001',
            'published_title' => 'Creator reaction release',
            'published_channel' => 'Creator Channel',
            'published_thumbnail_url' => 'https://img.youtube.com/vi/REACTION001/hqdefault.jpg',
        ]);
        $older = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Older published request',
            'status' => 'published',
            'published_at' => '2026-07-03 12:00:00',
        ]);
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Active request',
            'status' => 'approved',
        ]);
        $newer->creatorTags()->attach($tag);

        $response = $this->get(route('creators.published', $creator));

        $response
            ->assertOk()
            ->assertSee('Published Recommendations')
            ->assertSee('Ideas this creator has already made, covered, explored, or published.')
            ->assertSeeInOrder([
                'Creator reaction release',
                'Older published request',
            ])
            ->assertDontSee('Active request')
            ->assertSee('grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3', false)
            ->assertSee('Selected published recommendation')
            ->assertSee("selectRecommendation({$newer->id})", false)
            ->assertSee("selectedId === {$newer->id}", false)
            ->assertSee('href="'.route('creators.published', $creator).'#recommendation-'.$newer->id.'"', false)
            ->assertSee('Watch published content')
            ->assertSee('https://www.youtube.com/watch?v=REACTION001', false)
            ->assertSee('https://img.youtube.com/vi/REACTION001/hqdefault.jpg', false)
            ->assertSee('Creator Channel')
            ->assertSee('Original suggestion')
            ->assertSee('Why this was suggested')
            ->assertSee('Published context should stay plain: https://example.com &lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('href="https://example.com"', false)
            ->assertSee('Newer published request')
            ->assertSee('https://www.youtube.com/watch?v=SOURCE00001', false)
            ->assertDontSee('Upvote')
            ->assertDontSee('No longer accepting upvotes');

        $this->get(route('creators.published', ['creator' => $creator, 'q' => 'Creator reaction']))
            ->assertOk()
            ->assertSee('Creator reaction release')
            ->assertDontSee('Older published request')
            ->assertSee('value="Creator reaction"', false);

        $this->get(route('creators.published', ['creator' => $creator, 'q' => 'Deep Dive']))
            ->assertOk()
            ->assertSee('Creator reaction release')
            ->assertDontSee('Older published request')
            ->assertSee('value="Deep Dive"', false);

        $this->get(route('creators.published', ['creator' => $creator, 'q' => 'missing']))
            ->assertOk()
            ->assertSee('No published recommendations found.');
    }

    public function test_it_displays_a_youtube_thumbnail_when_a_video_id_is_available(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);

        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'title' => 'Never Gonna Give You Up',
            'status' => 'approved',
        ]);

        $this->get('/jfragment')
            ->assertOk()
            ->assertSee('https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg', false)
            ->assertSee('onerror="this.hidden = true"', false)
            ->assertSee('Thumbnail for Never Gonna Give You Up');
    }

    public function test_invalid_or_missing_youtube_video_ids_use_the_placeholder(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);

        foreach ([
            ['title' => 'Missing ID', 'youtube_video_id' => null],
            ['title' => 'Malformed ID', 'youtube_video_id' => 'not-a-video-id'],
        ] as $recommendation) {
            Recommendation::factory()->create([
                'creator_id' => $creator->id,
                'youtube_url' => null,
                'status' => 'approved',
                ...$recommendation,
            ]);
        }

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Video preview unavailable')
            ->assertDontSee('/vi//hqdefault.jpg', false)
            ->assertDontSee('/vi/not-a-video-id/hqdefault.jpg', false);
    }

    public function test_cards_show_placeholder_reason_submitter_and_pick_copy(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $submitter = User::factory()->create(['name' => 'Example Fan']);
        $reason = str_repeat('A meaningful reason for this recommendation. ', 8);
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $submitter->id,
            'recommendation_type' => 'topic',
            'youtube_url' => null,
            'youtube_video_id' => null,
            'title' => 'A thoughtful community topic',
            'reason' => $reason,
            'status' => 'approved',
        ]);
        $viewer = User::factory()->create();

        $this->actingAs($viewer)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Community topic')
            ->assertSee('Submitted by Example Fan')
            ->assertSee('aria-label="Upvote this recommendation"', false)
            ->assertSee('mt-5 flex items-center justify-end', false)
            ->assertSee('inline-flex items-center gap-3 rounded-2xl', false)
            ->assertSeeInOrder([
                'aria-label="Upvote this recommendation"',
                '>0</p>',
                '>upvotes</p>',
            ], false)
            ->assertSee('Top requested')
            ->assertSee('Why this was suggested')
            ->assertSee(Str::limit($reason, 250))
            ->assertSee('Read more')
            ->assertSee('Show less')
            ->assertSee('x-bind:aria-expanded="expanded.toString()"', false);

        $this->post(route('recommendations.vote', [$creator, $recommendation]), [
            'confirm_favorite' => true,
            'vote_action' => 'add',
        ])
            ->assertRedirect();

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertDontSee('Remove upvote')
            ->assertSee('aria-label="Remove your upvote"', false)
            ->assertSee('name="vote_action" value="remove"', false)
            ->assertSee('1')
            ->assertSee('upvote');
    }

    public function test_recommendation_reason_renders_as_plain_text_without_links_or_html(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);

        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'A safely rendered recommendation',
            'reason' => '<script>alert(1)</script> Visit https://example.com for context.',
            'status' => 'approved',
        ]);

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Why this was suggested')
            ->assertSee('&lt;script&gt;alert(1)&lt;/script&gt; Visit https://example.com for context.', false)
            ->assertDontSee('<script>alert(1)</script>', false)
            ->assertDontSee('href="https://example.com"', false);
    }

    public function test_only_the_highest_voted_public_recommendation_is_top_requested(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $top = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Highest voted request',
            'status' => 'approved',
        ]);
        $other = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Lower voted request',
            'status' => 'coming_soon',
        ]);

        $this->addPicks($creator, $top, 3);
        $this->addPicks($creator, $other, 1);

        $response = $this->get(route('creator.queue', $creator));

        $response
            ->assertOk()
            ->assertSeeInOrder([
                'Top requested',
                'Highest voted request',
                'Lower voted request',
            ]);

        $this->assertSame(1, substr_count($response->getContent(), 'Top requested'));
    }

    public function test_inactive_creator_pages_return_not_found(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'inactive-creator',
            'status' => 'inactive',
            'deactivated_at' => now(),
        ]);

        $this->get(route('creator.queue', $creator))
            ->assertNotFound();
    }

    public function test_queue_remains_public_when_submissions_are_closed(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'jfragment',
            'submissions_open' => false,
        ]);

        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Visible recommendation',
            'status' => 'approved',
        ]);

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Visible recommendation');

        $this->actingAs(User::factory()->create())
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Recommendations closed');
    }

    public function test_authenticated_users_can_toggle_a_creator_favorite(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertDontSee('0 users have favorited this creator.')
            ->assertSee('0 followers')
            ->assertSee('Favorite');

        $this->post(route('creator.favorite', $creator))
            ->assertRedirect()
            ->assertSessionHas('success', 'Creator added to your favorites.');

        $this->assertDatabaseHas('creator_favorites', [
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertDontSee('1 user has favorited this creator.')
            ->assertSee('1 follower')
            ->assertSee('Favorited');

        $this->post(route('creator.favorite', $creator))
            ->assertRedirect()
            ->assertSessionHas('success', 'Creator removed from your favorites.');

        $this->assertDatabaseMissing('creator_favorites', [
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_unfavoriting_removes_only_that_creators_upvotes(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $otherCreator = Creator::factory()->create();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);
        $otherRecommendation = Recommendation::factory()->create([
            'creator_id' => $otherCreator->id,
            'status' => 'approved',
        ]);
        $user = User::factory()->create();

        foreach ([$creator, $otherCreator] as $favoritedCreator) {
            CreatorFavorite::query()->create([
                'creator_id' => $favoritedCreator->id,
                'user_id' => $user->id,
            ]);
        }

        foreach ([
            [$creator, $recommendation],
            [$otherCreator, $otherRecommendation],
        ] as [$pickedCreator, $pickedRecommendation]) {
            UserPick::factory()->create([
                'creator_id' => $pickedCreator->id,
                'recommendation_id' => $pickedRecommendation->id,
                'user_id' => $user->id,
            ]);
        }

        $this->actingAs($user)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Remove favorite?')
            ->assertSee('Unfavoriting removes your upvotes from this creator. Suggestions with no other votes may be removed.')
            ->assertSee('Remove favorite and upvotes')
            ->assertSee('Active upvotes on this creator: 1')
            ->assertSee('request-participation-confirmation', false)
            ->assertSee('data-modal-root="participation-confirmation"', false)
            ->assertSee('pointer-events-none invisible', false)
            ->assertSee('data-modal-backdrop="participation-confirmation"', false)
            ->assertSee('x-bind:hidden="! show"', false)
            ->assertSee('hidden', false)
            ->assertDontSee('confirm(', false);

        $this->post(route('creator.favorite', $creator))
            ->assertSessionHas(
                'success',
                'Creator removed from your favorites. Your upvotes for this creator were removed.',
            );

        $this->assertDatabaseMissing('creator_favorites', [
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('user_picks', [
            'recommendation_id' => $recommendation->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('creator_favorites', [
            'creator_id' => $otherCreator->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('user_picks', [
            'recommendation_id' => $otherRecommendation->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_unfavoriting_only_removes_zero_support_pending_or_approved_submissions(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $user = User::factory()->create();
        CreatorFavorite::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);

        $pending = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $user->id,
            'status' => 'pending',
            'title' => 'Early pending request',
        ]);
        $approved = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $user->id,
            'status' => 'approved',
            'title' => 'Zero support approved request',
        ]);
        $approvedWithSupport = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $user->id,
            'status' => 'approved',
            'title' => 'Supported approved request',
        ]);
        $published = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $user->id,
            'status' => 'published',
            'title' => 'Published archive request',
            'published_at' => now(),
        ]);
        $creatorAdded = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $user->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_CREATOR,
            'status' => 'approved',
            'title' => 'Creator-added request',
        ]);

        $supporter = User::factory()->create();
        UserPick::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_id' => $approvedWithSupport->id,
            'user_id' => $supporter->id,
        ]);
        foreach ([$pending, $approved, $published, $creatorAdded] as $recommendation) {
            UserPick::factory()->create([
                'creator_id' => $creator->id,
                'recommendation_id' => $recommendation->id,
                'user_id' => $user->id,
            ]);
        }

        $this->actingAs($user)
            ->post(route('creator.favorite', $creator))
            ->assertSessionHas(
                'success',
                'Creator removed from your favorites. Your upvotes for this creator were removed.',
            );

        $this->assertDatabaseMissing('recommendations', ['id' => $pending->id]);
        $this->assertDatabaseMissing('recommendations', ['id' => $approved->id]);
        $this->assertDatabaseHas('recommendations', ['id' => $approvedWithSupport->id]);
        $this->assertDatabaseHas('recommendations', ['id' => $published->id]);
        $this->assertDatabaseHas('recommendations', ['id' => $creatorAdded->id]);
        $this->assertDatabaseMissing('user_picks', [
            'recommendation_id' => $published->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseHas('user_picks', [
            'recommendation_id' => $approvedWithSupport->id,
            'user_id' => $supporter->id,
        ]);
    }

    public function test_unfavoriting_never_removes_creator_acted_on_or_historical_submissions(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $user = User::factory()->create();
        CreatorFavorite::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);

        $recommendations = collect(['coming_soon', 'scheduled', 'recorded', 'published', 'already_seen', 'passed', 'hidden'])
            ->map(fn (string $status) => Recommendation::factory()->create([
                'creator_id' => $creator->id,
                'submitted_by' => $user->id,
                'status' => $status,
                'published_at' => $status === 'published' ? now() : null,
            ]));

        $recommendations->each(fn (Recommendation $recommendation) => UserPick::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_id' => $recommendation->id,
            'user_id' => $user->id,
        ]));

        $this->actingAs($user)
            ->post(route('creator.favorite', $creator))
            ->assertRedirect();

        foreach ($recommendations as $recommendation) {
            $this->assertDatabaseHas('recommendations', ['id' => $recommendation->id]);
            $this->assertDatabaseMissing('user_picks', [
                'recommendation_id' => $recommendation->id,
                'user_id' => $user->id,
            ]);
        }
    }

    public function test_refavoriting_does_not_restore_removed_upvotes_or_unsupported_submissions(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $user = User::factory()->create();
        CreatorFavorite::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);
        $unsupported = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $user->id,
            'status' => 'approved',
            'title' => 'Unsupported request',
        ]);
        $otherRecommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
            'title' => 'Another guide request',
        ]);

        foreach ([$unsupported, $otherRecommendation] as $recommendation) {
            UserPick::factory()->create([
                'creator_id' => $creator->id,
                'recommendation_id' => $recommendation->id,
                'user_id' => $user->id,
            ]);
        }

        $this->actingAs($user)
            ->post(route('creator.favorite', $creator))
            ->assertRedirect();

        $this->assertDatabaseMissing('creator_favorites', [
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('recommendations', ['id' => $unsupported->id]);
        $this->assertDatabaseHas('recommendations', ['id' => $otherRecommendation->id]);
        $this->assertDatabaseMissing('user_picks', [
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);

        $this->post(route('creator.favorite', $creator))
            ->assertSessionHas('success', 'Creator added to your favorites.');

        $this->assertDatabaseHas('creator_favorites', [
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);
        $this->assertDatabaseMissing('recommendations', ['id' => $unsupported->id]);
        $this->assertDatabaseMissing('user_picks', [
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_non_consuming_public_statuses_do_not_show_an_upvote_action(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'already_seen',
            'title' => 'Locked recommendation',
        ]);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Locked recommendation')
            ->assertSee('No longer accepting upvotes')
            ->assertDontSee('aria-label="Upvote this recommendation"', false);

        $this->post(route('recommendations.vote', [$creator, $recommendation]))
            ->assertSessionHasErrors([
                'limit' => 'This suggestion is no longer accepting upvotes.',
            ]);

        $this->assertDatabaseCount('user_picks', 0);
        $this->assertFalse($recommendation->consumesUpvotes());
    }

    public function test_creator_hero_shows_visible_recommendation_and_vote_totals(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $visible = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);
        $hidden = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'hidden',
        ]);

        $this->addPicks($creator, $visible, 3);
        $this->addPicks($creator, $hidden, 5);

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSeeInOrder([
                '1 recommendation',
                '0 followers',
                '3 upvotes',
            ]);
    }

    public function test_add_recommendation_cta_only_shows_suggestion_resources_for_favorited_guides(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $guestResponse = $this->get(route('creator.queue', $creator));

        $guestResponse
            ->assertOk()
            ->assertSee('Add Recommendation')
            ->assertDontSee('Add Recommendation (', false);

        $unfavoritedUser = User::factory()->create();

        $this->actingAs($unfavoritedUser)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Add Recommendation')
            ->assertDontSee('Add Recommendation (', false);

        $favoritedUser = User::factory()->create();
        CreatorFavorite::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $favoritedUser->id,
        ]);

        Recommendation::factory()
            ->count(2)
            ->create([
                'creator_id' => $creator->id,
                'submitted_by' => $favoritedUser->id,
                'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            ]);

        $this->actingAs($favoritedUser)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Add Recommendation (1/3)');
    }

    public function test_add_recommendation_cta_shows_zero_remaining_for_exhausted_favorited_guides(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
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
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Add Recommendation (0/3)')
            ->assertSee('pointer-events-none bg-slate-400 shadow-none', false);
    }

    public function test_creator_owners_cannot_favorite_their_own_page(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $owner = User::factory()->create();
        CreatorOwner::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertDontSee('id="creator-favorite-toggle"', false)
            ->assertDontSee('Manage creator page')
            ->assertSee('Settings')
            ->assertSee('aria-label="Open settings for '.$creator->display_name.'"', false)
            ->assertSee(route('creators.dashboard', $creator), false);

        $this->post(route('creator.favorite', $creator))
            ->assertSessionHasErrors([
                'favorite' => 'Creators cannot favorite their own creator page.',
            ]);

        $this->assertDatabaseCount('creator_favorites', 0);
    }

    public function test_public_queue_displays_and_filters_creator_tags(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $tag = CreatorTag::query()->create([
            'creator_id' => $creator->id,
            'name' => 'Live Performance',
            'slug' => 'live-performance',
        ]);
        $tagged = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Tagged live session',
            'status' => 'approved',
        ]);
        $untagged = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Untagged studio session',
            'status' => 'approved',
        ]);
        $tagged->creatorTags()->attach($tag);

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Live Performance')
            ->assertSee('Tagged live session')
            ->assertSee('Untagged studio session');

        $this->get(route('creator.queue', [
            'creator' => $creator,
            'tag' => 'live-performance',
        ]))
            ->assertOk()
            ->assertSee('Tagged live session')
            ->assertDontSee('Untagged studio session')
            ->assertSee('value="live-performance"', false);

        $otherCreator = Creator::factory()->create();
        CreatorTag::query()->create([
            'creator_id' => $otherCreator->id,
            'name' => 'Foreign Tag',
            'slug' => 'foreign-tag',
        ]);

        $this->get(route('creator.queue', [
            'creator' => $creator,
            'tag' => 'foreign-tag',
        ]))
            ->assertOk()
            ->assertSee('Tagged live session')
            ->assertSee('Untagged studio session')
            ->assertDontSee('Foreign Tag');

        $this->assertTrue($untagged->creatorTags()->doesntExist());
    }

    public function test_non_owners_do_not_see_manage_creator_page_link(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);

        $this->actingAs(User::factory()->create())
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertDontSee('Manage creator page')
            ->assertDontSee('Settings');
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
}
