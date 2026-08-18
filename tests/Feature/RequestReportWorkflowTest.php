<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorOwner;
use App\Models\Recommendation;
use App\Models\RequestReport;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequestReportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guides_report_without_mutating_and_reports_aggregate(): void
    {
        [$creator,$item,$owner] = $this->context();
        $a = User::factory()->create();
        $b = User::factory()->create();
        $this->actingAs($a)->post(route('recommendations.reports.store', [$creator, $item]), ['reason' => 'spam', 'details' => 'Repeated advertising'])->assertRedirect(route('creator.queue', $creator));
        $this->assertSame('approved', $item->fresh()->status);
        $this->assertDatabaseHas('request_reports', ['recommendation_id' => $item->id, 'reason' => 'spam', 'details' => 'Repeated advertising', 'status' => 'pending']);
        $this->actingAs($b)->post(route('recommendations.reports.store', [$creator, $item]), ['reason' => 'misleading'])->assertRedirect();
        $this->assertSame(2, RequestReport::where('recommendation_id', $item->id)->count());
        $this->actingAs($a)->post(route('recommendations.reports.store', [$creator, $item]), ['reason' => 'other'])->assertSessionHasErrors('report');
        $this->assertSame(1, $owner->notifications()->where('data->request_id', $item->id)->count());
    }

    public function test_creator_can_keep_or_hide_while_preserving_history_and_releasing_capacity(): void
    {
        [$creator,$keep,$owner] = $this->context();
        $hide = Recommendation::factory()->for($creator)->create(['status' => 'approved']);
        $guide = User::factory()->create();
        foreach ([$keep, $hide] as $item) {
            RequestReport::create(['recommendation_id' => $item->id, 'creator_id' => $creator->id, 'reported_by_user_id' => $guide->id, 'reason' => 'spam']);
            UserPick::factory()->create(['creator_id' => $creator->id, 'recommendation_id' => $item->id, 'user_id' => $guide->id, 'vote_count' => 1]);
        }
        $this->actingAs($owner)->post(route('creators.reports.resolve', [$creator, $keep]), ['resolution' => 'kept'])->assertRedirect();
        $this->assertSame('approved', $keep->fresh()->status);
        $this->post(route('creators.reports.resolve', [$creator, $hide]), ['resolution' => 'hidden'])->assertRedirect();
        $this->assertSame('hidden', $hide->fresh()->status);
        $this->assertNotContains('hidden', Recommendation::suggestionConsumingStatuses());
        $this->assertDatabaseHas('user_picks', ['recommendation_id' => $hide->id, 'user_id' => $guide->id, 'release_reason' => 'request_hidden']);
        $this->assertDatabaseHas('request_reports', ['recommendation_id' => $hide->id, 'resolution' => 'hidden', 'status' => 'resolved']);
    }

    public function test_unauthorized_guide_cannot_resolve_and_closed_request_cannot_be_reported(): void
    {
        [$creator,$item] = $this->context();
        $guide = User::factory()->create();
        RequestReport::create(['recommendation_id' => $item->id, 'creator_id' => $creator->id, 'reported_by_user_id' => $guide->id, 'reason' => 'spam']);
        $this->actingAs($guide)->post(route('creators.reports.resolve', [$creator, $item]), ['resolution' => 'hidden'])->assertForbidden();
        $item->update(['status' => 'published']);
        $this->post(route('recommendations.reports.store', [$creator, $item]), ['reason' => 'spam'])->assertSessionHasErrors('report');
    }

    private function context(): array
    {
        $creator = Creator::factory()->create(['status' => 'active']);
        $owner = User::factory()->create();
        CreatorOwner::create(['creator_id' => $creator->id, 'user_id' => $owner->id, 'role' => 'owner']);

        return [$creator, Recommendation::factory()->for($creator)->create(['status' => 'approved']), $owner];
    }
}
