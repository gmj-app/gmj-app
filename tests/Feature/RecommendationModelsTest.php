<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\CreatorOwner;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use Database\Seeders\CreatorSeeder;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RecommendationModelsTest extends TestCase
{
    use RefreshDatabase;

    public function test_models_have_the_expected_relationships_and_defaults(): void
    {
        $recommendation = Recommendation::factory()->create();
        $userPick = UserPick::factory()->create([
            'recommendation_id' => $recommendation->id,
            'creator_id' => $recommendation->creator_id,
        ]);
        $owner = User::factory()->create();
        $fan = User::factory()->create();
        $favorite = CreatorFavorite::query()->create([
            'creator_id' => $recommendation->creator_id,
            'user_id' => $fan->id,
        ]);
        $moderator = User::factory()->create();
        $recommendation->update([
            'scheduled_for' => now()->addDay(),
            'published_at' => now(),
            'moderation_reason' => 'duplicate',
            'moderation_note' => 'Already requested.',
            'moderated_by' => $moderator->id,
            'moderated_at' => now(),
        ]);
        $recommendation->refresh();
        $creatorOwner = CreatorOwner::query()->create([
            'creator_id' => $recommendation->creator_id,
            'user_id' => $owner->id,
        ]);

        $this->assertSame('pending', $recommendation->status);
        $this->assertFalse($recommendation->is_pinned);
        $this->assertSame('Pending Review', $recommendation->statusLabel());
        $this->assertNotNull($recommendation->scheduled_for);
        $this->assertNotNull($recommendation->published_at);
        $this->assertNotNull($recommendation->moderated_at);
        $this->assertSame($moderator->id, $recommendation->moderatedBy->id);
        $this->assertSame('unverified', $recommendation->creator->verification_status);
        $this->assertSame('active', $recommendation->creator->status);
        $this->assertTrue($recommendation->creator->submissions_open);
        $this->assertSame('manual', $recommendation->creator->recommendation_approval_mode);
        $this->assertFalse($recommendation->creator->autoApprovesRecommendations());
        $this->assertSame('pending', $recommendation->creator->defaultRecommendationStatus());
        $this->assertNull($recommendation->creator->bio);
        $this->assertNull($recommendation->creator->submission_instructions);
        $this->assertNull($recommendation->creator->deactivated_at);
        $this->assertNull($recommendation->creator->verified_at);
        $this->assertSame($recommendation->creator_id, $recommendation->creator->id);
        $this->assertSame($recommendation->submitted_by, $recommendation->submittedBy->id);
        $this->assertSame($userPick->user_id, $userPick->user->id);
        $this->assertSame($userPick->creator_id, $userPick->creator->id);
        $this->assertSame($userPick->recommendation_id, $userPick->recommendation->id);
        $this->assertTrue($recommendation->creator->recommendations->contains($recommendation));
        $this->assertTrue($recommendation->submittedBy->recommendationsSubmitted->contains($recommendation));
        $this->assertTrue($userPick->user->userPicks->contains($userPick));
        $this->assertTrue($userPick->creator->userPicks->contains($userPick));
        $this->assertTrue($recommendation->userPicks->contains($userPick));
        $this->assertSame('owner', $creatorOwner->role);
        $this->assertSame($owner->id, $creatorOwner->user->id);
        $this->assertSame($recommendation->creator_id, $creatorOwner->creator->id);
        $this->assertTrue($owner->ownedCreators->contains($recommendation->creator));
        $this->assertTrue($owner->creatorOwners->contains($creatorOwner));
        $this->assertTrue($recommendation->creator->owners->contains($owner));
        $this->assertTrue($recommendation->creator->creatorOwners->contains($creatorOwner));
        $this->assertSame($fan->id, $favorite->user->id);
        $this->assertSame($recommendation->creator_id, $favorite->creator->id);
        $this->assertTrue($fan->favoriteCreators->contains($recommendation->creator));
        $this->assertTrue($fan->creatorFavorites->contains($favorite));
        $this->assertTrue($recommendation->creator->favoritedBy->contains($fan));
        $this->assertTrue($recommendation->creator->creatorFavorites->contains($favorite));
    }

    public function test_a_user_cannot_pick_the_same_recommendation_twice(): void
    {
        $user = User::factory()->create();
        $recommendation = Recommendation::factory()->create();

        UserPick::factory()->create([
            'user_id' => $user->id,
            'creator_id' => $recommendation->creator_id,
            'recommendation_id' => $recommendation->id,
        ]);

        $this->expectException(QueryException::class);

        UserPick::factory()->create([
            'user_id' => $user->id,
            'creator_id' => $recommendation->creator_id,
            'recommendation_id' => $recommendation->id,
        ]);
    }

    public function test_youtube_thumbnail_url_requires_a_valid_looking_video_id(): void
    {
        $recommendation = Recommendation::factory()->make([
            'youtube_video_id' => 'dQw4w9WgXcQ',
        ]);

        $this->assertSame(
            'https://img.youtube.com/vi/dQw4w9WgXcQ/hqdefault.jpg',
            $recommendation->youtubeThumbnailUrl(),
        );

        foreach ([null, '', 'short', 'not-a-video-id'] as $invalidVideoId) {
            $recommendation->youtube_video_id = $invalidVideoId;

            $this->assertNull($recommendation->youtubeThumbnailUrl());
        }
    }

    public function test_creator_card_description_uses_bio_instructions_and_fallback_in_priority_order(): void
    {
        $creatorWithBio = Creator::factory()->make([
            'bio' => "  <strong>A creator bio</strong>\n with extra spacing.  ",
            'submission_instructions' => 'These instructions should not be used.',
        ]);
        $creatorWithInstructions = Creator::factory()->make([
            'bio' => null,
            'submission_instructions' => "  Suggest music,\n documentaries, and interviews. ",
        ]);
        $creatorWithoutDescription = Creator::factory()->make([
            'bio' => null,
            'submission_instructions' => null,
        ]);
        $creatorWithLongBio = Creator::factory()->make([
            'bio' => str_repeat('Long creator biography text. ', 10),
        ]);

        $this->assertSame('A creator bio with extra spacing.', $creatorWithBio->card_description);
        $this->assertSame(
            'Suggest music, documentaries, and interviews.',
            $creatorWithInstructions->card_description,
        );
        $this->assertSame("Help guide this creator's journey.", $creatorWithoutDescription->card_description);
        $this->assertLessThanOrEqual(123, mb_strlen($creatorWithLongBio->card_description));
    }

    public function test_creator_default_recommendation_status_uses_the_approval_mode(): void
    {
        $manualCreator = Creator::factory()->make([
            'recommendation_approval_mode' => Creator::APPROVAL_MODE_MANUAL,
        ]);
        $autoCreator = Creator::factory()->make([
            'recommendation_approval_mode' => Creator::APPROVAL_MODE_AUTO,
        ]);

        $this->assertFalse($manualCreator->autoApprovesRecommendations());
        $this->assertSame('pending', $manualCreator->defaultRecommendationStatus());
        $this->assertTrue($autoCreator->autoApprovesRecommendations());
        $this->assertSame('approved', $autoCreator->defaultRecommendationStatus());
    }

    public function test_creator_seeder_creates_jfragment(): void
    {
        $this->seed(CreatorSeeder::class);

        $this->assertDatabaseHas('creators', [
            'slug' => 'jfragment',
            'display_name' => 'JFragment',
            'channel_url' => 'https://www.youtube.com/@jasoncalebjohnson',
        ]);
    }

    public function test_a_user_cannot_have_duplicate_ownership_for_the_same_creator(): void
    {
        $creator = Creator::factory()->create();
        $user = User::factory()->create();

        CreatorOwner::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);

        $this->expectException(QueryException::class);

        CreatorOwner::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);
    }

    public function test_a_user_cannot_favorite_the_same_creator_twice(): void
    {
        $creator = Creator::factory()->create();
        $user = User::factory()->create();

        CreatorFavorite::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);

        $this->expectException(QueryException::class);

        CreatorFavorite::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $user->id,
        ]);
    }
}
