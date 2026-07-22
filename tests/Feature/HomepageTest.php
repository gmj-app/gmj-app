<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use App\Services\PlatformStatisticsService;
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
            ->assertSee('data-header-utility-cluster', false)
            ->assertSee('data-header-notification-control class="relative flex shrink-0 items-center"', false)
            ->assertSee('data-header-account-control class="relative flex shrink-0 items-center"', false)
            ->assertSee('data-header-circle-trigger="notifications"', false)
            ->assertSee('data-header-circle-trigger="account"', false)
            ->assertSee('inline-flex size-11 shrink-0 items-center justify-center', false)
            ->assertSee('src="https://example.com/avatar.jpg"', false)
            ->assertSee('alt="Public Guide avatar"', false)
            ->assertSee('data-header-avatar-image class="block h-full w-full rounded-full object-cover" width="44" height="44"', false)
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

        $fallbackResponse = $this->actingAs(User::factory()->create(['avatar_url' => null]))
            ->get('/')
            ->assertOk()
            ->assertSee('data-header-avatar-fallback class="inline-flex h-full w-full items-center justify-center rounded-full"', false)
            ->assertDontSee('data-notification-unread-badge', false);

        $this->assertSame(2, substr_count($fallbackResponse->getContent(), 'data-header-circle-trigger='));
        $this->assertSame(2, substr_count($fallbackResponse->getContent(), 'size-11 shrink-0 items-center justify-center'));
    }

    public function test_header_shows_live_platform_stats_while_homepage_search_is_centered_independently(): void
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
            ->assertSee('data-public-header', false)
            ->assertSee('data-home-search-group', false)
            ->assertSee('max-w-[42rem]', false)
            ->assertSee('method="GET" action="'.route('search.index').'"', false)
            ->assertSee('name="q"', false)
            ->assertSee('placeholder="Search creators, artists, songs, or topics..."', false)
            ->assertSee('<button type="submit"', false)
            ->assertSee('Search')
            ->assertSee('data-platform-stats', false)
            ->assertSee('aria-label="Guide My Journey community statistics"', false)
            ->assertSee('hidden xl:inline-flex', false)
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
        $this->assertLessThan(
            strpos($response->getContent(), 'data-home-hero'),
            strpos($response->getContent(), 'data-platform-stats'),
            'Platform statistics must render in the header, before the homepage hero.',
        );
    }

    public function test_authenticated_header_orders_stats_before_notifications_and_avatar(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get('/')->assertOk();
        $content = $response->getContent();

        $this->assertLessThan(strpos($content, 'aria-controls="notification-dropdown"'), strpos($content, 'data-platform-stats'));
        $this->assertLessThan(strpos($content, 'aria-label="Open account menu"'), strpos($content, 'aria-controls="notification-dropdown"'));
        $response
            ->assertSee('md:grid-cols-[1fr_auto_1fr]', false)
            ->assertSee('gap-2.5', false)
            ->assertSee('data-header-utility-cluster class="flex shrink-0 items-center justify-end gap-2.5"', false)
            ->assertSee('class="absolute right-0 mt-3 w-64', false)
            ->assertSee('notification-dropdown')
            ->assertSee('account-menu');
    }

    public function test_compact_platform_stats_format_large_counts_without_queries_or_interaction(): void
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $response = $this->blade(
            '<x-platform-stats :creator-count="$creatorCount" :guide-count="$guideCount" />',
            ['creatorCount' => 1000, 'guideCount' => 1000000],
        );

        $response
            ->assertSee('>1,000</dd>', false)
            ->assertSee('>1,000,000</dd>', false)
            ->assertSee('min-w-48', false)
            ->assertDontSee('<a ', false)
            ->assertDontSee('<button', false);

        $this->blade(
            '<x-platform-stats :creator-count="1" :guide-count="1" />',
        )->assertSee('Creator')->assertSee('Guide')->assertDontSee('Creators')->assertDontSee('Guides');
        $this->assertSame([], DB::getQueryLog());
    }

    public function test_shared_platform_statistics_are_cached_across_public_pages(): void
    {
        Creator::factory()->create();
        User::factory()->create();
        app(PlatformStatisticsService::class)->forget();
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->get('/about')->assertOk();
        $this->get('/faq')->assertOk();

        $countQueries = collect(DB::getQueryLog())->filter(fn (array $query): bool => str_contains(strtolower($query['query']), 'count('));
        $this->assertCount(2, $countQueries, 'The shared header counts should query once each and then use the cache.');

        Creator::factory()->create();
        $this->assertSame(2, app(PlatformStatisticsService::class)->publicCounts()['creatorCount']);
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
            ->assertSee('grid-cols-1 gap-x-4 gap-y-4 md:grid-cols-2 xl:grid-cols-3', false)
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
            ->assertDontSee('Top requests')
            ->assertDontSee('Visible top request')
            ->assertSee(route('creator.queue', $popularCreator), false)
            ->assertSee('aria-label="View Popular Creator"', false)
            ->assertDontSee('aria-label="View Popular Creator\'s journey"', false)
            ->assertSee('src="https://example.com/popular-creator-banner.jpg"', false)
            ->assertSee('loading="lazy"', false)
            ->assertSee('bg-gradient-to-br from-indigo-600 via-sky-600 to-violet-600', false)
            ->assertSee('ring-4 ring-white dark:ring-slate-900', false)
            ->assertSee('min-h-[15.5rem]', false)
            ->assertSee('h-24 shrink-0', false)
            ->assertSee('src="https://example.com/popular-creator.jpg"', false)
            ->assertSee('alt="Popular Creator avatar"', false)
            ->assertSee('width="64"', false)
            ->assertSee('height="64"', false)
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

    public function test_requester_badge_is_removed_from_homepage_but_remains_in_search_results(): void
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

        $this->actingAs($guide)->get(route('home'))->assertOk()->assertDontSee('You requested')->assertDontSee('Historical Badge Needle');
        $this->actingAs($guide)->get(route('search.index', ['q' => 'Badge Needle']))->assertOk()->assertSee('You requested');
        $this->actingAs(User::factory()->create())->get(route('search.index', ['q' => 'Badge Needle']))->assertOk()->assertDontSee('You requested');
    }

    public function test_homepage_creator_cards_do_not_render_request_detail_content(): void
    {
        $creator = Creator::factory()->create([
            'display_name' => 'Three Request Creator',
            'slug' => 'three-request-creator',
        ]);

        $request = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Homepage request title must stay hidden',
            'status' => 'approved',
        ]);

        $this->addVotes($request, 3);

        $this->get('/')
            ->assertOk()
            ->assertSee('Three Request Creator')
            ->assertDontSee('Top requests')
            ->assertDontSee('Homepage request title must stay hidden')
            ->assertDontSee('You requested')
            ->assertDontSee('requested-by-you-badge');
    }

    public function test_homepage_does_not_issue_a_top_request_query_or_introduce_n_plus_one_queries(): void
    {
        $creators = Creator::factory()->count(12)->create();
        foreach ($creators as $creator) {
            Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'approved']);
        }

        $queries = [];
        DB::listen(function ($query) use (&$queries): void {
            $queries[] = strtolower($query->sql);
        });

        $this->get('/')->assertOk()->assertSee($creators->first()->display_name);

        $this->assertLessThanOrEqual(10, count($queries));
        $this->assertFalse(collect($queries)->contains(fn (string $sql): bool => str_contains($sql, 'row_number() over')));
        $this->assertFalse(collect($queries)->contains(fn (string $sql): bool => str_contains($sql, 'ranked_requests')));
    }

    public function test_homepage_metrics_include_authoritative_request_and_published_counts_without_titles(): void
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
            ->assertDontSee('Open approved request')
            ->assertDontSee('Published finished request')
            ->assertDontSee('Recorded finished request')
            ->assertDontSee('Scheduled finished request')
            ->assertDontSee('Passed finished request')
            ->assertSee('>5</strong> <span class="truncate">requests</span>', false)
            ->assertSee('>1</strong> <span class="truncate">published</span>', false);
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
            ->assertSee('>0</strong> <span class="truncate">followers</span>', false)
            ->assertSee('>3</strong> <span class="truncate">requests</span>', false)
            ->assertSee('>1</strong> <span class="truncate">published</span>', false)
            ->assertSee('No Published Creator')
            ->assertSee('>1</strong> <span class="truncate">request</span>', false)
            ->assertSee('>0</strong> <span class="truncate">published</span>', false);
    }

    public function test_homepage_never_renders_an_empty_top_requests_panel(): void
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
            ->assertDontSee('Approved public request')
            ->assertDontSee('Private pending request')
            ->assertSee('>0</strong> <span class="truncate">followers</span>', false)
            ->assertSee('>1</strong> <span class="truncate">request</span>', false);

        $approved->update(['status' => 'hidden']);
        $pending->update(['status' => 'approved']);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Approved public request')
            ->assertDontSee('Private pending request')
            ->assertSee('>0</strong> <span class="truncate">followers</span>', false)
            ->assertSee('>1</strong> <span class="truncate">request</span>', false);
    }

    public function test_homepage_creator_cards_show_only_current_valid_followers_with_correct_pluralization(): void
    {
        $zero = Creator::factory()->create(['display_name' => 'Zero Followers Creator']);
        $one = Creator::factory()->create(['display_name' => 'One Follower Creator']);
        $many = Creator::factory()->create(['display_name' => 'Many Followers Creator']);

        CreatorFavorite::query()->create([
            'creator_id' => $one->id,
            'user_id' => User::factory()->create()->id,
        ]);

        foreach (User::factory()->count(2)->create() as $guide) {
            CreatorFavorite::query()->create(['creator_id' => $many->id, 'user_id' => $guide->id]);
        }

        CreatorFavorite::query()->create([
            'creator_id' => $many->id,
            'user_id' => User::factory()->create()->id,
            'released_at' => now(),
            'release_reason' => 'creator_unavailable',
        ]);

        $deletedGuide = User::factory()->create();
        CreatorFavorite::query()->create(['creator_id' => $many->id, 'user_id' => $deletedGuide->id]);
        $deletedGuide->delete();

        $this->get('/')
            ->assertOk()
            ->assertSee('>0</strong> <span class="truncate">followers</span>', false)
            ->assertSee('>1</strong> <span class="truncate">follower</span>', false)
            ->assertSee('>2</strong> <span class="truncate">followers</span>', false)
            ->assertDontSee('</strong> vote', false);
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
            ->assertSee('line-clamp-2 text-sm leading-5', false)
            ->assertSee('lg:line-clamp-1', false);
    }

    public function test_popular_creator_grid_uses_the_canonical_container_and_three_desktop_columns(): void
    {
        Creator::factory()->count(4)->create();
        Creator::factory()->create([
            'display_name' => 'A deliberately long creator display name that must truncate safely',
            'bio' => str_repeat('A long biography remains available while its visual preview is clamped. ', 4),
        ]);

        $response = $this->get('/')->assertOk();

        $response
            ->assertSee('data-popular-creators-container class="mx-auto max-w-5xl"', false)
            ->assertSee('data-popular-creators-grid', false)
            ->assertSee('grid-cols-1 gap-x-4 gap-y-4 md:grid-cols-2 xl:grid-cols-3', false)
            ->assertDontSee('max-w-[100rem]', false)
            ->assertDontSee('xl:grid-cols-4', false)
            ->assertDontSee('2xl:grid-cols-5', false)
            ->assertDontSee('col-span-', false)
            ->assertDontSee('gridTemplateColumns', false)
            ->assertSee('data-home-grid-tile', false)
            ->assertSee('data-creator-card', false)
            ->assertSee('data-add-creator-card', false)
            ->assertSee('min-h-[15.5rem]', false)
            ->assertSee('2xl:min-h-56', false)
            ->assertSee('h-24 shrink-0', false)
            ->assertSee('2xl:h-20', false)
            ->assertSee('2xl:h-14 2xl:w-14', false)
            ->assertSee('title="A deliberately long creator display name that must truncate safely"', false)
            ->assertSee('line-clamp-2', false)
            ->assertSee('lg:line-clamp-1', false)
            ->assertSee('flex flex-wrap items-center justify-center', false)
            ->assertSee('2xl:grid 2xl:grid-cols-3', false)
            ->assertSee('2xl:hidden', false)
            ->assertSee('tabular-nums', false)
            ->assertSee('dark:border-slate-800', false)
            ->assertSee('dark:bg-slate-900', false);

        $this->assertSame(5, substr_count($response->getContent(), 'data-creator-card'));
        $this->assertSame(6, substr_count($response->getContent(), 'data-home-grid-tile'));
        $this->assertSame(6, substr_count($response->getContent(), 'min-h-[15.5rem]'));
        $this->assertSame(6, substr_count($response->getContent(), '2xl:min-h-56'));
    }

    public function test_empty_and_single_creator_homepage_rows_render_without_stale_request_content(): void
    {
        $this->get('/')
            ->assertOk()
            ->assertSee('data-popular-creators-grid', false)
            ->assertSee('Add Creator Account')
            ->assertDontSee('Top requests');

        Creator::factory()->create(['display_name' => 'Only Creator']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Only Creator')
            ->assertSee('Add Creator Account')
            ->assertDontSee('Top requests');
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
