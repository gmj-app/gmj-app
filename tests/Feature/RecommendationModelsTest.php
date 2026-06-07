<?php

namespace Tests\Feature;

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

        $this->assertSame('pending', $recommendation->status);
        $this->assertFalse($recommendation->is_pinned);
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

    public function test_creator_seeder_creates_jfragment(): void
    {
        $this->seed(CreatorSeeder::class);

        $this->assertDatabaseHas('creators', [
            'slug' => 'jfragment',
            'display_name' => 'JFragment',
            'channel_url' => 'https://www.youtube.com/@jasoncalebjohnson',
        ]);
    }
}
