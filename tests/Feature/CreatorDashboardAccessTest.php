<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorOwner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorDashboardAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_from_creator_dashboard(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);

        $this->get(route('creators.dashboard', $creator))
            ->assertRedirect('/login');
    }

    public function test_non_owners_receive_forbidden_response(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get(route('creators.dashboard', $creator))
            ->assertForbidden();
    }

    public function test_creator_owner_can_access_dashboard(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'jfragment',
            'display_name' => 'JFragment',
        ]);
        $owner = User::factory()->create();

        CreatorOwner::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->get(route('creators.dashboard', $creator))
            ->assertOk()
            ->assertSee('Creator Dashboard')
            ->assertSee('JFragment')
            ->assertSee('Recommendations received');
    }

    public function test_non_owner_roles_cannot_access_dashboard_yet(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $user = User::factory()->create();

        CreatorOwner::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $user->id,
            'role' => 'moderator',
        ]);

        $this->actingAs($user)
            ->get(route('creators.dashboard', $creator))
            ->assertForbidden();
    }

    public function test_owner_can_access_dashboard_for_an_inactive_creator(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'inactive-creator',
            'status' => 'inactive',
            'deactivated_at' => now(),
        ]);
        $owner = User::factory()->create();

        CreatorOwner::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->get(route('creators.dashboard', $creator))
            ->assertOk();
    }

    public function test_single_owner_reaches_creator_dashboard_from_my_hub(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'jfragment',
            'display_name' => 'JFragment',
        ]);
        $owner = User::factory()->create();
        CreatorOwner::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My Hub')
            ->assertSee('Manage creator page')
            ->assertSee(route('creators.dashboard', $creator), false);
    }

    public function test_multiple_creator_owner_navigation_links_to_my_creator_pages(): void
    {
        $owner = User::factory()->create();
        $firstCreator = Creator::factory()->create([
            'slug' => 'first-creator',
            'display_name' => 'First Creator',
        ]);
        $secondCreator = Creator::factory()->create([
            'slug' => 'second-creator',
            'display_name' => 'Second Creator',
        ]);

        foreach ([$firstCreator, $secondCreator] as $creator) {
            CreatorOwner::query()->create([
                'creator_id' => $creator->id,
                'user_id' => $owner->id,
                'role' => 'owner',
            ]);
        }

        $this->actingAs($owner)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee(route('creators.index'), false);

        $this->get(route('creators.index'))
            ->assertOk()
            ->assertSee('My Creator Pages')
            ->assertSee('First Creator')
            ->assertSee('Second Creator')
            ->assertSee(route('creators.dashboard', $firstCreator), false)
            ->assertSee(route('creators.dashboard', $secondCreator), false);
    }

    public function test_non_creator_users_do_not_see_creator_dashboard_navigation(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Creator Dashboard');
    }
}
