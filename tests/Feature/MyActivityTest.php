<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class MyActivityTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_redirected_to_login(): void
    {
        $this->get('/my-activity')->assertRedirect(route('login'));
    }

    public function test_page_only_shows_the_authenticated_guides_activity_and_correct_links(): void
    {
        $guide = User::factory()->create();
        $otherGuide = User::factory()->create();
        $creator = Creator::factory()->create(['display_name' => 'Activity Creator']);

        $active = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Active vote title',
            'status' => 'approved',
        ]);
        $published = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $guide->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'title' => 'Published suggestion title',
            'status' => 'published',
        ]);
        $hidden = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $guide->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'title' => 'Hidden private title',
            'status' => 'hidden',
        ]);
        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $otherGuide->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'title' => 'Another guide title',
            'status' => 'approved',
        ]);

        UserPick::factory()->create([
            'user_id' => $guide->id,
            'creator_id' => $creator->id,
            'recommendation_id' => $active->id,
            'vote_count' => 3,
        ]);
        UserPick::factory()->create([
            'user_id' => $guide->id,
            'creator_id' => $creator->id,
            'recommendation_id' => $published->id,
            'vote_count' => 7,
        ]);

        $this->actingAs($guide)->get(route('activity.index'))
            ->assertOk()
            ->assertSee('My Activity')
            ->assertSee('Activity Creator')
            ->assertSee('3 active votes')
            ->assertSee('Published suggestion title')
            ->assertSee('Published')
            ->assertSee(route('creator.queue', $creator).'#recommendation-'.$active->id, false)
            ->assertSee(route('creators.published', $creator).'#recommendation-'.$published->id, false)
            ->assertDontSee('Hidden private title')
            ->assertDontSee('Another guide title');
    }

    public function test_unfavorited_historical_activity_appears_and_filters_are_bookmarkable(): void
    {
        $guide = User::factory()->create();
        $creator = Creator::factory()->create(['display_name' => 'Past Creator']);
        $suggestion = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $guide->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'title' => 'Past published result',
            'status' => 'published',
        ]);
        UserPick::factory()->create([
            'user_id' => $guide->id,
            'creator_id' => $creator->id,
            'recommendation_id' => $suggestion->id,
            'vote_count' => 2,
        ]);

        $this->actingAs($guide)->get(route('activity.index', ['type' => 'published']))
            ->assertOk()
            ->assertSee('Past Creator')
            ->assertSee('Past published result')
            ->assertSee('x-data="{ open: true }"', false);

        $this->actingAs($guide)->get(route('activity.index', ['type' => 'votes']))
            ->assertOk()
            ->assertSee('No activity matches this filter.');
    }

    public function test_most_recent_creator_is_expanded_and_queries_do_not_scale_per_creator(): void
    {
        $guide = User::factory()->create();
        Carbon::setTestNow('2026-07-10 10:00:00');
        $older = Creator::factory()->create(['display_name' => 'Older Creator']);
        Recommendation::factory()->create([
            'creator_id' => $older->id,
            'submitted_by' => $guide->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
        ]);
        Carbon::setTestNow('2026-07-11 10:00:00');
        $newer = Creator::factory()->create(['display_name' => 'Newer Creator']);
        Recommendation::factory()->create([
            'creator_id' => $newer->id,
            'submitted_by' => $guide->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
        ]);

        DB::flushQueryLog();
        DB::enableQueryLog();
        $response = $this->actingAs($guide)->get(route('activity.index'))->assertOk();
        Carbon::setTestNow();

        $response->assertSeeInOrder(['Newer Creator', 'Older Creator']);
        $this->assertSame(1, substr_count($response->getContent(), 'x-data="{ open: true }"'));
        $this->assertLessThanOrEqual(12, collect(DB::getQueryLog())->count());
    }

    public function test_avatar_menu_contains_my_hub_first_and_preserves_account_actions(): void
    {
        $user = User::factory()->create();
        $response = $this->actingAs($user)->get(route('activity.index'))
            ->assertOk()
            ->assertSee('aria-controls="account-menu"', false)
            ->assertSee('href="'.route('dashboard').'" @click="accountOpen = false" role="menuitem"', false)
            ->assertSee('href="'.route('activity.index').'"', false);

        $accountMenu = substr($response->getContent(), strpos($response->getContent(), 'id="account-menu"'));

        $this->assertNotFalse(strpos($accountMenu, $user->publicName()));
        $this->assertTrue(strpos($accountMenu, 'My Hub') < strpos($accountMenu, 'My Activity'));
        $this->assertTrue(strpos($accountMenu, 'My Activity') < strpos($accountMenu, 'Profile'));
        $this->assertTrue(strpos($accountMenu, 'Profile') < strpos($accountMenu, 'Theme'));
        $this->assertTrue(strpos($accountMenu, 'Theme') < strpos($accountMenu, 'Log out'));
        $this->assertStringContainsString('class="border-t border-slate-100 pt-2 dark:border-slate-800"', $accountMenu);
    }

    public function test_my_hub_dropdown_item_uses_the_dashboard_active_state(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk();

        $accountMenu = substr($response->getContent(), strpos($response->getContent(), 'id="account-menu"'));

        $this->assertMatchesRegularExpression(
            '/href="'.preg_quote(route('dashboard'), '/').'"[^>]*class="[^"]*bg-indigo-50 text-indigo-700[^"]*"/',
            $accountMenu,
        );
    }

    public function test_page_has_one_primary_heading_and_body_begins_with_the_subtitle(): void
    {
        $response = $this->actingAs(User::factory()->create())
            ->get(route('activity.index'))
            ->assertOk()
            ->assertSee('My Guide Activity')
            ->assertSee('See what you’ve suggested and where your votes are currently allocated.')
            ->assertSeeInOrder([
                'See what you’ve suggested and where your votes are currently allocated.',
                'Activity filters',
            ]);

        $this->assertSame(1, substr_count($response->getContent(), '<h1'));
        $this->assertMatchesRegularExpression('/<h1[^>]*>\s*My Activity\s*<\/h1>/', $response->getContent());
        $this->assertSame(1, substr_count($response->getContent(), '>My Guide Activity<'));
    }
}
