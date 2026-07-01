<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PasswordConfirmationTest extends TestCase
{
    use RefreshDatabase;

    public function test_confirm_password_screen_redirects_for_google_only_mvp(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/confirm-password')
            ->assertRedirect(route('dashboard'))
            ->assertSessionHas('status', 'Guide My Journey uses Google sign-in for MVP.');
    }

    public function test_confirm_password_post_redirects_without_validating_password(): void
    {
        $this->actingAs(User::factory()->create())
            ->post('/confirm-password', [
                'password' => 'wrong-password',
            ])
            ->assertRedirect(route('dashboard', absolute: false))
            ->assertSessionHasNoErrors()
            ->assertSessionHas('status', 'Guide My Journey uses Google sign-in for MVP.');
    }
}
