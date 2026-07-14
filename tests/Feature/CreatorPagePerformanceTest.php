<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CreatorPagePerformanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_creator_page_initial_response_stays_bounded(): void
    {
        $creator = Creator::factory()->create(['slug' => 'performance-creator', 'status' => 'active']);
        $supporters = User::factory()->count(10)->create();

        Recommendation::factory()->count(30)->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
        ])->each(function (Recommendation $recommendation) use ($creator, $supporters): void {
            $supporters->each(fn (User $user) => UserPick::factory()->create([
                'creator_id' => $creator->id,
                'recommendation_id' => $recommendation->id,
                'user_id' => $user->id,
            ]));
        });

        $queries = 0;
        $queryMs = 0.0;
        DB::listen(function ($query) use (&$queries, &$queryMs): void {
            $queries++;
            $queryMs += $query->time;
        });
        $start = hrtime(true);
        $memory = memory_get_peak_usage(true);

        $response = $this->get(route('creator.queue', $creator));
        $elapsedMs = (hrtime(true) - $start) / 1_000_000;
        $html = $response->getContent();

        fwrite(STDERR, sprintf(
            "\ncreator-page queries=%d query_ms=%.2f response_ms=%.2f html_kb=%.1f memory_delta_mb=%.1f\n",
            $queries,
            $queryMs,
            $elapsedMs,
            strlen($html) / 1024,
            (memory_get_peak_usage(true) - $memory) / 1048576,
        ));

        $response->assertOk();
        $this->assertSame(25, substr_count($html, 'data-creator-request-row'));
        $this->assertLessThanOrEqual(20, $queries);
        $this->assertLessThan(350 * 1024, strlen($html));
        $this->assertStringNotContainsString('data-recommendation-expanded-card', $html);
        $this->assertStringContainsString('mqdefault.jpg', $html);
        $this->assertStringNotContainsString('hqdefault.jpg', $html);
    }

    public function test_expanded_details_are_loaded_separately_and_remain_personalized(): void
    {
        $creator = Creator::factory()->create(['status' => 'active']);
        $viewer = User::factory()->create();
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $viewer->id,
            'status' => 'approved',
            'title' => 'Deferred request details',
        ]);
        UserPick::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_id' => $recommendation->id,
            'user_id' => $viewer->id,
            'vote_count' => 2,
        ]);

        $this->actingAs($viewer)->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Deferred request details')
            ->assertDontSee('data-recommendation-expanded-card', false);

        $this->actingAs($viewer)
            ->get(route('requests.card-details', $recommendation), ['X-Requested-With' => 'XMLHttpRequest'])
            ->assertOk()
            ->assertSee('Deferred request details')
            ->assertSee('You requested')
            ->assertSee('data-current-user-votes="2"', false);
    }
}
