<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\CreatorOwner;
use App\Models\CreatorTag;
use App\Models\GuideAccolade;
use App\Models\Recommendation;
use App\Models\RecommendationAlternative;
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
            ->assertSee('class="px-4 sm:px-6 lg:px-8"', false)
            ->assertSee('max-w-5xl min-w-0 grid-cols-[1fr_auto]', false)
            ->assertSee('md:grid-cols-[1fr_auto_1fr]', false)
            ->assertSee('>JFragment</h1>', false)
            ->assertDontSee("JFragment's Journey", false)
            ->assertDontSee('Suggest ideas, vote with the community, and help guide what this creator makes next.')
            ->assertSee('Exploring music, culture, and first-listen discoveries.')
            ->assertSee('aria-label="Open creator actions"', false)
            ->assertSee('Biography')
            ->assertSee('Description')
            ->assertSee('More info')
            ->assertSee('Request guidance')
            ->assertSee('Tell me why this recommendation matters to you.')
            ->assertSee('A note from JFragment')
            ->assertSee('aria-label="JFragment avatar"', false)
            ->assertSee('&lt;script&gt;alert(&#039;nope&#039;)&lt;/script&gt;', false)
            ->assertDontSee('<script>alert', false)
            ->assertSeeInOrder(['Biography', 'Request guidance'])
            ->assertDontSee('<details', false)
            ->assertSee('Add Request')
            ->assertSee('aria-label="Add a request for JFragment"', false)
            ->assertSee('Visit Channel')
            ->assertSee('aria-label="Visit JFragment\'s YouTube channel"', false)
            ->assertDontSee('Submit a recommendation')
            ->assertDontSee('Visit YouTube channel')
            ->assertSeeInOrder([
                'Your limits',
                'Filter requests',
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
            ->assertSee('Request guidance')
            ->assertSee('No biography has been added for this creator yet.')
            ->assertSee('This creator has not added request guidance yet.')
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

    public function test_it_orders_by_votes_then_oldest_submission_and_ignores_pinned_state(): void
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
        $this->addPicks($creator, $mostVotes, 4);
        $this->addPicks($creator, $newest, 3);
        $this->addPicks($creator, $oldest, 3);

        $this->get('/jfragment')
            ->assertOk()
            ->assertSeeInOrder([
                'Most voted recommendation',
                'Oldest recommendation',
                'Newest recommendation',
                'Pinned recommendation',
            ]);
    }

    public function test_vote_changes_recalculate_public_ranking(): void
    {
        $creator = Creator::factory()->create(['slug' => 'ranking-vote-changes']);
        $older = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Older three vote suggestion',
            'status' => 'approved',
            'created_at' => now()->subDay(),
        ]);
        $newer = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Newer changing suggestion',
            'status' => 'approved',
            'created_at' => now(),
        ]);

        $this->addPicks($creator, $older, 3);
        $this->addPicks($creator, $newer, 3);

        $this->get(route('creator.queue', $creator))
            ->assertSeeInOrder([$older->title, $newer->title]);

        $extraVote = UserPick::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_id' => $newer->id,
            'vote_count' => 1,
        ]);

        $this->get(route('creator.queue', $creator))
            ->assertSeeInOrder([$newer->title, $older->title]);

        $extraVote->delete();

        $this->get(route('creator.queue', $creator))
            ->assertSeeInOrder([$older->title, $newer->title]);
    }

    public function test_identical_votes_and_submission_times_use_id_ascending(): void
    {
        $creator = Creator::factory()->create(['slug' => 'deterministic-ranking']);
        $submittedAt = now()->subHour();
        $first = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Lower ID suggestion',
            'status' => 'approved',
            'created_at' => $submittedAt,
        ]);
        $second = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Higher ID suggestion',
            'status' => 'approved',
            'created_at' => $submittedAt,
        ]);
        $this->addPicks($creator, $first, 3);
        $this->addPicks($creator, $second, 3);

        $this->get(route('creator.queue', $creator))
            ->assertSeeInOrder([$first->title, $second->title]);
    }

    public function test_vote_ordering_is_stable_across_pagination_boundaries(): void
    {
        $creator = Creator::factory()->create(['slug' => 'paginated-ranking']);
        $submittedAt = now()->subDay();
        $recommendations = collect(range(1, 27))->map(fn (int $number) => Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => "Boundary suggestion {$number}",
            'status' => 'approved',
            'created_at' => $submittedAt,
        ]));

        $firstPage = $this->get(route('creator.queue', $creator))->assertOk();
        $secondPage = $this->get(route('creator.queue', ['creator' => $creator, 'page' => 2]))->assertOk();

        $this->assertSame(
            $recommendations->take(25)->pluck('id')->all(),
            $firstPage->viewData('recommendations')->getCollection()->pluck('id')->all(),
        );
        $this->assertSame(
            $recommendations->skip(25)->pluck('id')->all(),
            $secondPage->viewData('recommendations')->getCollection()->pluck('id')->all(),
        );
        $secondPage->assertSee('26th')->assertSee('27th');
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
                    'message' => 'Your vote was added.',
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
                'votes',
                '2nd',
                'Second ranked request',
                '1',
                'vote',
            ])
            ->assertSee('id="recommendation-'.$first->id.'"', false)
            ->assertSee('id="recommendation-details-'.$first->id.'"', false)
            ->assertSee('aria-controls="recommendation-details-'.$first->id.'"', false)
            ->assertSee('x-bind:aria-expanded="open.toString()"', false)
            ->assertSee('x-show="open"', false)
            ->assertSee('hover:border-emerald-300', false)
            ->assertSee('focus-visible:ring-emerald-500', false)
            ->assertSee('border-emerald-400 ring-2 ring-emerald-300/70 dark:border-emerald-500 dark:ring-emerald-500/40', false)
            ->assertSee('rotate-180 text-emerald-600 dark:text-emerald-300', false)
            ->assertSee('x-data="{ open: false }"', false)
            ->assertSee('x-data="{ open: true }"', false)
            ->assertSee('data-recommendation-action-feedback', false)
            ->assertSee('Your vote was added.');

        $this->assertSame(1, substr_count($response->getContent(), 'id="recommendation-'.$first->id.'"'));
        $this->assertSame(1, substr_count($response->getContent(), 'id="recommendation-'.$second->id.'"'));
    }

    public function test_logged_in_guides_see_their_vote_and_submission_indicators(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $guide = User::factory()->create();
        $otherGuide = User::factory()->create();

        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $otherGuide->id,
            'title' => 'Unmarked request',
            'status' => 'approved',
        ]);
        $submitted = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $guide->id,
            'title' => 'Suggested by current guide',
            'status' => 'approved',
        ]);
        $voted = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $otherGuide->id,
            'title' => 'Voted by current guide',
            'status' => 'approved',
        ]);
        $submittedAndVoted = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $guide->id,
            'title' => 'Submitted and voted by current guide',
            'status' => 'approved',
        ]);

        UserPick::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_id' => $voted->id,
            'user_id' => $guide->id,
            'vote_count' => 2,
        ]);
        UserPick::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_id' => $submittedAndVoted->id,
            'user_id' => $guide->id,
            'vote_count' => 1,
        ]);
        UserPick::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_id' => $submitted->id,
            'user_id' => $otherGuide->id,
            'vote_count' => 3,
        ]);

        $response = $this->actingAs($guide)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Unmarked request')
            ->assertSee('Suggested by current guide')
            ->assertSee('Voted by current guide')
            ->assertSee('Submitted and voted by current guide')
            ->assertSee('title="You voted here with 2 votes"', false)
            ->assertSee('title="You voted here with 1 vote"', false)
            ->assertSee('title="You requested"', false)
            ->assertSee('text-emerald-700', false)
            ->assertSee('text-amber-700', false);

        $this->assertSame(4, substr_count($response->getContent(), '<span>You voted</span>'));
        $this->assertSame(4, substr_count($response->getContent(), '<span>You requested</span>'));
        $this->assertSame(2, substr_count($response->getContent(), 'Edit request details'));

        $this->actingAs(User::factory()->create())
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertDontSee('You voted')
            ->assertDontSee('You requested');

        auth()->logout();

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertDontSee('You voted')
            ->assertDontSee('You requested');
    }

    public function test_collapsed_rows_hide_avatar_stacks_while_expanded_support_keeps_them(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $requester = User::factory()->create([
            'name' => 'Original Fan',
            'public_display_name' => 'Original Fan',
            'public_handle' => 'originalfan',
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
            ->count(63)
            ->sequence(fn ($sequence) => [
                'name' => 'Voter '.str_pad((string) ($sequence->index + 1), 2, '0', STR_PAD_LEFT),
                'public_display_name' => 'Voter '.str_pad((string) ($sequence->index + 1), 2, '0', STR_PAD_LEFT),
                'public_handle' => 'voter'.str_pad((string) ($sequence->index + 1), 2, '0', STR_PAD_LEFT),
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
            ->assertSee('title="Suggested by Original Fan&#10;Founding Guide (#1)"', false)
            ->assertSee('aria-label="View Original Fan&#039;s Guide profile"', false)
            ->assertSee('href="'.route('guides.show', ['handle' => 'originalfan']).'"', false)
            ->assertSee('accolade-founding', false)
            ->assertSee('guide-accolade__number', false)
            ->assertSee('#1')
            ->assertDontSee('bg-amber-400 text-amber-950 ring-1 ring-white', false)
            ->assertSee('src="https://example.test/avatar-0.jpg"', false)
            ->assertSee('title="Supported by Voter 01&#10;Founding Guide (#2)"', false)
            ->assertSee('aria-label="View Voter 01&#039;s Guide profile"', false)
            ->assertSee('href="'.route('guides.show', ['handle' => 'voter01']).'"', false)
            ->assertSee('accolade-founding', false)
            ->assertSee('Community support')
            ->assertDontSee('title="54 more supporters"', false)
            ->assertDontSee('title="59 more supporters"', false)
            ->assertSee('title="13 additional supporters"', false)
            ->assertSee('aria-label="13 additional supporters"', false)
            ->assertSee('src="https://example.test/avatar-49.jpg"', false)
            ->assertDontSee('src="https://example.test/avatar-50.jpg"', false)
            ->assertDontSee('data-supporter-name', false)
            ->assertDontSee('this.nextElementSibling.hidden', false)
            ->assertDontSee('original@example.test');

        $collapsedHeader = Str::of($response->getContent())
            ->after('aria-controls="recommendation-details-'.$recommendation->id.'"')
            ->before('</button>');

        $this->assertStringNotContainsString('recommendation-support-avatars', (string) $collapsedHeader);
        $this->assertStringNotContainsString('Suggested by Original Fan', (string) $collapsedHeader);
        $this->assertStringNotContainsString('Supported by Voter', (string) $collapsedHeader);
        $this->assertStringNotContainsString('more supporters', (string) $collapsedHeader);
        $this->assertStringContainsString('items-center', (string) $collapsedHeader);
        $this->assertStringContainsString('sm:min-h-[66px]', (string) $collapsedHeader);
    }

    public function test_full_community_support_separates_up_to_twenty_larger_avatars(): void
    {
        $creator = Creator::factory()->create(['slug' => 'support-layout']);
        $requester = User::factory()->create(['public_display_name' => 'Layout Requester']);
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $requester->id,
            'status' => 'approved',
        ]);

        $longNameSupporter = User::factory()->create([
            'name' => 'Private Google Profile Name',
            'public_display_name' => 'Samantha with a very long public name ðŸŒŸ',
            'email' => 'private-google@example.test',
        ]);

        User::factory()->count(9)->create()->push($longNameSupporter)->each(fn (User $user) => UserPick::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_id' => $recommendation->id,
            'user_id' => $user->id,
        ]));

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('size-9 text-sm sm:size-10', false)
            ->assertSee('grid w-full grid-cols-[repeat(auto-fill,minmax(3.25rem,1fr))] items-start gap-x-3 gap-y-4 px-1', false)
            ->assertSee('data-supporter-name', false)
            ->assertSee('Samantha with a very long public name ðŸŒŸ')
            ->assertSee('max-w-[88px] truncate', false)
            ->assertDontSee('Private Google Profile Name')
            ->assertDontSee('private-google@example.test')
            ->assertDontSee('additional supporters');
    }

    public function test_detail_support_grid_handles_small_and_large_support_counts_without_stacking(): void
    {
        $creator = Creator::factory()->create();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);
        $recommendation->load('submittedBy');
        $this->blade(
            '<x-recommendation-support-avatars :recommendation="$recommendation" :limit="1" :include-upvoters="false" layout="detail" />',
            compact('recommendation'),
        )
            ->assertSee('flex-wrap gap-x-2 gap-y-3', false)
            ->assertDontSee('grid-cols-[repeat(auto-fill', false);
        $created = 0;

        foreach ([1, 4, 8, 12, 20, 30] as $supportCount) {
            User::factory()->count($supportCount - $created)->create()->each(fn (User $user) => UserPick::factory()->create([
                'creator_id' => $creator->id,
                'recommendation_id' => $recommendation->id,
                'user_id' => $user->id,
            ]));
            $created = $supportCount;
            $recommendation->load('userPicks.user');

            $view = $this->blade(
                '<x-recommendation-support-avatars :recommendation="$recommendation" :limit="50" :include-requester="false" layout="detail" />',
                compact('recommendation'),
            );

            $view
                ->assertSee('grid-cols-[repeat(auto-fill,minmax(3.25rem,1fr))]', false)
                ->assertDontSee('-ml-', false);
            $this->assertSame($supportCount, substr_count((string) $view, 'guide-avatar relative inline-flex'));
        }
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
            ->assertSee('Most votes')
            ->assertSee('Newest')
            ->assertSee('Status')
            ->assertSee('Scheduled date')
            ->assertSee('Filter requests')
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
            ->assertSee('No requests yet. Be the first to suggest something for this journey.')
            ->assertSee('Submit request');

        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Visible request',
            'status' => 'approved',
        ]);

        $this->get(route('creator.queue', ['creator' => $creator, 'q' => 'missing']))
            ->assertOk()
            ->assertSee('No requests found.')
            ->assertDontSee('No requests yet. Be the first to suggest something for this journey.');
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
            'category' => 'music',
            'description' => 'Trace one drum break across decades of music.',
            'status' => 'approved',
        ]);

        $response = $this->get('/jfragment');

        $response
            ->assertOk()
            ->assertSee('Topic')
            ->assertSee('The history of the Amen break')
            ->assertSee('Trace one drum break across decades of music.')
            ->assertSee('Topic request')
            ->assertSee('border-l-2 border-cyan-500/60', false)
            ->assertSee('text-cyan-800 dark:text-cyan-200', false)
            ->assertSee('aria-hidden="true"', false)
            ->assertDontSee('Community topic')
            ->assertDontSee('aria-label="Open original link for The history of the Amen break"', false)
            ->assertDontSee('Watch on YouTube');

        $response->assertSeeInOrder([
            'The history of the Amen break',
            'Approved',
            'Topic',
            'music',
            'Submitted',
            'Suggested by',
            'Topic description',
        ]);
    }

    public function test_published_topic_can_show_real_published_media_without_an_original_placeholder(): void
    {
        $creator = Creator::factory()->create(['slug' => 'published-topic']);
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_type' => 'topic',
            'youtube_url' => null,
            'youtube_video_id' => null,
            'title' => 'A community topic that became a video',
            'status' => 'published',
            'published_reaction_url' => 'https://www.youtube.com/watch?v=TOPICVIDEO1',
            'published_thumbnail_url' => 'https://img.youtube.com/vi/TOPICVIDEO1/hqdefault.jpg',
        ]);

        $this->get(route('creators.published', $creator).'#recommendation-'.$recommendation->id)
            ->assertOk()
            ->assertSee('https://img.youtube.com/vi/TOPICVIDEO1/hqdefault.jpg', false)
            ->assertSee('Open published video: A community topic that became a video', false)
            ->assertDontSee('Community topic');
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
        $newest = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Original newest request title',
            'status' => 'published',
            'category' => 'music',
            'published_at' => '2026-07-06 12:00:00',
            'published_reaction_url' => 'https://www.youtube.com/watch?v=PUBLISHED01',
            'published_title' => 'Creator finished video',
            'published_channel' => 'Creator Finished Channel',
            'published_thumbnail_url' => 'https://img.youtube.com/vi/PUBLISHED01/hqdefault.jpg',
        ]);
        $fallback = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Fallback original published title',
            'status' => 'published',
            'published_at' => '2026-07-05 12:00:00',
            'youtube_url' => 'https://www.youtube.com/watch?v=FALLBACK001',
            'youtube_video_id' => 'FALLBACK001',
        ]);
        $third = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Third published sidebar item',
            'status' => 'published',
            'published_at' => '2026-07-04 12:00:00',
        ]);
        $fourth = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Fourth published sidebar item',
            'status' => 'published',
            'published_at' => '2026-07-03 12:00:00',
        ]);
        $fifth = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Old published sidebar item',
            'status' => 'published',
            'published_at' => '2026-07-02 12:00:00',
        ]);

        $this->addPicks($creator, $newest, 2);
        $this->addPicks($creator, $fallback, 5);

        $response = $this->get(route('creator.queue', $creator));

        $response
            ->assertOk()
            ->assertSee('Recently Published')
            ->assertSee('View all published')
            ->assertSee('divide-y divide-slate-200/80 dark:divide-slate-700/50', false)
            ->assertSee('w-[84px]', false)
            ->assertSee('hover:bg-emerald-50/70', false)
            ->assertSee('focus-visible:ring-emerald-500', false)
            ->assertSee('mt-4 flex justify-end', false)
            ->assertDontSee('bg-emerald-100 px-2.5 py-1 text-[11px]', false)
            ->assertDontSee('bg-red-600/95 text-white shadow-md', false)
            ->assertSeeInOrder([
                'Creator finished video',
                'Fallback original published title',
                'Third published sidebar item',
                'Fourth published sidebar item',
            ])
            ->assertSee('Jul 6, 2026 &middot; 2 votes', false)
            ->assertSee('Creator finished video')
            ->assertSee('https://img.youtube.com/vi/PUBLISHED01/hqdefault.jpg', false)
            ->assertSee('Fallback original published title')
            ->assertSee('https://img.youtube.com/vi/FALLBACK001/hqdefault.jpg', false)
            ->assertSee(route('creators.published', $creator).'#recommendation-'.$newest->id, false)
            ->assertSee(route('creators.published', $creator).'#recommendation-'.$fallback->id, false)
            ->assertSee('2 votes')
            ->assertSee('5 votes')
            ->assertDontSee('Creator Finished Channel')
            ->assertDontSee('Old published sidebar item')
            ->assertDontSee(route('creators.published', $creator).'#recommendation-'.$fifth->id, false);

        $this->assertStringContainsString('Active community request', $response->getContent());
        $this->assertStringNotContainsString('Original newest request title', $response->getContent());
        $this->assertStringNotContainsString('id="recommendation-'.$newest->id.'"', $response->getContent());
        $this->assertStringNotContainsString('id="recommendation-'.$third->id.'"', $response->getContent());
        $this->assertStringNotContainsString('id="recommendation-'.$fourth->id.'"', $response->getContent());
        $this->assertStringContainsString('id="recommendation-'.$active->id.'"', $response->getContent());
    }

    public function test_creator_page_shows_recently_published_empty_state(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Recently Published')
            ->assertSee('No published requests yet.');
    }

    public function test_exhausted_vote_alert_explains_when_votes_return(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $user = User::factory()->create();

        $errors = (new ViewErrorBag)->put(
            'default',
            new MessageBag([
                'limit' => ["You've used all your votes for this creator."],
            ]),
        );

        $this->actingAs($user)
            ->withSession(['errors' => $errors])
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee("You've used all your votes for this creator.")
            ->assertSee('You’ll get votes back when requests you supported are published or closed.');
    }

    public function test_published_page_lists_searches_and_selects_published_recommendations(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $tag = CreatorTag::query()->create([
            'creator_id' => $creator->id,
            'name' => 'Deep Dive',
            'slug' => 'deep-dive',
        ]);
        $requester = User::factory()->create([
            'name' => 'Google Requester Name',
            'public_display_name' => 'Public Requester',
            'public_handle' => 'publicrequester',
            'avatar_url' => null,
        ]);
        $supporter = User::factory()->create([
            'name' => 'Google Supporter Name',
            'public_display_name' => 'Public Supporter',
            'public_handle' => 'publicsupporter',
            'avatar_url' => 'https://example.test/supporter.jpg',
        ]);
        $secondSupporter = User::factory()->create([
            'name' => 'Second Google Supporter',
            'public_display_name' => 'Second Supporter',
            'public_handle' => 'secondsupporter',
        ]);
        $newer = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $requester->id,
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
            'youtube_url' => 'https://www.youtube.com/watch?v=ORIGINAL001',
            'youtube_video_id' => 'ORIGINAL001',
            'published_at' => '2026-07-03 12:00:00',
        ]);
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Active request',
            'status' => 'approved',
        ]);
        $newer->creatorTags()->attach($tag);
        UserPick::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_id' => $newer->id,
            'user_id' => $supporter->id,
            'vote_count' => 3,
        ]);
        UserPick::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_id' => $newer->id,
            'user_id' => $secondSupporter->id,
            'vote_count' => 1,
        ]);

        $response = $this->get(route('creators.published', $creator));

        $response
            ->assertOk()
            ->assertSee('Published Requests')
            ->assertSee('Ideas this creator has already made, covered, explored, or published.')
            ->assertSeeInOrder([
                'Creator reaction release',
                'Older published request',
            ])
            ->assertDontSee('Active request')
            ->assertSee('grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3', false)
            ->assertSee('Selected published request')
            ->assertSee("selectRecommendation({$newer->id})", false)
            ->assertSee("selectedId === {$newer->id}", false)
            ->assertSee('href="'.route('creators.published', $creator).'#recommendation-'.$newer->id.'"', false)
            ->assertSee('Watch published content')
            ->assertSee('https://www.youtube.com/watch?v=REACTION001', false)
            ->assertSee('aria-label="Open published video: Creator reaction release"', false)
            ->assertSee('rel="noopener noreferrer nofollow ugc"', false)
            ->assertSee('bg-red-600/95', false)
            ->assertSee('https://img.youtube.com/vi/REACTION001/hqdefault.jpg', false)
            ->assertSee('Creator Channel')
            ->assertSee('Suggested by')
            ->assertSee('Community support')
            ->assertSee('Public Requester')
            ->assertSee('title="Suggested by Public Requester', false)
            ->assertSee('title="Supported by Public Supporter', false)
            ->assertSee('title="Supported by Second Supporter', false)
            ->assertSee('src="https://example.test/supporter.jpg"', false)
            ->assertSee('4 votes when published')
            ->assertSee('No votes yet.')
            ->assertDontSee('Google Requester Name')
            ->assertDontSee('Google Supporter Name')
            ->assertSee('Original request')
            ->assertSee('Why this was suggested')
            ->assertSee('Published context should stay plain: https://example.com &lt;script&gt;alert(1)&lt;/script&gt;', false)
            ->assertDontSee('href="https://example.com"', false)
            ->assertSee('Newer published request')
            ->assertSee('https://www.youtube.com/watch?v=SOURCE00001', false)
            ->assertSee('href="https://www.youtube.com/watch?v=ORIGINAL001"', false)
            ->assertSee('aria-label="Open published video: Older published request"', false)
            ->assertDontSee('Upvote')
            ->assertDontSee('No longer accepting votes');

        $catalogMarkup = Str::of($response->getContent())
            ->after('grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3')
            ->before('No published requests yet.');

        $this->assertStringNotContainsString('bg-red-600/95', (string) $catalogMarkup);
        $this->assertStringNotContainsString('Published work', (string) $catalogMarkup);
        $this->assertStringNotContainsString('Community support', (string) $catalogMarkup);

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
            ->assertSee('No published requests found.');
    }

    public function test_it_displays_a_youtube_thumbnail_when_a_video_id_is_available(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);

        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'youtube_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
            'title' => 'Never Gonna Give You Up',
            'status' => 'approved',
        ]);

        $response = $this->get('/jfragment')
            ->assertOk()
            ->assertSee('https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg', false)
            ->assertSee('onerror="this.hidden = true"', false)
            ->assertSee('Thumbnail for Never Gonna Give You Up')
            ->assertDontSee('border-cyan-500/60', false)
            ->assertDontSee('text-cyan-800 dark:text-cyan-200', false);

        $collapsedHeader = Str::of($response->getContent())
            ->after('aria-controls="recommendation-details-'.$recommendation->id.'"')
            ->before('</button>');

        $this->assertStringContainsString('width="88"', (string) $collapsedHeader);
        $this->assertStringContainsString('height="50"', (string) $collapsedHeader);
        $this->assertStringContainsString('loading="lazy"', (string) $collapsedHeader);
        $this->assertStringContainsString('decoding="async"', (string) $collapsedHeader);
        $this->assertStringContainsString('sm:h-[50px] sm:w-[88px]', (string) $collapsedHeader);
        $this->assertStringContainsString('dark:text-slate-100', (string) $collapsedHeader);
        $this->assertStringNotContainsString('fill-current', (string) $collapsedHeader);
    }

    public function test_compact_topic_rows_show_a_topic_tile_without_an_image(): void
    {
        $creator = Creator::factory()->create(['slug' => 'topic-creator']);

        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_type' => 'topic',
            'youtube_url' => null,
            'youtube_video_id' => null,
            'title' => 'A compact community topic',
            'status' => 'approved',
        ]);

        $response = $this->get(route('creator.queue', $creator))->assertOk();
        $collapsedHeader = Str::of($response->getContent())
            ->after('aria-controls="recommendation-details-'.$recommendation->id.'"')
            ->before('</button>');

        $this->assertStringContainsString('A compact community topic', (string) $collapsedHeader);
        $this->assertStringNotContainsString('<img', (string) $collapsedHeader);
        $this->assertStringContainsString('sm:h-[50px] sm:w-[88px]', (string) $collapsedHeader);
        $this->assertStringContainsString('bg-gradient-to-br from-slate-900 via-indigo-950 to-indigo-800', (string) $collapsedHeader);
        $this->assertStringContainsString('tracking-[0.18em]', (string) $collapsedHeader);
        $this->assertStringContainsString('Topic</span>', (string) $collapsedHeader);
        $this->assertStringNotContainsString('fill-current', (string) $collapsedHeader);
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
        $submitter = User::factory()->create([
            'name' => 'Example Fan',
            'public_display_name' => 'Example Fan',
            'public_handle' => 'examplefan',
        ]);
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
            ->assertSee('Topic')
            ->assertDontSee('Community topic')
            ->assertSee('Suggested by Example Fan')
            ->assertSee('aria-label="Add vote to this request"', false)
            ->assertSee('mt-5 flex items-center justify-end', false)
            ->assertSee('inline-flex w-full flex-col gap-3 rounded-2xl', false)
            ->assertDontSee('votes total')
            ->assertSeeInOrder([
                'aria-hidden="true" class="text-3xl font-extrabold',
                '>0 total votes</span>',
                '>0/3</p>',
                'aria-label="Add vote to this request"',
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
            ->assertSee('aria-label="Remove vote from this request"', false)
            ->assertSee('name="vote_action" value="remove"', false)
            ->assertSee('1')
            ->assertSee('vote');
    }

    public function test_mobile_vote_module_centers_controls_and_supports_multi_digit_allocations(): void
    {
        $creator = Creator::factory()->create(['slug' => 'mobile-vote-controls']);
        $guide = User::factory()->create(['plan_slug' => 'pro']);
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Mobile vote layout',
            'status' => 'approved',
        ]);
        UserPick::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_id' => $recommendation->id,
            'user_id' => $guide->id,
            'vote_count' => 10,
        ]);

        $response = $this->actingAs($guide)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('data-vote-controls', false)
            ->assertSee('grid-cols-[2.75rem_minmax(4.5rem,auto)_2.75rem]', false)
            ->assertSee('sm:flex sm:w-auto sm:max-w-none sm:justify-end', false)
            ->assertSee('>10/10</p>', false)
            ->assertSee('whitespace-nowrap', false)
            ->assertSee('aria-label="Remove vote from this request"', false)
            ->assertSee('aria-label="Add vote to this request"', false);

        $voteModule = Str::of($response->getContent())
            ->after('data-vote-controls')
            ->before('</div>\n                    @else');

        $this->assertGreaterThanOrEqual(2, substr_count((string) $voteModule, 'size-11'));
        $this->assertStringContainsString('disabled', (string) $voteModule);
    }

    public function test_mobile_expanded_support_uses_generic_founder_og_and_future_accolades(): void
    {
        $creator = Creator::factory()->create(['slug' => 'mobile-accolades']);
        $founder = User::factory()->create([
            'guide_number' => 45,
            'public_display_name' => 'Mobile Founder',
        ]);
        $og = User::factory()->create([
            'guide_number' => 233,
            'public_display_name' => 'Mobile OG',
        ]);
        GuideAccolade::factory()->create([
            'code' => 'mobile_future',
            'label' => 'Mobile Future',
            'rule_type' => GuideAccolade::RULE_GUIDE_NUMBER_RANGE,
            'minimum_guide_number' => 700,
            'maximum_guide_number' => 800,
            'priority' => 80,
            'display_number_plate' => true,
            'plate_prefix' => '#',
            'css_class' => 'accolade-mobile-future',
        ]);
        $future = User::factory()->create([
            'guide_number' => 750,
            'public_display_name' => 'Mobile Future Guide',
        ]);
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $founder->id,
            'title' => 'Mobile accolade support',
            'status' => 'approved',
        ]);
        foreach ([$og, $future] as $supporter) {
            UserPick::factory()->create([
                'creator_id' => $creator->id,
                'recommendation_id' => $recommendation->id,
                'user_id' => $supporter->id,
            ]);
        }

        $response = $this->get(route('creator.queue', $creator))->assertOk();

        foreach ([
            ['accolade-founding', '#45'],
            ['accolade-og', '#233'],
            ['accolade-mobile-future', '#750'],
        ] as [$class, $plate]) {
            $response->assertSee($class, false)->assertSee($plate);
        }
        $response
            ->assertSee('guide-avatar relative inline-flex', false)
            ->assertSee('z-10 accolade-', false)
            ->assertSee('guide-accolade__number absolute bottom-0 left-1/2 z-30', false)
            ->assertSee('grid-cols-[repeat(auto-fill,minmax(3.25rem,1fr))]', false)
            ->assertSee('overflow-visible', false);

        $accoladeCss = file_get_contents(resource_path('css/app.css'));
        $this->assertDoesNotMatchRegularExpression('/@media[^}]*accolade[^}]*display\s*:\s*none/is', $accoladeCss);
    }

    public function test_logged_in_guides_can_privately_suggest_an_alternative_video(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Original request',
            'status' => 'approved',
        ]);
        $guide = User::factory()->create();

        $this->actingAs($guide)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Suggest alternative')
            ->assertSee('Suggest an alternative')
            ->assertSee(route('recommendations.alternatives.store', [$creator, $recommendation]), false)
            ->assertSee('Know a better version of this request?');

        $this->post(route('recommendations.alternatives.store', [$creator, $recommendation]), [
            'alternative_url' => 'https://www.youtube.com/watch?v=ALTernatE01',
            'reason' => '<strong>This live version has clearer audio.</strong>',
            'alternative_recommendation_id' => $recommendation->id,
        ])
            ->assertRedirect(route('creator.queue', $creator).'#recommendation-'.$recommendation->id);

        $this->assertDatabaseHas('recommendation_alternatives', [
            'recommendation_id' => $recommendation->id,
            'user_id' => $guide->id,
            'alternative_url' => 'https://www.youtube.com/watch?v=ALTernatE01',
            'alternative_video_id' => 'ALTernatE01',
            'reason' => 'This live version has clearer audio.',
            'status' => RecommendationAlternative::STATUS_PENDING,
        ]);
    }

    public function test_original_guide_sees_withdraw_action_on_their_active_recommendation_only(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $submitter = User::factory()->create();
        $otherGuide = User::factory()->create();
        $guideRecommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $submitter->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'title' => 'Guide owned request',
            'status' => 'approved',
        ]);
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $submitter->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_CREATOR,
            'title' => 'Creator added request',
            'status' => 'approved',
        ]);
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $submitter->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'title' => 'Finished guide request',
            'status' => 'passed',
        ]);

        $response = $this->actingAs($submitter)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Guide owned request')
            ->assertSee('Suggest alternative')
            ->assertSee('aria-label="Withdraw this request"', false)
            ->assertSee('Withdraw this request?')
            ->assertSee('This removes it from the active list and returns any active votes placed on it.')
            ->assertSee(route('recommendations.withdraw', [$creator, $guideRecommendation]), false)
            ->assertSeeInOrder(['Suggest alternative', 'Withdraw request']);

        $this->assertSame(
            1,
            substr_count($response->getContent(), 'aria-label="Withdraw this request"'),
        );

        $this->actingAs($otherGuide)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertDontSee('aria-label="Withdraw this request"', false)
            ->assertDontSee('Withdraw this request?');

        auth()->logout();

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertDontSee('aria-label="Withdraw this request"', false)
            ->assertDontSee('Withdraw this request?');
    }

    public function test_alternatives_are_private_to_creator_owners(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $owner = User::factory()->create(['name' => 'Creator Owner']);
        $guide = User::factory()->create([
            'name' => 'Helpful Guide',
            'public_display_name' => 'Helpful Guide',
            'public_handle' => 'helpfulguide',
        ]);
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Request with private alternatives',
            'status' => 'approved',
        ]);
        $creator->creatorOwners()->create([
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);
        $recommendation->alternatives()->create([
            'user_id' => $guide->id,
            'alternative_url' => 'https://www.youtube.com/watch?v=PRIVATEalt1',
            'alternative_video_id' => 'PRIVATEalt1',
            'reason' => 'This version has official captions.',
        ]);

        $this->actingAs($guide)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertDontSee('Alternative suggestions')
            ->assertDontSee('PRIVATEalt1')
            ->assertDontSee('This version has official captions.');

        $this->actingAs($owner)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Creator only')
            ->assertSee('1 alternative suggested')
            ->assertSee('Private')
            ->assertSee('https://www.youtube.com/watch?v=PRIVATEalt1')
            ->assertSee('This version has official captions.')
            ->assertSee('Suggested by Helpful Guide')
            ->assertSee('Use this alternative')
            ->assertSee('Dismiss');
    }

    public function test_creator_owner_can_accept_or_dismiss_alternatives(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $owner = User::factory()->create();
        $guide = User::factory()->create();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'youtube_url' => 'https://www.youtube.com/watch?v=ORIGINAL001',
            'youtube_video_id' => 'ORIGINAL001',
            'status' => 'approved',
        ]);
        $creator->creatorOwners()->create([
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);
        $acceptedAlternative = $recommendation->alternatives()->create([
            'user_id' => $guide->id,
            'alternative_url' => 'https://www.youtube.com/watch?v=ACCEPTED001',
            'alternative_video_id' => 'ACCEPTED001',
            'reason' => 'Better source.',
        ]);
        $dismissedAlternative = $recommendation->alternatives()->create([
            'user_id' => $guide->id,
            'alternative_url' => 'https://www.youtube.com/watch?v=DISMISSED01',
            'alternative_video_id' => 'DISMISSED01',
            'reason' => 'Duplicate source.',
        ]);

        $this->actingAs($owner)
            ->patch(route('recommendations.alternatives.accept', [$creator, $recommendation, $acceptedAlternative]))
            ->assertRedirect(route('creator.queue', $creator).'#recommendation-'.$recommendation->id);

        $this->assertDatabaseHas('recommendation_alternatives', [
            'id' => $acceptedAlternative->id,
            'status' => RecommendationAlternative::STATUS_ACCEPTED,
            'reviewed_by' => $owner->id,
        ]);
        $this->assertSame('https://www.youtube.com/watch?v=ACCEPTED001', $recommendation->fresh()->youtube_url);
        $this->assertSame('ACCEPTED001', $recommendation->fresh()->youtube_video_id);

        $this->patch(route('recommendations.alternatives.dismiss', [$creator, $recommendation, $dismissedAlternative]))
            ->assertRedirect(route('creator.queue', $creator).'#recommendation-'.$recommendation->id);

        $this->assertDatabaseHas('recommendation_alternatives', [
            'id' => $dismissedAlternative->id,
            'status' => RecommendationAlternative::STATUS_DISMISSED,
            'reviewed_by' => $owner->id,
        ]);
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
            ->assertSee('Requests closed');
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
            ->assertSee('Unfavoriting removes your active votes from this creator. Requests with no other votes may be removed.')
            ->assertSee('Remove favorite and active votes')
            ->assertSee('Active votes on this creator: 1')
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
                'Creator removed from your favorites. Your active votes for this creator were removed.',
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
                'Creator removed from your favorites. Your active votes for this creator were removed.',
            );

        $this->assertDatabaseMissing('recommendations', ['id' => $pending->id]);
        $this->assertDatabaseMissing('recommendations', ['id' => $approved->id]);
        $this->assertDatabaseHas('recommendations', ['id' => $approvedWithSupport->id]);
        $this->assertDatabaseHas('recommendations', ['id' => $published->id]);
        $this->assertDatabaseHas('recommendations', ['id' => $creatorAdded->id]);
        $this->assertDatabaseHas('user_picks', [
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
            $this->assertDatabaseHas('user_picks', [
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

    public function test_non_consuming_public_statuses_do_not_show_a_vote_action(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $recommendations = collect([
            'already_seen' => 'Already Seen',
            'coming_soon' => 'Coming Soon',
            'scheduled' => 'Scheduled',
            'recorded' => 'Recorded',
            'passed' => 'Passed',
        ])->map(fn (string $label, string $status) => Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => $status,
            'title' => "{$label} locked recommendation",
        ]));
        $recommendation = $recommendations->first();
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertDontSee('votes total')
            ->assertDontSee('No longer accepting votes')
            ->assertDontSee('aria-label="Add vote to this request"', false);

        foreach ($recommendations as $status => $lockedRecommendation) {
            $response
                ->assertSee($lockedRecommendation->title)
                ->assertSee($lockedRecommendation->statusLabel(), false);
        }

        $response->assertSee('Voting closed');

        $this->post(route('recommendations.vote', [$creator, $recommendation]))
            ->assertSessionHasErrors([
                'limit' => 'This request is no longer accepting votes.',
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
                '1 request',
                '0 followers',
                '3 votes',
            ]);
    }

    public function test_add_recommendation_cta_only_shows_suggestion_resources_for_favorited_guides(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $guestResponse = $this->get(route('creator.queue', $creator));

        $guestResponse
            ->assertOk()
            ->assertSee('Add Request')
            ->assertDontSee('Add Request (', false);

        $unfavoritedUser = User::factory()->create();

        $this->actingAs($unfavoritedUser)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Add Request')
            ->assertDontSee('Add Request (', false);

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
            ->assertSee('Add Request (1/3)');
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
            ->assertSee('Add Request (0/3)')
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

    public function test_playlist_rows_render_compact_and_expanded_playlist_treatment(): void
    {
        $creator = Creator::factory()->create(['slug' => 'playlist-creator']);
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'media_type' => 'playlist',
            'youtube_url' => 'https://www.youtube.com/playlist?list=PL1234567890',
            'normalized_url' => 'https://www.youtube.com/playlist?list=PL1234567890',
            'youtube_video_id' => null,
            'youtube_playlist_id' => 'PL1234567890',
            'thumbnail_url' => 'https://i.ytimg.com/vi/example/hqdefault.jpg',
            'source_title' => 'Essential Performances',
            'source_channel' => 'Music Guide',
            'source_item_count' => 18,
            'title' => 'Essential Performances',
            'status' => 'approved',
        ]);

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('YouTube Playlist')
            ->assertSee('18 videos')
            ->assertSee('Open playlist: Essential Performances', false)
            ->assertSee('https://i.ytimg.com/vi/example/hqdefault.jpg', false)
            ->assertDontSee('Video preview unavailable');
    }

    public function test_published_playlist_uses_playlist_metadata_and_link_treatment(): void
    {
        $creator = Creator::factory()->create(['slug' => 'published-playlist']);
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'published',
            'published_reaction_url' => 'https://www.youtube.com/playlist?list=PLPUBLISHED01',
            'published_normalized_url' => 'https://www.youtube.com/playlist?list=PLPUBLISHED01',
            'published_media_type' => 'playlist',
            'published_video_id' => null,
            'published_playlist_id' => 'PLPUBLISHED01',
            'published_title' => 'Published Playlist',
            'published_channel' => 'Creator Channel',
            'published_thumbnail_url' => 'https://i.ytimg.com/vi/published/hqdefault.jpg',
            'published_item_count' => 7,
        ]);

        $this->get(route('creators.published', $creator))
            ->assertOk()
            ->assertSee('Published Playlist')
            ->assertSee('Playlist')
            ->assertSee('7 videos')
            ->assertSee('View playlist')
            ->assertSee('Open published playlist: Published Playlist', false)
            ->assertDontSee('Video preview unavailable');
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
