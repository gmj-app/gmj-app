<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use App\Services\Accolades\Evaluators\GuideInfluenceEvaluator;
use App\Services\Accolades\Evaluators\GuideSupportedPublicationEvaluator;
use App\Services\RequestSupportRecipientResolver;
use App\Services\RequestSupportService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RequesterSupportTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_and_pending_submissions_receive_one_real_support_and_retries_do_not_add_votes(): void
    {
        foreach ([Creator::APPROVAL_MODE_AUTO, Creator::APPROVAL_MODE_MANUAL] as $mode) {
            $creator = Creator::factory()->create(['recommendation_approval_mode' => $mode]);
            $guide = User::factory()->create();
            $this->actingAs($guide)->post(route('recommendations.store', $creator), [
                'recommendation_type' => 'topic', 'title' => 'A new request',
                'description' => 'Please cover this topic.', 'confirm_favorite' => '1',
            ])->assertSessionHasNoErrors()->assertRedirect(route('creator.queue', $creator));
            $request = $creator->recommendations()->sole();
            app(RequestSupportService::class)->supportRequest($guide, $request);
            $this->assertSame(1, $request->userPicks()->count());
            $this->assertDatabaseHas('user_picks', ['recommendation_id' => $request->id, 'user_id' => $guide->id, 'vote_count' => 1]);
            if ($mode === Creator::APPROVAL_MODE_AUTO) {
                $this->get(route('creator.queue', $creator))->assertOk()->assertSee('You requested')->assertSee('aria-pressed="true"', false);
                $other = User::factory()->create();
                $this->actingAs($other)->postJson(route('recommendations.vote', [$creator, $request]))->assertOk()->assertJson(['votes' => 2]);
                $this->deleteJson(route('recommendations.vote.destroy', [$creator, $request]))->assertOk()->assertJson(['votes' => 1]);
                $this->actingAs($guide);
                $this->delete('/'.$creator->slug.'/recommendations/'.$request->id.'/vote')->assertRedirect();
                $this->assertSame(0, $request->userPicks()->count());
                $this->get(route('creator.queue', $creator))->assertSee('You requested');
                $this->assertSame(0, $request->userPicks()->count());
            }
        }
    }

    public function test_backfill_is_dry_by_default_idempotent_and_preserves_closed_and_creator_requests(): void
    {
        $guide = User::factory()->create();
        $creator = Creator::factory()->create();
        $attributes = ['creator_id' => $creator->id, 'submitted_by' => $guide->id, 'submission_source' => 'fan'];
        $open = Recommendation::factory()->create($attributes + ['status' => 'approved']);
        $pending = Recommendation::factory()->create($attributes + ['status' => 'pending']);
        foreach (['coming_soon', 'scheduled', 'recorded', 'published', 'passed', 'already_seen', 'hidden', 'withdrawn', 'merged_duplicate'] as $status) {
            Recommendation::factory()->create($attributes + ['status' => $status]);
        }
        Recommendation::factory()->create(array_merge($attributes, ['status' => 'approved', 'submission_source' => 'creator']));
        $this->artisan('requests:backfill-requester-support --dry-run')->assertSuccessful();
        $this->assertDatabaseCount('user_picks', 0);
        $this->artisan('requests:backfill-requester-support --apply')->assertSuccessful();
        $this->artisan('requests:backfill-requester-support --apply')->assertSuccessful();
        $this->assertDatabaseCount('user_picks', 2);
        $this->assertEqualsCanonicalizing([$open->id, $pending->id], UserPick::pluck('recommendation_id')->all());
        $this->assertSame([1], UserPick::pluck('vote_count')->unique()->values()->all());
    }

    public function test_support_failure_rolls_back_submission(): void
    {
        $this->mock(RequestSupportService::class)->shouldReceive('supportRequest')->once()->andThrow(new \RuntimeException('support unavailable'));
        $this->actingAs(User::factory()->create())->post(route('recommendations.store', Creator::factory()->create()), [
            'recommendation_type' => 'topic', 'title' => 'Rollback request',
            'description' => 'Please cover this topic.', 'confirm_favorite' => '1',
        ])->assertStatus(500);
        $this->assertDatabaseCount('recommendations', 0);
        $this->assertDatabaseCount('user_picks', 0);
    }

    public function test_own_publication_counts_once_in_support_and_influence_but_excludes_duplicate_notification_recipient(): void
    {
        $guide = User::factory()->create();
        $request = Recommendation::factory()->create(['submitted_by' => $guide->id, 'submission_source' => 'fan', 'status' => 'published']);
        app(RequestSupportService::class)->supportRequest($guide, $request);
        $this->assertSame(1, app(GuideSupportedPublicationEvaluator::class)->evaluate($guide->id)->value);
        $this->assertSame(1, app(GuideInfluenceEvaluator::class)->evaluate($guide->id)->value);
        $this->assertCount(0, app(RequestSupportRecipientResolver::class)->resolve($request, $guide->id));
    }
}
