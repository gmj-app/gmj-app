<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorTag;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreatorOverallRankTest extends TestCase
{
    use RefreshDatabase;

    public function test_filters_and_every_public_sort_preserve_overall_ranks_and_totals(): void
    {
        $creator = Creator::factory()->create(['status' => 'active']);
        $tag = CreatorTag::query()->create(['creator_id' => $creator->id, 'name' => 'Christmas', 'slug' => 'christmas']);
        $guides = User::factory()->count(12)->create();
        $expected = [];
        for ($rank = 1; $rank <= 12; $rank++) {
            $match = $rank % 2 === 0;
            $row = Recommendation::factory()->create([
                'creator_id' => $creator->id,
                'title' => ($match ? 'Needle Christmas ' : 'Other ').$rank,
                'category' => $match ? 'music' : 'documentary',
                'status' => $match ? 'recorded' : 'approved',
                'vote_total_at_close' => $match ? 13 - $rank : null,
                'created_at' => now()->subDays(13 - $rank),
            ]);
            foreach ($guides->take(13 - $rank) as $guide) {
                UserPick::factory()->create(['creator_id' => $creator->id, 'recommendation_id' => $row->id, 'user_id' => $guide->id, 'vote_count' => 1]);
            }
            if ($match) {
                $row->creatorTags()->attach($tag);
            }
            $expected[$row->id] = $rank;
        }

        foreach ([[], ['q' => 'Needle'], ['tag' => 'christmas'], ['category' => 'music'], ['status' => 'recorded'],
            ['tag' => 'christmas', 'q' => 'Needle'], ['category' => 'music', 'tag' => 'christmas'],
            ['status' => 'recorded', 'tag' => 'christmas'],
            ['q' => 'Needle', 'tag' => 'christmas', 'category' => 'music', 'status' => 'recorded']] as $filters) {
            foreach (['votes', 'newest', 'status', 'scheduled'] as $sort) {
                $response = $this->get(route('creator.queue', ['creator' => $creator, ...$filters, 'sort' => $sort]))->assertOk();
                $rows = $response->viewData('recommendations');
                $this->assertSame($filters === [] ? 12 : 6, $rows->total());
                $ranks = [];
                foreach ($rows as $row) {
                    $this->assertSame($expected[$row->id], (int) $row->overall_rank);
                    $this->assertSame(13 - $expected[$row->id], $row->totalVotes());
                    $ranks[] = (int) $row->overall_rank;
                }
                $ordered = $filters === [] ? range(1, 12) : [2, 4, 6, 8, 10, 12];
                if ($sort === 'status' && $filters === []) {
                    $ordered = [1, 3, 5, 7, 9, 11, 2, 4, 6, 8, 10, 12];
                }
                $this->assertSame($sort === 'newest' ? array_reverse($ordered) : $ordered, $ranks);
                if ($filters !== []) {
                    $response->assertSee('aria-label="Overall rank 2nd"', false)->assertSee('aria-label="Overall rank 12th"', false)
                        ->assertDontSee('aria-label="Overall rank 1st"', false);
                }
            }
        }
    }

    public function test_pagination_page_sizes_and_query_counts_remain_bounded(): void
    {
        $creator = Creator::factory()->create(['status' => 'active']);
        $tag = CreatorTag::query()->create(['creator_id' => $creator->id, 'name' => 'Christmas', 'slug' => 'christmas']);
        $expected = [];
        for ($rank = 1; $rank <= 160; $rank++) {
            $row = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'approved', 'created_at' => '2026-01-01 00:00:00']);
            if ($rank % 3 !== 0) {
                $row->creatorTags()->attach($tag);
                $expected[$row->id] = $rank;
            }
        }
        // Warm shared header caches before measuring page-size-dependent query counts.
        $this->get(route('creator.queue', $creator))->assertOk();
        $counts = [];
        foreach ([10, 25, 50, 100] as $perPage) {
            foreach ([1, 2] as $page) {
                DB::flushQueryLog();
                DB::enableQueryLog();
                $response = $this->get(route('creator.queue', ['creator' => $creator, 'tag' => 'christmas', 'per_page' => $perPage, 'page' => $page]))->assertOk();
                $queries = DB::getQueryLog();
                DB::disableQueryLog();
                $counts[] = count($queries);
                $rows = $response->viewData('recommendations');
                $this->assertSame(count($expected), $rows->total());
                $this->assertSame(array_slice($expected, ($page - 1) * $perPage, $perPage, true),
                    $rows->getCollection()->mapWithKeys(fn ($row) => [$row->id => (int) $row->overall_rank])->all());
                $sql = implode("\n", array_column($queries, 'query'));
                $this->assertStringContainsString('ROW_NUMBER() OVER', $sql);
                $this->assertStringContainsString('limit '.$perPage, $sql);
                $this->assertLessThanOrEqual(20, count($queries));
            }
        }
        $this->assertLessThanOrEqual(1, max($counts) - min($counts));
        fwrite(STDERR, '\noverall-rank filtered page queries='.implode(',', $counts)."\n");
    }

    public function test_eligibility_ties_binary_support_and_refresh_use_existing_rules(): void
    {
        $creator = Creator::factory()->create(['status' => 'active']);
        $older = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'approved', 'created_at' => '2026-01-01']);
        $tied = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'approved', 'created_at' => '2026-01-01']);
        $newer = Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'approved', 'created_at' => '2026-01-02']);
        foreach ([$older, $tied, $newer] as $row) {
            // Requester support counts once, like any other Guide support.
            UserPick::factory()->create(['creator_id' => $creator->id, 'recommendation_id' => $row->id, 'user_id' => $row->submitted_by, 'vote_count' => 1]);
        }
        foreach (['coming_soon', 'scheduled', 'recorded'] as $status) {
            Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => $status, 'vote_total_at_close' => 2, 'created_at' => '2025-01-01']);
        }
        foreach (['pending', 'published', 'merged_duplicate', 'hidden', 'withdrawn', 'already_seen', 'passed'] as $status) {
            Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => $status, 'vote_total_at_close' => 999]);
        }
        Recommendation::factory()->create(['creator_id' => $creator->id, 'status' => 'recorded', 'vote_total_at_close' => 999, 'deleted_at' => now()]);
        Recommendation::factory()->create(['status' => 'recorded', 'vote_total_at_close' => 999]);
        for ($load = 0; $load < 2; $load++) {
            $rows = $this->get(route('creator.queue', $creator))->assertOk()->viewData('recommendations');
            $this->assertSame(6, $rows->total());
            foreach ([$older, $tied, $newer] as $i => $row) {
                $this->assertSame(4 + $i, (int) $rows->firstWhere('id', $row->id)->overall_rank);
                $filtered = $this->get(route('creator.queue', ['creator' => $creator, 'q' => $row->youtube_url]))->assertOk()->viewData('recommendations');
                $this->assertSame(1, $filtered->total());
                $this->assertSame(4 + $i, (int) $filtered->first()->overall_rank);
            }
        }
        $support = UserPick::factory()->create(['creator_id' => $creator->id, 'recommendation_id' => $newer->id, 'vote_count' => 1]);
        $this->assertSame(4, (int) $this->get(route('creator.queue', ['creator' => $creator, 'q' => $newer->youtube_url]))->assertOk()->viewData('recommendations')->first()->overall_rank);
        $support->update(['released_at' => now()]);
        $this->assertSame(6, (int) $this->get(route('creator.queue', ['creator' => $creator, 'q' => $newer->youtube_url]))->assertOk()->viewData('recommendations')->first()->overall_rank);
    }
}
