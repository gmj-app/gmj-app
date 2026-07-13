<?php

namespace Tests\Feature;

use App\Events\RequestPublished;
use App\Models\Creator;
use App\Models\CreatorOwner;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use App\Services\RecommendationStatusTransitionService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PhaseTwoRequestNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_publication_notifies_submitter_and_distinct_historical_supporters_once(): void
    {
        $creator = Creator::factory()->create(['display_name' => 'Journey Creator']);
        $actor = User::factory()->create();
        CreatorOwner::query()->create(['creator_id' => $creator->id, 'user_id' => $actor->id]);
        $submitter = User::factory()->create();
        $supporter = User::factory()->create();
        $releasedSupporter = User::factory()->create();
        $deletedSupporter = User::factory()->create();
        $request = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $submitter->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
            'title' => 'Original request title',
            'published_title' => 'Published reaction title',
            'status' => 'approved',
        ]);
        UserPick::factory()->create(['creator_id' => $creator->id, 'recommendation_id' => $request->id, 'user_id' => $submitter->id, 'vote_count' => 2]);
        UserPick::factory()->create(['creator_id' => $creator->id, 'recommendation_id' => $request->id, 'user_id' => $supporter->id, 'vote_count' => 5]);
        UserPick::factory()->create(['creator_id' => $creator->id, 'recommendation_id' => $request->id, 'user_id' => $releasedSupporter->id, 'vote_count' => 3, 'released_at' => now(), 'release_reason' => 'creator_unavailable']);
        UserPick::factory()->create(['creator_id' => $creator->id, 'recommendation_id' => $request->id, 'user_id' => $deletedSupporter->id, 'vote_count' => 1]);
        $deletedSupporter->delete();

        app(RecommendationStatusTransitionService::class)->transition($request, 'published', $actor, [], 'creator');

        $this->assertCount(1, $submitter->notifications);
        $this->assertSame('Your request was published', $submitter->notifications->first()->data['title']);
        $this->assertCount(1, $supporter->notifications);
        $this->assertSame('A request you supported was published', $supporter->notifications->first()->data['title']);
        $this->assertCount(1, $releasedSupporter->notifications);
        $this->assertCount(0, $deletedSupporter->notifications);
        $this->assertStringContainsString("/{$creator->slug}/published#recommendation-{$request->id}", $submitter->notifications->first()->data['action_url']);

        RequestPublished::dispatch($request->id, $creator->id, $submitter->id, $actor->id, 'creator', now()->toIso8601String());
        $this->assertCount(1, $submitter->fresh()->notifications);
        $this->assertCount(1, $supporter->fresh()->notifications);
    }

    public function test_spam_removed_support_does_not_receive_publication_notification(): void
    {
        $creator = Creator::factory()->create();
        $actor = User::factory()->create();
        $submitter = User::factory()->create();
        $removedSupporter = User::factory()->create();
        $request = Recommendation::factory()->create(['creator_id' => $creator->id, 'submitted_by' => $submitter->id, 'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN, 'status' => 'approved']);
        UserPick::factory()->create(['creator_id' => $creator->id, 'recommendation_id' => $request->id, 'user_id' => $removedSupporter->id, 'released_at' => now(), 'release_reason' => 'request_removed']);

        app(RecommendationStatusTransitionService::class)->transition($request, 'published', $actor, [], 'creator');

        $this->assertCount(0, $removedSupporter->notifications);
        $this->assertCount(1, $submitter->notifications);
    }

    public function test_guide_created_request_notifies_creator_with_moderation_specific_copy(): void
    {
        foreach ([Creator::APPROVAL_MODE_AUTO, Creator::APPROVAL_MODE_MANUAL] as $mode) {
            $creator = Creator::factory()->create(['recommendation_approval_mode' => $mode]);
            $owner = User::factory()->create();
            $guide = User::factory()->create(['public_display_name' => 'Helpful Guide']);
            CreatorOwner::query()->create(['creator_id' => $creator->id, 'user_id' => $owner->id]);

            $this->actingAs($guide)->post(route('recommendations.store', $creator), [
                'recommendation_type' => 'topic',
                'title' => 'A useful request',
                'description' => 'Enough context for this topic request.',
                'confirm_favorite' => '1',
            ])->assertRedirect();

            $notification = $owner->fresh()->notifications()->sole();
            $request = Recommendation::query()->where('creator_id', $creator->id)->sole();
            $this->assertSame($mode === Creator::APPROVAL_MODE_MANUAL ? 'New request awaiting review' : 'New request added', $notification->data['title']);
            $this->assertStringContainsString('Helpful Guide', $notification->data['message']);
            $this->assertStringNotContainsString('Recommendation', $notification->data['message']);
            $this->assertStringContainsString($mode === Creator::APPROVAL_MODE_MANUAL ? "#request-{$request->id}" : "#recommendation-{$request->id}", $notification->data['action_url']);
        }
    }

    public function test_creator_submitting_to_own_page_does_not_receive_redundant_notification(): void
    {
        $creator = Creator::factory()->create();
        $owner = User::factory()->create();
        CreatorOwner::query()->create(['creator_id' => $creator->id, 'user_id' => $owner->id]);

        $this->actingAs($owner)->post(route('recommendations.store', $creator), [
            'recommendation_type' => 'topic',
            'title' => 'Owner request',
            'description' => 'The owner is acting as a Guide here.',
            'confirm_favorite' => '1',
        ])->assertRedirect();

        $this->assertCount(0, $owner->fresh()->notifications);
    }
}
