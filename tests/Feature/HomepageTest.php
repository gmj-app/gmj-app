<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use App\Services\HomepageTopRequestsQuery;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class HomepageTest extends TestCase
{
    use RefreshDatabase;

    public function test_homepage_and_about_use_the_same_canonical_tagline_with_existing_highlight_markup(): void
    {
        $highlight = '<span class="bg-gradient-to-r from-sky-500 via-indigo-500 to-violet-500 bg-clip-text text-transparent dark:from-sky-400 dark:via-indigo-400 dark:to-violet-400">REQUEST</span>';

        $this->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Fans ', $highlight.'.', 'Communities ', '>VOTE</span>.', 'Creators ', '>DECIDE</span>.'], false)
            ->assertDontSee('Fans suggest. Communities vote. Creators decide.');

        $this->get('/about')
            ->assertOk()
            ->assertSee('Fans request. Communities vote. Creators decide.')
            ->assertDontSee('Fans suggest. Communities vote. Creators decide.')
            ->assertSee('class="mt-9 text-lg font-bold text-slate-300"', false);
    }

    public function test_public_navigation_and_static_pages_are_available(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('Guide My Journey')
            ->assertSee('favicon.svg', false)
            ->assertSee(route('home'), false)
            ->assertSee('class="px-4 sm:px-6 lg:px-8"', false)
            ->assertSee('mx-auto grid h-16 w-full max-w-5xl min-w-0 grid-cols-[1fr_auto] items-center gap-2 md:grid-cols-[1fr_auto_1fr]', false)
            ->assertSee('hidden items-center justify-center gap-8 text-sm font-semibold', false)
            ->assertSee('flex shrink-0 items-center justify-end gap-2', false)
            ->assertSeeInOrder(['My Hub', 'How it Works', 'FAQ'])
            ->assertDontSee('>Explore</a>', false)
            ->assertSee('Sign in')
            ->assertDontSee('Register')
            ->assertSee(route('about'), false)
            ->assertSee(route('faq'), false)
            ->assertSee(route('dashboard'), false)
            ->assertSeeInOrder(['Fans', 'REQUEST', 'Communities', 'VOTE', 'Creators', 'DECIDE'])
            ->assertSee('bg-gradient-to-r from-sky-500 via-indigo-500 to-violet-500 bg-clip-text text-transparent', false)
            ->assertDontSee('Fans suggest. Communities vote. Creators decide.');

        $this->get('/about')
            ->assertOk()
            ->assertSee('How It Works | Guide My Journey', false)
            ->assertSee('How it works')
            ->assertSee('fan requests')
            ->assertSee('content roadmap.')
            ->assertSee('A fan shares the spark')
            ->assertSee('The community adds its signal')
            ->assertSee('The strongest ideas rise')
            ->assertSee('The creator chooses the next move')
            ->assertSee('The community guides the journey.')
            ->assertSee('The creator owns the destination.')
            ->assertSee('Fans request. Communities vote. Creators decide.')
            ->assertDontSee('Fans suggest. Communities vote. Creators decide.');

        $this->get('/faq')
            ->assertOk()
            ->assertSee('What is Guide My Journey?')
            ->assertSee('Is this only for reaction channels?')
            ->assertSee('For Fans and Guides')
            ->assertSee('What are resources?')
            ->assertSee('Requests and Voting')
            ->assertSee('Can I vote for my own request?')
            ->assertSee('For Creators')
            ->assertSee('Can creators block users?')
            ->assertSee('Platform and YouTube')
            ->assertSee('Is Guide My Journey connected to YouTube?')
            ->assertSee('Is Guide My Journey free?')
            ->assertDontSee('How do creators claim or create a page?');

        $this->get('/contact')
            ->assertOk()
            ->assertSee('For questions, feedback, or creator inquiries, contact:')
            ->assertSee('support@guidemyjourney.test')
            ->assertDontSee('<form', false);
    }

    public function test_authenticated_public_navigation_keeps_account_actions(): void
    {
        $user = User::factory()->create([
            'public_display_name' => 'Public Guide',
            'public_handle' => 'public-guide',
            'avatar_url' => 'https://example.com/avatar.jpg',
        ]);

        $this->actingAs($user)
            ->get('/')
            ->assertOk()
            ->assertSeeInOrder(['My Hub', 'How it Works', 'FAQ'])
            ->assertDontSee('>Explore</a>', false)
            ->assertSee('aria-haspopup="menu"', false)
            ->assertSee(':aria-expanded="accountOpen.toString()"', false)
            ->assertSee('aria-label="Open account menu"', false)
            ->assertSee('src="https://example.com/avatar.jpg"', false)
            ->assertSee('alt="Public Guide avatar"', false)
            ->assertSee('Public Guide')
            ->assertSee($user->email)
            ->assertSee('Profile')
            ->assertSee('Theme')
            ->assertSee('Switch to light theme')
            ->assertSee('Switch to dark theme')
            ->assertSee('Log out')
            ->assertDontSee('Creator Dashboard')
            ->assertDontSee('Sign in')
            ->assertSee(route('profile.edit'), false)
            ->assertSee(route('logout'), false);
    }

    public function test_homepage_hero_shows_live_platform_stats(): void
    {
        Creator::factory()->count(2)->create();
        Creator::factory()->create([
            'display_name' => 'Inactive Stats Creator',
            'status' => 'inactive',
            'deactivated_at' => now(),
        ]);
        User::factory()->count(1001)->create();

        $response = $this->get('/');

        $response
            ->assertOk()
            ->assertSee('data-home-search-group', false)
            ->assertSee('max-w-[54rem]', false)
            ->assertSee('lg:grid-cols-[minmax(0,1fr)_auto]', false)
            ->assertSee('method="GET" action="'.route('search.index').'"', false)
            ->assertSee('name="q"', false)
            ->assertSee('placeholder="Search creators, artists, songs, or topics..."', false)
            ->assertSee('<button type="submit"', false)
            ->assertSee('Search')
            ->assertSee('data-platform-stats', false)
            ->assertSee('aria-label="Platform stats"', false)
            ->assertSee('>2</dd>', false)
            ->assertSee('Creators')
            ->assertSee('>1,001</dd>', false)
            ->assertSee('Guides')
            ->assertSee('text-sm font-extrabold tabular-nums', false)
            ->assertDontSee('min-w-36', false)
            ->assertDontSee('Inactive Stats Creator');

        $this->assertSame(1, substr_count($response->getContent(), 'data-home-search-group'));
        $this->assertSame(1, substr_count($response->getContent(), 'data-platform-stats'));
        $this->assertSame(1, substr_count($response->getContent(), '<form method="GET" action="'.route('search.index').'"'));
    }

    public function test_compact_platform_stats_format_large_counts_without_queries_or_interaction(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->blade(
            '<x-homepage-platform-stats :creator-count="$creatorCount" :guide-count="$guideCount" />',
            ['creatorCount' => 1000, 'guideCount' => 1000000],
        );

        $response
            ->assertSee('>1,000</dd>', false)
            ->assertSee('>1,000,000</dd>', false)
            ->assertSee('min-w-48', false)
            ->assertDontSee('<a ', false)
            ->assertDontSee('<button', false);
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_homepage_ranks_creators_by_votes_on_visible_recommendations(): void
    {
        $popularCreator = Creator::factory()->create([
            'display_name' => 'Popular Creator',
            'slug' => 'popular-creator',
            'youtube_channel_title' => 'Popular Creator Channel',
            'youtube_thumbnail_url' => 'https://example.com/popular-creator.jpg',
            'youtube_banner_url' => 'https://example.com/popular-creator-banner.jpg',
            'verification_status' => 'verified',
        ]);
        $secondCreator = Creator::factory()->create([
            'display_name' => 'Second Creator',
            'slug' => 'second-creator',
        ]);

        $topRequest = Recommendation::factory()->create([
            'creator_id' => $popularCreator->id,
            'title' => 'Visible top request',
            'status' => 'approved',
        ]);
        $hiddenRequest = Recommendation::factory()->create([
            'creator_id' => $popularCreator->id,
            'title' => 'Hidden request with many votes',
            'status' => 'hidden',
        ]);
        $secondRequest = Recommendation::factory()->create([
            'creator_id' => $secondCreator->id,
            'title' => 'Second creator request',
            'status' => 'scheduled',
        ]);
        $passedRequest = Recommendation::factory()->create([
            'creator_id' => $secondCreator->id,
            'title' => 'Passed public request',
            'status' => 'passed',
        ]);

        $this->addVotes($topRequest, 3);
        $this->addVotes($hiddenRequest, 5);
        $this->addVotes($secondRequest, 2);
        $this->addVotes($passedRequest, 1);

        $this->get('/')
            ->assertOk()
            ->assertSee('Guide My Journey')
            ->assertSee('sm:pb-8 sm:pt-12', false)
            ->assertSee('sm:pb-14 sm:pt-8', false)
            ->assertSee('mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3', false)
            ->assertDontSee('sm:pb-24 sm:pt-20', false)
            ->assertDontSee('sm:py-20', false)
            ->assertSeeInOrder(['Fans ', '>REQUEST</span>.', 'Communities ', '>VOTE</span>.', 'Creators ', '>DECIDE</span>.'], false)
            ->assertSee('from-sky-500 via-indigo-500 to-violet-500 bg-clip-text text-transparent', false)
            ->assertSee('placeholder="Search creators, artists, songs, or topics..."', false)
            ->assertDontSee('Community-powered creator journeys')
            ->assertDontSee('Favorite creators, suggest ideas or links, and vote for what you want to see next.')
            ->assertSee('Popular creators')
            ->assertSee('Add Creator Account')
            ->assertSee(route('creators.create'), false)
            ->assertSee('aria-label="Add Creator Account"', false)
            ->assertSee('Verified')
            ->assertSee('Top requests')
            ->assertSee('Visible top request')
            ->assertSee(route('creator.queue', $popularCreator), false)
            ->assertSee('aria-label="View Popular Creator"', false)
            ->assertDontSee('aria-label="View Popular Creator\'s journey"', false)
            ->assertSee('src="https://example.com/popular-creator-banner.jpg"', false)
            ->assertSee('loading="lazy"', false)
            ->assertSee('bg-gradient-to-br from-indigo-600 via-sky-600 to-violet-600', false)
            ->assertSee('ring-4 ring-white dark:ring-slate-900', false)
            ->assertSee('src="https://example.com/popular-creator.jpg"', false)
            ->assertSee('alt="Popular Creator avatar"', false)
            ->assertSee('onerror="this.previousElementSibling.removeAttribute(\'aria-hidden\'); this.remove()"', false)
            ->assertSee('aria-label="Second Creator avatar"', false)
            ->assertSee('>SC</span>', false)
            ->assertSee('mt-auto border-t', false)
            ->assertDontSee('View journey', false)
            ->assertDontSee('&rarr;', false)
            ->assertDontSee('inline-flex min-h-12 w-full items-center justify-center rounded-full', false)
            ->assertDontSee('Hidden request with many votes')
            ->assertDontSee('Second creator request')
            ->assertDontSee('Passed public request')
            ->assertSeeInOrder([
                'Popular Creator',
                'Second Creator',
            ]);
    }

    public function test_requester_badge_is_consistent_on_homepage_and_search_results(): void
    {
        $guide = User::factory()->create();
        $creator = Creator::factory()->create(['display_name' => 'Badge Search Creator']);
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $guide->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_CREATOR,
            'title' => 'Historical Badge Needle',
            'status' => 'approved',
        ]);

        $this->actingAs($guide)->get(route('home'))->assertOk()->assertSee('You requested');
        $this->actingAs($guide)->get(route('search.index', ['q' => 'Badge Needle']))->assertOk()->assertSee('You requested');
        $this->actingAs(User::factory()->create())->get(route('search.index', ['q' => 'Badge Needle']))->assertOk()->assertDontSee('You requested');
    }

    public function test_homepage_shows_up_to_three_top_public_requests_per_creator(): void
    {
        $creator = Creator::factory()->create([
            'display_name' => 'Three Request Creator',
            'slug' => 'three-request-creator',
        ]);

        $newestTopRequest = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Newest tied top request',
            'status' => 'approved',
            'created_at' => now(),
        ]);
        $olderTopRequest = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Older tied top request',
            'status' => 'approved',
            'created_at' => now()->subDay(),
        ]);
        $thirdRequest = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Third highest public request',
            'status' => 'approved',
            'created_at' => now()->subDays(2),
        ]);
        $fourthRequest = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Fourth public request',
            'status' => 'passed',
            'created_at' => now()->subDays(3),
        ]);
        $hiddenRequest = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Hidden request must not appear',
            'status' => 'hidden',
        ]);

        $this->addVotes($newestTopRequest, 3);
        $this->addVotes($olderTopRequest, 3);
        $this->addVotes($thirdRequest, 2);
        $this->addVotes($fourthRequest, 1);
        $this->addVotes($hiddenRequest, 10);

        $this->get('/')
            ->assertOk()
            ->assertSee('Top requests')
            ->assertSeeInOrder([
                'Newest tied top request',
                'Older tied top request',
                'Third highest public request',
            ])
            ->assertSee('line-clamp-2 min-w-0 text-sm font-medium leading-5', false)
            ->assertDontSee('Fourth public request')
            ->assertDontSee('Hidden request must not appear');
    }

    public function test_homepage_top_request_projection_is_bounded_ranked_and_mysql_safe(): void
    {
        $first = Creator::factory()->create(['display_name' => 'Projection One']);
        $second = Creator::factory()->create(['display_name' => 'Projection Two']);
        $empty = Creator::factory()->create(['display_name' => 'Projection Empty']);

        $requests = collect();
        foreach ([$first, $second] as $creator) {
            foreach (range(1, 5) as $position) {
                $recommendation = Recommendation::factory()->create([
                    'creator_id' => $creator->id,
                    'title' => "{$creator->display_name} request {$position}",
                    'status' => 'approved',
                    'created_at' => now()->subMinutes($position),
                ]);
                $this->addVotes($recommendation, 6 - $position);
                $requests->push($recommendation);
            }
        }

        foreach (['pending', 'published', 'passed', 'already_seen', 'hidden', 'withdrawn'] as $status) {
            Recommendation::factory()->create([
                'creator_id' => $first->id,
                'title' => "Excluded {$status}",
                'status' => $status,
            ]);
        }
        Recommendation::factory()->create([
            'creator_id' => $first->id,
            'title' => 'Excluded moderated spam',
            'status' => 'approved',
            'moderation_status' => 'removed',
        ]);

        $projection = app(HomepageTopRequestsQuery::class);
        $result = $projection->get(collect([$first->id, $second->id, $empty->id]));

        $this->assertCount(3, $result->get($first->id));
        $this->assertCount(3, $result->get($second->id));
        $this->assertFalse($result->has($empty->id));
        $this->assertSame(
            ['Projection One request 1', 'Projection One request 2', 'Projection One request 3'],
            $result->get($first->id)->pluck('title')->all(),
        );
        $this->assertSame([5, 4, 3], $result->get($first->id)->pluck('user_picks_count')->map(fn ($value) => (int) $value)->all());
        $this->assertFalse($result->flatten()->contains(fn (Recommendation $request) => str_starts_with($request->title, 'Excluded')));

        $sql = strtolower($projection->builder([$first->id, $second->id])->toSql());
        $this->assertStringContainsString('row_number() over', $sql);
        $this->assertMatchesRegularExpression(
            '/row_number\(\) over .*user_picks_count.* from \(select .* as ["`]user_picks_count["`]/s',
            $sql,
            'The window must read the aggregate alias from a nested derived table, not define and order by it in the same SQL scope.',
        );
        $this->assertGreaterThanOrEqual(2, substr_count($sql, 'from ('));

        $queryCount = 0;
        DB::listen(function () use (&$queryCount): void {
            $queryCount++;
        });
        $this->get('/')->assertOk()->assertSee('Projection Empty');
        $this->assertLessThanOrEqual(12, $queryCount);
    }

    public function test_homepage_top_requests_only_show_votable_recommendations(): void
    {
        $creator = Creator::factory()->create([
            'display_name' => 'Votable Request Creator',
            'slug' => 'votable-request-creator',
        ]);

        $approvedRequest = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Open approved request',
            'status' => 'approved',
        ]);
        $publishedRequest = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Published finished request',
            'status' => 'published',
        ]);
        $recordedRequest = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Recorded finished request',
            'status' => 'recorded',
        ]);
        $scheduledRequest = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Scheduled finished request',
            'status' => 'scheduled',
        ]);
        $passedRequest = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Passed finished request',
            'status' => 'passed',
        ]);

        $this->addVotes($approvedRequest, 1);
        $this->addVotes($publishedRequest, 5);
        $this->addVotes($recordedRequest, 4);
        $this->addVotes($scheduledRequest, 3);
        $this->addVotes($passedRequest, 2);

        $this->get('/')
            ->assertOk()
            ->assertSee('Open approved request')
            ->assertDontSee('Published finished request')
            ->assertDontSee('Recorded finished request')
            ->assertDontSee('Scheduled finished request')
            ->assertDontSee('Passed finished request')
            ->assertSee('>5</strong> requests', false)
            ->assertSee('>1</strong> published', false);
    }

    public function test_homepage_creator_cards_show_published_recommendation_counts(): void
    {
        $publishedCreator = Creator::factory()->create([
            'display_name' => 'Published Stats Creator',
            'slug' => 'published-stats-creator',
        ]);
        $unpublishedCreator = Creator::factory()->create([
            'display_name' => 'No Published Creator',
            'slug' => 'no-published-creator',
        ]);

        $votedRequest = Recommendation::factory()->create([
            'creator_id' => $publishedCreator->id,
            'title' => 'Voted active request',
            'status' => 'approved',
        ]);
        Recommendation::factory()->create([
            'creator_id' => $publishedCreator->id,
            'title' => 'Published request',
            'status' => 'published',
        ]);
        Recommendation::factory()->create([
            'creator_id' => $publishedCreator->id,
            'title' => 'Recorded request is not published',
            'status' => 'recorded',
        ]);
        Recommendation::factory()->create([
            'creator_id' => $unpublishedCreator->id,
            'title' => 'Only active request',
            'status' => 'approved',
        ]);

        $this->addVotes($votedRequest, 1);

        $this->get('/')
            ->assertOk()
            ->assertSee('Published Stats Creator')
            ->assertSee('>1</strong> vote', false)
            ->assertSee('>3</strong> requests', false)
            ->assertSee('>1</strong> published', false)
            ->assertSee('No Published Creator')
            ->assertSee('>1</strong> request', false)
            ->assertSee('>0</strong> published', false);
    }

    public function test_homepage_hides_the_top_requests_panel_when_there_are_no_public_open_requests(): void
    {
        Creator::factory()->create([
            'display_name' => 'Creator Without Requests',
            'slug' => 'creator-without-requests',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Top requests')
            ->assertDontSee('No open requests yet');
    }

    public function test_pending_moderated_recommendations_never_leak_into_homepage_tiles_or_totals(): void
    {
        $creator = Creator::factory()->moderated()->create([
            'display_name' => 'Moderated Creator',
            'slug' => 'moderated-creator',
        ]);
        $approved = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Approved public request',
            'status' => 'approved',
        ]);
        $pending = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Private pending request',
            'status' => 'pending',
        ]);
        $this->addVotes($approved, 1);
        $this->addVotes($pending, 5);

        $this->get('/')
            ->assertOk()
            ->assertSee('Approved public request')
            ->assertDontSee('Private pending request')
            ->assertSee('>1</strong> vote', false)
            ->assertSee('>1</strong> request', false);

        $approved->update(['status' => 'hidden']);
        $pending->update(['status' => 'approved']);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Approved public request')
            ->assertSee('Private pending request')
            ->assertSee('>5</strong> votes', false)
            ->assertSee('>1</strong> request', false);
    }

    public function test_homepage_creator_cards_show_description_previews_with_fallbacks(): void
    {
        Creator::factory()->create([
            'display_name' => 'Creator With Bio',
            'youtube_channel_title' => 'Creator With Bio',
            'bio' => 'A focused creator biography for the discovery card.',
            'submission_instructions' => 'This should not be shown when a bio exists.',
        ]);
        Creator::factory()->create([
            'display_name' => 'Creator With Instructions',
            'bio' => null,
            'submission_instructions' => 'Suggest thoughtful documentaries and interviews.',
        ]);
        Creator::factory()->create([
            'display_name' => 'Creator Without Details',
            'bio' => null,
            'submission_instructions' => null,
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('A focused creator biography for the discovery card.')
            ->assertDontSee('This should not be shown when a bio exists.')
            ->assertSee('Suggest thoughtful documentaries and interviews.')
            ->assertSee("Help guide this creator's journey.")
            ->assertSee('line-clamp-2 text-sm leading-5', false);
    }

    public function test_search_filters_creators_and_changes_the_results_heading(): void
    {
        Creator::factory()->create([
            'display_name' => 'Vocals with Vanessa',
            'slug' => 'vocals-with-vanessa',
            'youtube_channel_title' => 'Vanessa Vocal Studio',
            'youtube_channel_url' => 'https://www.youtube.com/channel/UCVANESSADEMOCHANNEL01',
        ]);
        Creator::factory()->create([
            'display_name' => 'Movie Night Mike',
            'slug' => 'movie-night-mike',
        ]);

        $this->get('/?q=Vocal+Studio')
            ->assertOk()
            ->assertSee('Search results')
            ->assertSee('Vocals with Vanessa')
            ->assertDontSee('Movie Night Mike')
            ->assertSee('value="Vocal Studio"', false);
    }

    public function test_search_displays_an_empty_state_when_no_creators_match(): void
    {
        Creator::factory()->create(['display_name' => 'Existing Creator']);

        $this->get('/?q=missing')
            ->assertOk()
            ->assertSee('Search results')
            ->assertSee('No creators found.')
            ->assertDontSee('Existing Creator');
    }

    public function test_homepage_search_uses_the_global_creator_and_recommendation_search(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('action="'.route('search.index').'"', false)
            ->assertSee('Search creators, artists, songs, or topics...', false)
            ->assertSee('minlength="2"', false);
    }

    public function test_global_search_groups_active_topic_and_published_matches_by_creator(): void
    {
        $firstCreator = Creator::factory()->create(['display_name' => 'First Journey', 'slug' => 'first-journey']);
        $secondCreator = Creator::factory()->create(['display_name' => 'Second Journey', 'slug' => 'second-journey']);

        $active = Recommendation::factory()->create([
            'creator_id' => $firstCreator->id,
            'title' => 'Determinado live performance',
            'artist' => 'Kat Determinado',
            'status' => 'approved',
        ]);
        $topic = Recommendation::factory()->create([
            'creator_id' => $firstCreator->id,
            'recommendation_type' => 'topic',
            'youtube_url' => null,
            'youtube_video_id' => null,
            'title' => 'The story behind Determinado',
            'description' => 'Explore the song and its cultural context.',
            'status' => 'recorded',
        ]);
        $published = Recommendation::factory()->create([
            'creator_id' => $secondCreator->id,
            'title' => 'Original request title',
            'published_title' => 'Determinado reaction and analysis',
            'status' => 'published',
        ]);

        $response = $this->get(route('search.index', ['q' => 'Determinado']));

        $response
            ->assertOk()
            ->assertSee('Results for “Determinado”')
            ->assertSee('First Journey')
            ->assertSee('Second Journey')
            ->assertSee('2 matching requests')
            ->assertSee('Determinado live performance')
            ->assertSee('The story behind Determinado')
            ->assertSee('Recorded')
            ->assertSee('Topic')
            ->assertSee('Determinado reaction and analysis')
            ->assertSee('Published')
            ->assertSee(route('creator.queue', ['creator' => $firstCreator, 'q' => 'Determinado']).'#recommendation-'.$active->id, false)
            ->assertSee(route('creators.published', $secondCreator).'#recommendation-'.$published->id, false);

        $this->assertLessThan(
            strpos($response->getContent(), 'The story behind Determinado'),
            strpos($response->getContent(), 'Determinado live performance'),
        );
        $this->assertNotNull($topic);
    }

    public function test_global_search_supports_creator_only_matches_and_excludes_private_content(): void
    {
        $creatorMatch = Creator::factory()->create([
            'display_name' => 'Culture Explorer',
            'slug' => 'culture-explorer',
            'bio' => 'Deep dives into maritime history.',
        ]);
        $privateCreator = Creator::factory()->create(['display_name' => 'Private Journey', 'status' => 'inactive']);

        foreach (['pending', 'hidden', 'withdrawn'] as $status) {
            Recommendation::factory()->create([
                'creator_id' => $creatorMatch->id,
                'title' => 'Secret Maritime Match '.$status,
                'status' => $status,
            ]);
        }
        Recommendation::factory()->create([
            'creator_id' => $privateCreator->id,
            'title' => 'Maritime private creator match',
            'status' => 'approved',
        ]);

        $this->get(route('search.index', ['q' => 'maritime']))
            ->assertOk()
            ->assertSee('Culture Explorer')
            ->assertSee('Creator match')
            ->assertDontSee('Secret Maritime Match')
            ->assertDontSee('Private Journey');

        $this->get(route('search.index', ['q' => 'x']))
            ->assertOk()
            ->assertSee('Enter at least 2 characters.');
    }

    public function test_global_search_paginates_creator_groups_twelve_at_a_time(): void
    {
        foreach (range(1, 13) as $number) {
            Creator::factory()->create([
                'display_name' => sprintf('Needle Creator %02d', $number),
                'slug' => sprintf('needle-creator-%02d', $number),
            ]);
        }

        $this->get(route('search.index', ['q' => 'Needle']))
            ->assertOk()
            ->assertSee('Needle Creator 01')
            ->assertSee('Needle Creator 12')
            ->assertDontSee('Needle Creator 13');

        $this->get(route('search.index', ['q' => 'Needle', 'page' => 2]))
            ->assertOk()
            ->assertSee('Needle Creator 13')
            ->assertDontSee('Needle Creator 01')
            ->assertSee('q=Needle', false);
    }

    public function test_homepage_paginates_twelve_creators_per_page(): void
    {
        foreach (range(1, 13) as $number) {
            Creator::factory()->create([
                'display_name' => sprintf('Creator %02d', $number),
                'slug' => sprintf('creator-%02d', $number),
            ]);
        }

        $this->get('/')
            ->assertOk()
            ->assertSee('Creator 01')
            ->assertSee('Creator 12')
            ->assertDontSee('Creator 13');

        $this->get('/?page=2')
            ->assertOk()
            ->assertSee('Creator 13')
            ->assertDontSee('Creator 01');
    }

    public function test_inactive_creators_are_excluded_from_discovery_and_search(): void
    {
        Creator::factory()->create([
            'display_name' => 'Active Creator',
            'slug' => 'active-creator',
        ]);
        Creator::factory()->create([
            'display_name' => 'Inactive Creator',
            'slug' => 'inactive-creator',
            'status' => 'inactive',
            'deactivated_at' => now(),
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Active Creator')
            ->assertDontSee('Inactive Creator');

        $this->get('/?q=Inactive')
            ->assertOk()
            ->assertSee('No creators found.')
            ->assertDontSee('Inactive Creator');
    }

    public function test_soft_deleted_creators_are_excluded_from_public_discovery_and_routes(): void
    {
        $activeCreator = Creator::factory()->create([
            'display_name' => 'Active Creator',
            'slug' => 'active-creator',
        ]);
        $softDeletedCreator = Creator::factory()->create([
            'display_name' => 'Vitamin B Reacts',
            'slug' => 'vitamin-b-reacts',
        ]);
        Recommendation::factory()->create([
            'creator_id' => $softDeletedCreator->id,
            'title' => 'Preserved request',
            'status' => 'approved',
        ]);
        $softDeletedCreator->delete();

        $this->get('/')
            ->assertOk()
            ->assertSee('>1</dd>', false)
            ->assertSee('Active Creator')
            ->assertDontSee('Vitamin B Reacts')
            ->assertSee('Add Creator Account');

        $this->get('/?q=Vitamin+B')
            ->assertOk()
            ->assertSee('No creators found.')
            ->assertDontSee('Vitamin B Reacts')
            ->assertDontSee('Add Creator Account');

        $this->get(route('creator.queue', $softDeletedCreator->slug))
            ->assertNotFound();
        $this->get(route('creators.published', $softDeletedCreator->slug))
            ->assertNotFound();

        $this->assertDatabaseHas('recommendations', [
            'creator_id' => $softDeletedCreator->id,
            'title' => 'Preserved request',
        ]);
    }

    private function addVotes(Recommendation $recommendation, int $count): void
    {
        User::factory()
            ->count($count)
            ->create()
            ->each(fn (User $user) => UserPick::factory()->create([
                'user_id' => $user->id,
                'creator_id' => $recommendation->creator_id,
                'recommendation_id' => $recommendation->id,
            ]));
    }
}
