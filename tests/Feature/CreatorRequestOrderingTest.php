<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorOwner;
use App\Models\CreatorTag;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreatorRequestOrderingTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_defaults_to_most_voted_with_public_page_tie_breaking(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $olderHigh = $this->request($creator, 'Older tied request', now()->subDays(4));
        $newerHigh = $this->request($creator, 'Newer tied request', now()->subDays(2));
        $low = $this->request($creator, 'Lower voted request', now()->subDays(5));
        $zero = $this->request($creator, 'Zero vote request', now()->subDay());
        $this->giveVotes($olderHigh, 8);
        $this->giveVotes($newerHigh, 8);
        $this->giveVotes($low, 2);

        $dashboard = $this->actingAs($owner)
            ->get(route('creators.recommendations.index', $creator))
            ->assertOk()
            ->assertSee('value="most_voted" selected', false)
            ->assertSee('Most Voted')
            ->assertSeeInOrder([$olderHigh->title, $newerHigh->title, $low->title, $zero->title]);

        $public = $this->get(route('creator.queue', $creator))->assertOk();

        $this->assertSame('most_voted', $dashboard->viewData('filters')['sort']);
        $this->assertSame(
            $public->viewData('recommendations')->getCollection()->pluck('id')->all(),
            $dashboard->viewData('recommendations')->getCollection()->pluck('id')->all(),
        );
    }

    public function test_explicit_most_voted_and_invalid_sort_both_use_the_canonical_ranking(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $low = $this->request($creator, 'Low request', now()->subDay());
        $high = $this->request($creator, 'High request', now());
        $this->giveVotes($low, 1);
        $this->giveVotes($high, 5);

        foreach (['most_voted', 'not-a-real-sort'] as $sort) {
            $response = $this->actingAs($owner)->get(route('creators.recommendations.index', [
                'creator' => $creator,
                'sort' => $sort,
            ]));

            $response
                ->assertOk()
                ->assertSee('value="most_voted" selected', false)
                ->assertSeeInOrder([$high->title, $low->title]);
            $this->assertSame('most_voted', $response->viewData('filters')['sort']);
        }
    }

    public function test_newest_and_oldest_remain_explicit_creator_choices(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $old = $this->request($creator, 'Old request', now()->subWeek());
        $new = $this->request($creator, 'New request', now());

        $this->actingAs($owner)->get(route('creators.recommendations.index', [
            'creator' => $creator,
            'sort' => 'newest',
        ]))->assertOk()->assertSeeInOrder([$new->title, $old->title]);

        $this->get(route('creators.recommendations.index', [
            'creator' => $creator,
            'sort' => 'oldest',
        ]))->assertOk()->assertSee('value="oldest" selected', false)->assertSeeInOrder([$old->title, $new->title]);
    }

    public function test_search_status_category_and_tag_filters_keep_most_voted_ordering(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $tag = CreatorTag::query()->create([
            'creator_id' => $creator->id,
            'name' => 'Deep Dive',
            'slug' => 'deep-dive',
        ]);
        $low = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Needle low',
            'status' => 'approved',
            'category' => 'music',
        ]);
        $high = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Needle high',
            'status' => 'approved',
            'category' => 'music',
        ]);
        $low->creatorTags()->attach($tag);
        $high->creatorTags()->attach($tag);
        $this->giveVotes($low, 1);
        $this->giveVotes($high, 4);

        foreach ([
            ['q' => 'Needle'],
            ['status' => 'approved'],
            ['category' => 'music'],
            ['tag' => 'deep-dive'],
        ] as $filter) {
            $response = $this->actingAs($owner)->get(route('creators.recommendations.index', [
                'creator' => $creator,
                ...$filter,
            ]));

            $response->assertOk()->assertSeeInOrder([$high->title, $low->title]);
            $this->assertSame('most_voted', $response->viewData('filters')['sort']);
        }
    }

    public function test_dashboard_uses_frozen_totals_and_excludes_soft_deleted_requests(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $closed = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Recorded with frozen votes',
            'status' => 'recorded',
            'vote_total_at_close' => 12,
        ]);
        $active = $this->request($creator, 'Active with current votes', now()->subDay());
        $deleted = $this->request($creator, 'Deleted high request', now()->subWeek());
        $this->giveVotes($active, 5);
        $this->giveVotes($deleted, 20);
        $deleted->delete();

        $response = $this->actingAs($owner)
            ->get(route('creators.recommendations.index', $creator))
            ->assertOk()
            ->assertSeeInOrder([$closed->title, $active->title])
            ->assertDontSee($deleted->title);

        $ranked = $response->viewData('recommendations')->getCollection();
        $this->assertSame(12, $ranked->firstWhere('id', $closed->id)->totalVotes());
        $this->assertSame(5, $ranked->firstWhere('id', $active->id)->totalVotes());
    }

    public function test_public_and_dashboard_orders_match_for_the_shared_visible_subset(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $visibleHigh = $this->request($creator, 'Visible high', now()->subDays(3));
        $visibleLow = $this->request($creator, 'Visible low', now()->subDays(2));
        $hidden = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Dashboard-only hidden request',
            'status' => 'hidden',
            'vote_total_at_close' => 50,
        ]);
        $this->giveVotes($visibleHigh, 7);
        $this->giveVotes($visibleLow, 2);

        $publicIds = $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->viewData('recommendations')
            ->getCollection()
            ->pluck('id');
        $dashboardIds = $this->actingAs($owner)
            ->get(route('creators.recommendations.index', $creator))
            ->assertOk()
            ->viewData('recommendations')
            ->getCollection()
            ->pluck('id');

        $this->assertSame($publicIds->all(), $dashboardIds->filter(fn (int $id): bool => $publicIds->contains($id))->values()->all());
        $this->assertTrue($dashboardIds->contains($hidden->id));
        $this->assertFalse($publicIds->contains($hidden->id));
    }

    public function test_pagination_and_reset_keep_most_voted_as_the_canonical_default(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        Recommendation::factory()->count(26)->create(['creator_id' => $creator->id, 'status' => 'approved']);

        $response = $this->actingAs($owner)->get(route('creators.recommendations.index', [
            'creator' => $creator,
            'sort' => 'most_voted',
        ]));

        $response
            ->assertOk()
            ->assertSee('sort=most_voted', false)
            ->assertSee('href="'.route('creators.recommendations.index', $creator).'"', false);
        $this->assertSame(25, $response->viewData('recommendations')->count());

        $reset = $this->actingAs($owner)->get(route('creators.recommendations.index', $creator))->assertOk();
        $this->assertSame('most_voted', $reset->viewData('filters')['sort']);
    }

    public function test_default_ranking_does_not_introduce_n_plus_one_queries(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        Recommendation::factory()->count(10)->create(['creator_id' => $creator->id, 'status' => 'approved']);
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->actingAs($owner)->get(route('creators.recommendations.index', $creator))->assertOk();

        $this->assertLessThanOrEqual(14, count(DB::getQueryLog()));
    }

    /** @return array{Creator, User} */
    private function creatorWithOwner(): array
    {
        $creator = Creator::factory()->create(['status' => 'active', 'deactivated_at' => null]);
        $owner = User::factory()->create();
        CreatorOwner::query()->create(['creator_id' => $creator->id, 'user_id' => $owner->id, 'role' => 'owner']);

        return [$creator, $owner];
    }

    private function request(Creator $creator, string $title, mixed $createdAt): Recommendation
    {
        return Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => $title,
            'status' => 'approved',
            'created_at' => $createdAt,
        ]);
    }

    private function giveVotes(Recommendation $recommendation, int $votes): void
    {
        UserPick::factory()->create([
            'creator_id' => $recommendation->creator_id,
            'recommendation_id' => $recommendation->id,
            'user_id' => User::factory()->create()->id,
            'vote_count' => $votes,
        ]);
    }
}
