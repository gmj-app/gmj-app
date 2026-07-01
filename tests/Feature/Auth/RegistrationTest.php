<?php

namespace Tests\Feature\Auth;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RegistrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_registration_screen_shows_google_only_entry(): void
    {
        $this->get('/register')
            ->assertStatus(200)
            ->assertSee('Sign in')
            ->assertDontSee('Sign in to Guide My Journey')
            ->assertSee('Continue with Google')
            ->assertSeeInOrder(['Fans', 'SUGGEST', 'Communities', 'VOTE', 'Creators', 'DECIDE'])
            ->assertSee('bg-gradient-to-r from-sky-500 via-indigo-500 to-violet-500 bg-clip-text text-transparent', false)
            ->assertDontSee('Fans suggest. Communities vote. Creators decide.')
            ->assertDontSee('Use the same Google account you use for YouTube.')
            ->assertDontSee('This does not connect or verify your YouTube channel.')
            ->assertDontSee('name="email"', false)
            ->assertDontSee('name="password"', false)
            ->assertDontSee('Confirm Password');
    }

    public function test_password_registration_is_disabled(): void
    {
        $this->post('/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Guide My Journey uses Google sign-in for MVP.');

        $this->assertGuest();
        $this->assertDatabaseMissing('users', [
            'email' => 'test@example.com',
        ]);
    }
}
