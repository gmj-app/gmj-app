<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreatorRequestPaginationTest extends TestCase
{
    use RefreshDatabase;

    public function test_active_requests_default_to_ten_results_per_page(): void
    {
        $creator = $this->creatorWithActiveRequests(26);

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertViewHas('recommendations', fn (LengthAwarePaginator $requests) => $requests->perPage() === 10
                && $requests->count() === 10
                && $requests->total() === 26)
            ->assertSee('Showing <span class="font-medium">1</span> to <span class="font-medium">10</span> of <span class="font-medium">26</span> results', false);
    }

    #[DataProvider('allowedPageSizes')]
    public function test_each_allowed_page_size_controls_server_side_pagination(int $perPage): void
    {
        $creator = $this->creatorWithActiveRequests(120);

        $response = $this->get(route('creator.queue', ['creator' => $creator, 'per_page' => $perPage]))
            ->assertOk()
            ->assertViewHas('recommendations', fn (LengthAwarePaginator $requests) => $requests->perPage() === $perPage
                && $requests->count() === $perPage
                && $requests->lastPage() === (int) ceil(120 / $perPage));

        $this->assertSame(2, substr_count($response->getContent(), 'aria-expanded="true"'));
    }

    /** @return array<string, array{int}> */
    public static function allowedPageSizes(): array
    {
        return [
            'ten' => [10],
            'twenty five' => [25],
            'fifty' => [50],
            'one hundred' => [100],
        ];
    }

    #[DataProvider('invalidPageSizes')]
    public function test_invalid_page_sizes_fall_back_to_ten_without_reaching_paginate(mixed $invalid): void
    {
        $creator = $this->creatorWithActiveRequests(12);

        $this->get(route('creator.queue', ['creator' => $creator, 'per_page' => $invalid]))
            ->assertOk()
            ->assertViewHas('perPage', 10)
            ->assertViewHas('recommendations', fn (LengthAwarePaginator $requests) => $requests->perPage() === 10 && $requests->count() === 10);
    }

    /** @return array<string, array{mixed}> */
    public static function invalidPageSizes(): array
    {
        return [
            'zero' => [0],
            'negative' => [-10],
            'over maximum' => [101],
            'arbitrary string' => ['everything'],
        ];
    }

    public function test_authenticated_preference_applies_across_creators_and_valid_query_updates_it(): void
    {
        $guide = User::factory()->create();
        $firstCreator = $this->creatorWithActiveRequests(60);
        $secondCreator = $this->creatorWithActiveRequests(60);

        $this->actingAs($guide)
            ->get(route('creator.queue', ['creator' => $firstCreator, 'per_page' => 50]))
            ->assertOk()
            ->assertSessionHas("creator_requests_per_page.users.{$guide->id}", 50);

        $this->get(route('creator.queue', $secondCreator))
            ->assertOk()
            ->assertViewHas('perPage', 50)
            ->assertViewHas('recommendations', fn (LengthAwarePaginator $requests) => $requests->count() === 50);

        $this->get(route('creator.queue', ['creator' => $secondCreator, 'per_page' => 25]))
            ->assertOk()
            ->assertSessionHas("creator_requests_per_page.users.{$guide->id}", 25);
    }

    public function test_invalid_explicit_value_does_not_corrupt_a_saved_preference(): void
    {
        $guide = User::factory()->create();
        $creator = $this->creatorWithActiveRequests(60);

        $this->actingAs($guide)->get(route('creator.queue', ['creator' => $creator, 'per_page' => 50]))->assertOk();

        $this->get(route('creator.queue', ['creator' => $creator, 'per_page' => 'invalid']))
            ->assertOk()
            ->assertViewHas('perPage', 10)
            ->assertSessionHas("creator_requests_per_page.users.{$guide->id}", 50);

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertViewHas('perPage', 50);
    }

    public function test_preferences_are_isolated_between_authenticated_guides(): void
    {
        $firstGuide = User::factory()->create();
        $secondGuide = User::factory()->create();
        $creator = $this->creatorWithActiveRequests(60);

        $this->actingAs($firstGuide)->get(route('creator.queue', ['creator' => $creator, 'per_page' => 50]))->assertOk();

        $this->actingAs($secondGuide)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertViewHas('perPage', 10);
    }

    public function test_guest_preference_persists_in_the_session_and_never_requires_login(): void
    {
        $firstCreator = $this->creatorWithActiveRequests(30);
        $secondCreator = $this->creatorWithActiveRequests(30);

        $this->get(route('creator.queue', ['creator' => $firstCreator, 'per_page' => 25]))
            ->assertOk()
            ->assertSessionHas('creator_requests_per_page.guest', 25);

        $this->get(route('creator.queue', $secondCreator))
            ->assertOk()
            ->assertViewHas('perPage', 25);
    }

    public function test_selector_is_accessible_exact_and_drops_stale_page_while_preserving_filters(): void
    {
        $creator = $this->creatorWithActiveRequests(30);

        $response = $this->get(route('creator.queue', [
            'creator' => $creator,
            'q' => 'Request',
            'status' => 'approved',
            'sort' => 'newest',
            'per_page' => 25,
            'page' => 2,
        ]))->assertOk();

        $html = $response->getContent();
        $this->assertMatchesRegularExpression('/<select[^>]+id="requests-per-page"[^>]+name="per_page"[^>]+aria-label="Requests per page"[^>]+onchange="this\.form\.requestSubmit\(\)"[^>]*>.*?<option value="10".*?<option value="25" selected.*?<option value="50".*?<option value="100".*?<\/select>/s', $html);
        $this->assertSame(4, preg_match_all('/<option value="(?:10|25|50|100)"/', $html));
        $this->assertStringContainsString('<noscript>', $html);

        preg_match('/<form[^>]+data-request-per-page-form[^>]*>(.*?)<\/form>/s', $html, $form);
        $this->assertStringContainsString('name="q" value="Request"', $form[1]);
        $this->assertStringContainsString('name="status" value="approved"', $form[1]);
        $this->assertStringContainsString('name="sort" value="newest"', $form[1]);
        $this->assertStringNotContainsString('name="page"', $form[1]);
    }

    public function test_pagination_and_filter_controls_preserve_the_sanitized_page_size(): void
    {
        $creator = $this->creatorWithActiveRequests(40);

        $this->get(route('creator.queue', [
            'creator' => $creator,
            'q' => 'Request',
            'status' => 'approved',
            'sort' => 'newest',
            'per_page' => 25,
        ]))
            ->assertOk()
            ->assertSee('name="per_page" value="25"', false)
            ->assertSee('q=Request&amp;status=approved&amp;sort=newest&amp;per_page=25&amp;page=2', false)
            ->assertSee(route('creator.queue', ['creator' => $creator, 'per_page' => 25]), false);
    }

    public function test_page_size_affects_only_active_requests_and_empty_results_are_safe(): void
    {
        $creator = $this->creatorWithActiveRequests(12);
        Recommendation::factory()->count(6)->create([
            'creator_id' => $creator->id,
            'status' => 'published',
        ]);

        $this->get(route('creator.queue', ['creator' => $creator, 'per_page' => 100]))
            ->assertOk()
            ->assertViewHas('recommendations', fn (LengthAwarePaginator $requests) => $requests->count() === 12)
            ->assertViewHas('recentPublishedRecommendations', fn ($requests) => $requests->count() === 6)
            ->assertViewHas('hasMorePublishedRecommendations', false);

        $this->get(route('creator.queue', ['creator' => $creator, 'q' => 'no-match', 'per_page' => 100]))
            ->assertOk()
            ->assertViewHas('recommendations', fn (LengthAwarePaginator $requests) => $requests->isEmpty())
            ->assertSee('Showing 0 results');
    }

    public function test_query_count_remains_bounded_across_all_page_sizes(): void
    {
        $creator = $this->creatorWithActiveRequests(120);
        $counts = [];

        foreach ([10, 25, 50, 100] as $perPage) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $response = $this->get(route('creator.queue', ['creator' => $creator, 'per_page' => $perPage]))->assertOk();
            $counts[$perPage] = count(DB::getQueryLog());
            DB::disableQueryLog();

            $response->assertDontSee('<iframe', false);
        }

        $this->assertLessThanOrEqual(2, max($counts) - min($counts), json_encode($counts, JSON_THROW_ON_ERROR));
        $this->assertLessThan(30, max($counts), json_encode($counts, JSON_THROW_ON_ERROR));
    }

    public function test_authenticated_vote_state_query_count_is_bounded_at_ten_and_one_hundred_rows(): void
    {
        $creator = $this->creatorWithActiveRequests(120);
        $guide = User::factory()->create();
        $counts = [];

        foreach ([10, 100] as $perPage) {
            DB::flushQueryLog();
            DB::enableQueryLog();

            $response = $this->actingAs($guide)
                ->get(route('creator.queue', ['creator' => $creator, 'per_page' => $perPage]))
                ->assertOk();
            $counts[$perPage] = count(DB::getQueryLog());
            DB::disableQueryLog();

            $this->assertSame($perPage, substr_count($response->getContent(), 'data-collapsed-vote-button'));
        }

        fwrite(STDERR, sprintf(
            "\nauthenticated-vote-state queries_10=%d queries_100=%d\n",
            $counts[10],
            $counts[100],
        ));

        $this->assertLessThanOrEqual(2, $counts[100] - $counts[10], json_encode($counts, JSON_THROW_ON_ERROR));
        $this->assertLessThan(32, max($counts), json_encode($counts, JSON_THROW_ON_ERROR));
    }

    private function creatorWithActiveRequests(int $count): Creator
    {
        $creator = Creator::factory()->create();

        Recommendation::factory()->count($count)->sequence(fn ($sequence) => [
            'creator_id' => $creator->id,
            'status' => 'approved',
            'title' => 'Request '.($sequence->index + 1),
            'created_at' => now()->subSeconds($sequence->index),
        ])->create();

        return $creator;
    }
}
