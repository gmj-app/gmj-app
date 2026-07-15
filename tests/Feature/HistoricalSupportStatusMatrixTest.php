<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use App\Services\RecommendationStatusTransitionService;
use App\Services\RequestSupportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class HistoricalSupportStatusMatrixTest extends TestCase
{
    use RefreshDatabase;

    #[DataProvider('closedStatuses')]
    public function test_every_closed_voting_status_preserves_quantity_and_supporter_identity(string $status): void
    {
        $creator = Creator::factory()->create();
        $actor = User::factory()->create();
        $guides = User::factory()->count(5)->create();
        $request = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
            'submitted_by' => $actor->id,
        ]);

        foreach ([2, 2, 2, 1, 1] as $index => $quantity) {
            UserPick::factory()->create([
                'creator_id' => $creator->id,
                'recommendation_id' => $request->id,
                'user_id' => $guides[$index]->id,
                'vote_count' => $quantity,
            ]);
        }

        app(RecommendationStatusTransitionService::class)->transition($request, $status, $actor);
        $request->refresh();
        $support = app(RequestSupportService::class);

        $this->assertSame(0, $support->activeVoteQuantity($request));
        $this->assertSame(8, $support->historicalVoteQuantity($request));
        $this->assertSame(5, $support->historicalSupporterCount($request));
        $this->assertSame(8, $support->displayVoteQuantity($request));
        $this->assertSame(5, $support->displaySupporterCount($request));
        $this->assertEqualsCanonicalizing($guides->pluck('id')->all(), $support->displaySupport($request)->pluck('user_id')->all());
    }

    public function test_moderation_invalidations_are_not_valid_historical_support(): void
    {
        $request = Recommendation::factory()->create(['status' => 'published', 'vote_total_at_close' => 4]);
        UserPick::factory()->create([
            'creator_id' => $request->creator_id,
            'recommendation_id' => $request->id,
            'vote_count' => 3,
            'released_at' => now(),
            'release_reason' => 'request_published',
        ]);
        UserPick::factory()->create([
            'creator_id' => $request->creator_id,
            'recommendation_id' => $request->id,
            'vote_count' => 1,
            'released_at' => now(),
            'release_reason' => 'request_removed',
        ]);

        $this->assertSame(3, app(RequestSupportService::class)->historicalVoteQuantity($request));
        $this->assertSame(1, app(RequestSupportService::class)->historicalSupporterCount($request));
    }

    public function test_closed_status_retries_and_progression_do_not_erase_history(): void
    {
        $actor = User::factory()->create();
        $request = Recommendation::factory()->create(['status' => 'approved']);
        UserPick::factory()->create([
            'creator_id' => $request->creator_id,
            'recommendation_id' => $request->id,
            'user_id' => User::factory(),
            'vote_count' => 8,
        ]);
        $transitions = app(RecommendationStatusTransitionService::class);

        foreach (['scheduled', 'scheduled', 'recorded', 'published'] as $status) {
            $request = $transitions->transition($request, $status, $actor)['recommendation'];
            $this->assertSame(8, app(RequestSupportService::class)->historicalVoteQuantity($request));
            $this->assertSame(1, app(RequestSupportService::class)->historicalSupporterCount($request));
        }
    }

    public function test_supporter_endpoint_uses_the_same_historical_scope_and_total(): void
    {
        $requester = User::factory()->create();
        $request = Recommendation::factory()->create([
            'status' => 'published',
            'submitted_by' => $requester->id,
            'vote_total_at_close' => 8,
            'supporter_count_at_close' => 5,
        ]);
        $guides = User::factory()->count(5)->create();
        foreach ([2, 2, 2, 1, 1] as $index => $quantity) {
            UserPick::factory()->create([
                'creator_id' => $request->creator_id,
                'recommendation_id' => $request->id,
                'user_id' => $guides[$index]->id,
                'vote_count' => $quantity,
                'released_at' => now(),
                'release_reason' => 'request_published',
            ]);
        }

        $this->getJson(route('requests.supporters', $request))
            ->assertOk()
            ->assertJsonPath('total', 5)
            ->assertJsonPath('next_page', null);
    }

    public function test_approved_card_and_endpoint_display_bounded_active_supporters_consistently(): void
    {
        $request = Recommendation::factory()->create(['status' => 'approved']);
        $guides = User::factory()->count(8)->create();
        foreach ([2, 2, 2, 2, 2, 2, 1, 1] as $index => $quantity) {
            UserPick::factory()->create([
                'creator_id' => $request->creator_id,
                'recommendation_id' => $request->id,
                'user_id' => $guides[$index]->id,
                'vote_count' => $quantity,
            ]);
        }

        $support = app(RequestSupportService::class);
        $this->assertSame('active', $support->displaySupportScope($request));
        $this->assertSame(14, $support->displayVoteQuantity($request));
        $this->assertSame(8, $support->displaySupporterCount($request, $request->submitted_by));
        $this->assertCount(6, $support->displaySupporterPreview($request, 6, $request->submitted_by));

        $this->get(route('requests.card-details', $request))
            ->assertOk()
            ->assertSee('data-supporter-preview-count="6"', false)
            ->assertSee('data-supporter-total="8"', false)
            ->assertSee('+2', false)
            ->assertDontSee('No votes yet.');
        $this->getJson(route('requests.supporters', $request))
            ->assertOk()
            ->assertJsonPath('total', 8);

        $request->userPicks()->where('user_id', $guides->last()->id)->delete();
        $this->assertSame(13, $support->displayVoteQuantity($request));
        $this->assertSame(7, $support->displaySupporterCount($request, $request->submitted_by));
    }

    public static function closedStatuses(): array
    {
        return [
            'coming soon' => ['coming_soon'],
            'scheduled' => ['scheduled'],
            'recorded' => ['recorded'],
            'published' => ['published'],
            'passed' => ['passed'],
            'already seen' => ['already_seen'],
        ];
    }
}
