<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorOwner;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use App\Services\RequestDuplicateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class RequestDuplicateWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_reports_are_canonicalized_and_aggregated_without_mutating_requests(): void
    {
        [$creator,$a,$b] = $this->pair();
        $one = User::factory()->create();
        $two = User::factory()->create();
        $service = app(RequestDuplicateService::class);
        $case = $service->report($one, $b, $a);
        $service->report($two, $a, $b);
        $this->assertSame(min($a->id, $b->id), $case->request_low_id);
        $this->assertDatabaseCount('request_duplicate_cases', 1);
        $this->assertDatabaseCount('request_duplicate_reports', 2);
        $this->assertSame('approved', $a->fresh()->status);
        $this->assertSame('approved', $b->fresh()->status);
        $this->expectException(ValidationException::class);
        $service->report($one, $a, $b);
    }

    public function test_confirmed_merge_unions_unique_active_support_and_preserves_history(): void
    {
        [$creator,$a,$b] = $this->pair();
        $reporter = User::factory()->create();
        $owner = User::factory()->create();
        CreatorOwner::create(['creator_id' => $creator->id, 'user_id' => $owner->id, 'role' => 'owner']);
        $onlyA = User::factory()->create();
        $onlyB = User::factory()->create();
        $both = User::factory()->create();
        foreach ([[$onlyA, $a], [$both, $a], [$onlyB, $b], [$both, $b]] as [$user,$item]) {
            UserPick::create(['user_id' => $user->id, 'creator_id' => $creator->id, 'recommendation_id' => $item->id, 'vote_count' => 1]);
        }
        $service = app(RequestDuplicateService::class);
        $case = $service->report($reporter, $a, $b);
        $service->resolve($case, $owner, 'keep_a');
        $this->assertSame(3, UserPick::activeSupport()->where('recommendation_id', $a->id)->distinct()->count('user_id'));
        $this->assertSame(0, UserPick::activeSupport()->where('recommendation_id', $b->id)->count());
        $this->assertDatabaseHas('user_picks', ['user_id' => $both->id, 'recommendation_id' => $b->id, 'release_reason' => 'merged_duplicate']);
        $this->assertDatabaseHas('recommendations', ['id' => $b->id, 'status' => 'merged_duplicate', 'merged_into_request_id' => $a->id, 'submitted_by' => $b->submitted_by]);
        $this->assertSame(3, $case->fresh()->merge_summary['final_unique_supporters']);
    }

    public function test_cross_creator_closed_and_self_pairs_are_rejected_and_only_owner_can_resolve(): void
    {
        [$creator,$a,$b] = $this->pair();
        $guide = User::factory()->create();
        $case = app(RequestDuplicateService::class)->report($guide, $a, $b);
        $this->actingAs($guide)->post(route('creators.duplicates.resolve', [$creator, $case]), ['resolution' => 'not_duplicate'])->assertForbidden();
        $other = Recommendation::factory()->create(['status' => 'approved']);
        foreach ([[$a, $a], [$a, $other]] as [$left,$right]) {
            try {
                app(RequestDuplicateService::class)->report($guide, $left, $right);
                $this->fail('Pair should fail');
            } catch (ValidationException) {
            }
        }
    }

    public function test_public_selection_mode_survives_query_state_and_merged_request_releases_capacity(): void
    {
        [$creator,$a,$b] = $this->pair();
        $guide = User::factory()->create();
        $this->actingAs($guide)->get(route('creator.queue', ['creator' => $creator, 'duplicate_source' => $a->id, 'q' => 'Song', 'per_page' => 50]))->assertOk()->assertSee('Possible duplicate')->assertSee('Select as duplicate')->assertSee('name="duplicate_source" value="'.$a->id.'"', false);
        $this->assertContains('merged_duplicate', Recommendation::STATUSES);
        $this->assertNotContains('merged_duplicate', Recommendation::suggestionConsumingStatuses());
    }

    private function pair(): array
    {
        $creator = Creator::factory()->create(['status' => 'active']);

        return [$creator, Recommendation::factory()->for($creator)->create(['status' => 'approved', 'title' => 'Song A']), Recommendation::factory()->for($creator)->create(['status' => 'approved', 'title' => 'Song B'])];
    }
}
