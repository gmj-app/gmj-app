<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_password_update_is_disabled_for_google_only_mvp(): void
    {
        $user = User::factory()->create();
        $password = $user->password;

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'google-sign-in-only');

        $this->assertSame($password, $user->refresh()->password);
    }

    public function test_password_update_does_not_validate_password_fields(): void
    {
        $user = User::factory()->create();
        $password = $user->password;

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->put('/password', [
                'current_password' => 'wrong-password',
                'password' => 'new-password',
                'password_confirmation' => 'new-password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile')
            ->assertSessionHas('status', 'google-sign-in-only');

        $this->assertSame($password, $user->refresh()->password);
    }
}
