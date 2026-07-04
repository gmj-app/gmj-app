<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InternalPlanTestingTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_admin_cannot_access_plan_testing(): void
    {
        config([
            'gmj.plan_testing_enabled' => true,
            'gmj.admin_emails' => ['admin@example.test'],
        ]);

        $this->actingAs(User::factory()->create(['email' => 'guide@example.test']))
            ->get(route('internal.plan-testing'))
            ->assertNotFound();
    }

    public function test_admin_cannot_access_plan_testing_when_disabled(): void
    {
        config([
            'gmj.plan_testing_enabled' => false,
            'gmj.admin_emails' => ['admin@example.test'],
        ]);

        $this->actingAs(User::factory()->create(['email' => 'admin@example.test']))
            ->get(route('internal.plan-testing'))
            ->assertNotFound();
    }

    public function test_admin_can_view_and_change_their_plan_when_enabled(): void
    {
        config([
            'gmj.plan_testing_enabled' => true,
            'gmj.admin_emails' => ['admin@example.test'],
        ]);

        $admin = User::factory()->create([
            'email' => 'admin@example.test',
            'plan_slug' => 'free',
        ]);

        $this->actingAs($admin)
            ->get(route('internal.plan-testing'))
            ->assertOk()
            ->assertSee('Plan Testing')
            ->assertSee('Free')
            ->assertSee('Creator favorites')
            ->assertSee('3');

        $this->post(route('internal.plan-testing'), [
            'plan_slug' => 'pro',
        ])->assertRedirect(route('internal.plan-testing'));

        $this->assertSame('pro', $admin->fresh()->plan_slug);

        $this->get(route('internal.plan-testing'))
            ->assertOk()
            ->assertSee('Pro')
            ->assertSee('10');
    }
}
