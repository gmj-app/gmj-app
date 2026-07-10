<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorSoftDeleteCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_soft_deletes_creator_without_deleting_related_data(): void
    {
        $creator = Creator::factory()->create([
            'display_name' => 'Vitamin B Reacts',
            'slug' => 'vitamin-b-reacts',
        ]);
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);
        $user = User::factory()->create();
        UserPick::factory()->create([
            'creator_id' => $creator->id,
            'recommendation_id' => $recommendation->id,
            'user_id' => $user->id,
        ]);

        $this->artisan('creators:soft-delete vitamin-b-reacts')
            ->expectsOutput("Soft-deleted creator #{$creator->id} (Vitamin B Reacts).")
            ->expectsOutput('User accounts, recommendations, votes, and uploads were not deleted.')
            ->assertExitCode(0);

        $this->assertSoftDeleted('creators', ['id' => $creator->id]);
        $this->assertDatabaseHas('recommendations', ['id' => $recommendation->id]);
        $this->assertDatabaseHas('user_picks', ['recommendation_id' => $recommendation->id]);
        $this->assertDatabaseHas('users', ['id' => $user->id]);
    }

    public function test_command_can_soft_delete_by_exact_display_name(): void
    {
        $creator = Creator::factory()->create([
            'display_name' => 'Vitamin B Reacts',
            'slug' => 'vitamin-b-reacts',
        ]);

        $this->artisan('creators:soft-delete "Vitamin B Reacts"')
            ->assertExitCode(0);

        $this->assertSoftDeleted('creators', ['id' => $creator->id]);
    }
}
