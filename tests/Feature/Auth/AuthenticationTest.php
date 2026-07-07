<?php

namespace Tests\Feature\Auth;

use App\Models\Creator;
use App\Models\CreatorOwner;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class AuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_screen_can_be_rendered(): void
    {
        $this->get('/login')
            ->assertStatus(200)
            ->assertSee('Guide My Journey')
            ->assertSee('favicon.svg', false)
            ->assertSee('Sign in')
            ->assertDontSee('Sign in to Guide My Journey')
            ->assertSeeInOrder(['Fans', 'SUGGEST', 'Communities', 'VOTE', 'Creators', 'DECIDE'])
            ->assertSee('bg-gradient-to-r from-sky-500 via-indigo-500 to-violet-500 bg-clip-text text-transparent', false)
            ->assertDontSee('Fans suggest. Communities vote. Creators decide.')
            ->assertSee('Continue with Google')
            ->assertDontSee('Use the same Google account you use for YouTube.')
            ->assertDontSee('This does not connect or verify your YouTube channel.')
            ->assertDontSee('Forgot your password?')
            ->assertDontSee('name="password"', false)
            ->assertSee(route('auth.google.redirect', absolute: false), false);
    }

    public function test_password_login_is_disabled(): void
    {
        $user = User::factory()->create();

        $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Guide My Journey uses Google sign-in for MVP.');

        $this->assertGuest();
    }

    public function test_google_redirect_uses_basic_identity_scopes_only(): void
    {
        config([
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'https://example.com/auth/google/callback',
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('scopes')
            ->once()
            ->with(['openid', 'email', 'profile'])
            ->andReturnSelf();
        $provider->shouldReceive('redirect')
            ->once()
            ->andReturn(redirect('https://accounts.google.com/o/oauth2/v2/auth'));

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);

        $this->get(route('auth.google.redirect'))
            ->assertRedirect('https://accounts.google.com/o/oauth2/v2/auth');
    }

    public function test_google_redirect_url_includes_client_id_and_normalized_callback(): void
    {
        config([
            'app.url' => 'https://gmj-app-mvp-production-jfo2mv.laravel.cloud',
            'services.google.client_id' => 'google-client-id',
            'services.google.client_secret' => 'google-client-secret',
            'services.google.redirect' => 'https://gmj-app-mvp-production-jfo2mv.laravel.cloud',
        ]);

        $response = $this->get(route('auth.google.redirect'));
        $redirect = $response->headers->get('Location');

        $response->assertRedirect();
        $this->assertStringContainsString('client_id=google-client-id', $redirect);
        $this->assertStringContainsString(
            'redirect_uri=https%3A%2F%2Fgmj-app-mvp-production-jfo2mv.laravel.cloud%2Fauth%2Fgoogle%2Fcallback',
            $redirect,
        );
    }

    public function test_google_callback_creates_a_new_guide_user(): void
    {
        $this->mockGoogleUser('google-123', 'guide@example.com', 'New Guide', 'https://example.com/avatar.jpg');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('profile.setup', absolute: false));

        $this->assertAuthenticated();
        $this->assertDatabaseHas('users', [
            'email' => 'guide@example.com',
            'name' => 'New Guide',
            'google_id' => 'google-123',
            'avatar_url' => 'https://example.com/avatar.jpg',
            'auth_provider' => 'google',
            'public_display_name' => null,
            'public_handle' => null,
            'public_profile_completed_at' => null,
        ]);
    }

    public function test_login_returns_owner_to_the_protected_page_that_required_authentication(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $owner = User::factory()->create();
        CreatorOwner::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        $this->get(route('creators.settings.edit', $creator))
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', route('creators.settings.edit', $creator));

        $this->mockGoogleUser('owner-google-id', $owner->email, $owner->name);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('creators.settings.edit', $creator));

        $this->assertAuthenticatedAs($owner->fresh());
        $this->assertSame('owner-google-id', $owner->fresh()->google_id);
    }

    public function test_guest_upvote_login_returns_to_recommendation_without_replaying_vote(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $recommendation = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'status' => 'approved',
        ]);
        $user = User::factory()->create();
        $returnUrl = route('creator.queue', $creator, absolute: false)
            ."#recommendation-{$recommendation->id}";

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('id="recommendation-'.$recommendation->id.'"', false)
            ->assertSee(route('login.required', ['return' => $returnUrl]), false);

        $this->get(route('login.required', ['return' => $returnUrl]))
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', $returnUrl);

        $this->mockGoogleUser('guide-google-id', $user->email, $user->name);

        $this->get(route('auth.google.callback'))
            ->assertRedirect($returnUrl);

        $this->get($returnUrl)
            ->assertOk()
            ->assertDontSee('data-modal-root="participation-confirmation"', false)
            ->assertDontSee('bg-slate-950/60 backdrop-blur-[2px]', false)
            ->assertDontSee('style="display: block;"', false)
            ->assertDontSee('inert', false);

        $this->assertDatabaseCount('user_picks', 0);
        $this->assertDatabaseCount('creator_favorites', 0);
    }

    public function test_guest_favorite_and_submit_links_preserve_creator_context(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $user = User::factory()->create();
        $creatorPage = route('creator.queue', $creator, absolute: false);

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee(route('login.required', ['return' => $creatorPage]), false)
            ->assertSee(route('recommendations.create', $creator), false);

        $this->get(route('login.required', ['return' => $creatorPage]))
            ->assertRedirect(route('login'));

        $this->mockGoogleUser('guide-google-id', $user->email, $user->name);

        $this->get(route('auth.google.callback'))
            ->assertRedirect($creatorPage);

        $this->post(route('logout'));

        $this->get(route('recommendations.create', $creator))
            ->assertRedirect(route('login'))
            ->assertSessionHas('url.intended', route('recommendations.create', $creator));

        $this->mockGoogleUser('guide-google-id', $user->email, $user->name);

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('recommendations.create', $creator));
    }

    public function test_login_required_redirect_rejects_external_and_protocol_relative_urls(): void
    {
        foreach ([
            'https://evil.example/phishing',
            '//evil.example/phishing',
            '/\\evil.example/phishing',
            "/\nevil.example/phishing",
        ] as $returnUrl) {
            $this->get(route('login.required', ['return' => $returnUrl]))
                ->assertRedirect(route('login'))
                ->assertSessionMissing('url.intended');
        }
    }

    public function test_google_callback_rejects_unsafe_intended_urls(): void
    {
        session(['url.intended' => 'https://evil.example/phishing']);
        $this->mockGoogleUser('guide-google-id', 'guide@example.com', 'Guide');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('profile.setup', absolute: false));
    }

    public function test_authenticated_dashboard_does_not_render_a_modal_backdrop(): void
    {
        $this->actingAs(User::factory()->create())
            ->get('/dashboard')
            ->assertOk()
            ->assertSee("I'm a Creator", false)
            ->assertSee("I'm a Guide", false)
            ->assertDontSee("You're logged in!")
            ->assertDontSee('fixed inset-0 overflow-y-auto', false)
            ->assertDontSee('absolute inset-0 bg-gray-500 opacity-75', false);
    }

    public function test_google_callback_logs_in_existing_google_id_user(): void
    {
        $user = User::factory()->create([
            'google_id' => 'existing-google-id',
            'email' => 'existing@example.com',
        ]);

        $this->mockGoogleUser('existing-google-id', 'changed@example.com', 'Changed Name');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertAuthenticatedAs($user);
        $this->assertSame('existing@example.com', $user->fresh()->email);
    }

    public function test_google_callback_links_existing_user_by_email_without_overwriting_password(): void
    {
        $user = User::factory()->create([
            'email' => 'existing@example.com',
            'password' => Hash::make('original-password'),
            'google_id' => null,
        ]);
        $password = $user->password;

        $this->mockGoogleUser('linked-google-id', $user->email, 'Google Name', 'https://example.com/avatar.jpg');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard', absolute: false));

        $user->refresh();

        $this->assertAuthenticatedAs($user);
        $this->assertSame('linked-google-id', $user->google_id);
        $this->assertSame($password, $user->password);
        $this->assertSame('https://example.com/avatar.jpg', $user->avatar_url);
    }

    public function test_users_can_logout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/logout');

        $this->assertGuest();
        $response->assertRedirect('/');
    }

    private function mockGoogleUser(string $id, ?string $email, string $name, ?string $avatar = null): void
    {
        $socialiteUser = (new SocialiteUser)->map([
            'id' => $id,
            'name' => $name,
            'email' => $email,
            'avatar' => $avatar,
        ]);

        $provider = Mockery::mock();
        $provider->shouldReceive('user')
            ->once()
            ->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')
            ->once()
            ->with('google')
            ->andReturn($provider);
    }
}
