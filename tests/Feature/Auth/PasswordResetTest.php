<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class PasswordResetTest extends TestCase
{
    use RefreshDatabase;

    public function test_reset_password_link_screen_redirects_to_google_sign_in(): void
    {
        $this->get('/forgot-password')
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Guide My Journey uses Google sign-in for MVP.');
    }

    public function test_reset_password_link_cannot_be_requested(): void
    {
        Notification::fake();

        $user = User::factory()->create();

        $this->post('/forgot-password', ['email' => $user->email])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Guide My Journey uses Google sign-in for MVP.');

        Notification::assertNothingSent();
    }

    public function test_reset_password_screen_redirects_to_google_sign_in(): void
    {
        $this->get('/reset-password/example-token')
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Guide My Journey uses Google sign-in for MVP.');
    }

    public function test_password_cannot_be_reset_with_token_post(): void
    {
        $user = User::factory()->create();

        $this->post('/reset-password', [
            'token' => 'example-token',
            'email' => $user->email,
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Guide My Journey uses Google sign-in for MVP.');
    }
}
