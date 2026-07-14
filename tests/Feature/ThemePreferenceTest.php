<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ThemePreferenceTest extends TestCase
{
    use RefreshDatabase;

    public function test_new_guest_receives_dark_before_paint_without_using_system_preference(): void
    {
        $this->get('/')->assertOk()
            ->assertSee('<html lang="en" class="dark" data-theme="dark">', false)
            ->assertSee("const theme = accountTheme || browserTheme || 'dark';", false)
            ->assertSee("document.documentElement.classList.toggle('dark', theme === 'dark');", false)
            ->assertDontSee('prefers-color-scheme');
    }

    public function test_new_accounts_default_to_dark(): void
    {
        $user = User::factory()->create();

        $this->assertSame('dark', $user->theme_preference);
        $this->actingAs($user)->get('/')->assertSee('<html lang="en" class="dark" data-theme="dark">', false);
    }

    public function test_existing_explicit_account_preferences_are_server_rendered(): void
    {
        $light = User::factory()->create(['theme_preference' => 'light']);
        $dark = User::factory()->create(['theme_preference' => 'dark']);

        $this->actingAs($light)->get('/')->assertSee('<html lang="en" class="" data-theme="light">', false);
        $this->actingAs($dark)->get('/')->assertSee('<html lang="en" class="dark" data-theme="dark">', false);
    }

    public function test_guest_browser_preference_persists_and_invalid_values_fall_back_to_dark(): void
    {
        $this->withUnencryptedCookie('theme', 'light')->get('/')
            ->assertSee('<html lang="en" class="" data-theme="light">', false);

        $this->withUnencryptedCookie('theme', 'invalid')->get('/')
            ->assertSee('<html lang="en" class="dark" data-theme="dark">', false);
    }

    public function test_authenticated_selection_persists_across_sessions(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->patchJson(route('profile.theme.update'), ['theme' => 'light'])
            ->assertOk()->assertJson(['theme' => 'light']);

        $this->assertSame('light', $user->fresh()->theme_preference);
        $this->flushSession();
        $this->actingAs($user->fresh())->get('/')
            ->assertSee('<html lang="en" class="" data-theme="light">', false);
    }

    public function test_invalid_or_missing_existing_account_preference_falls_back_to_dark(): void
    {
        $user = User::factory()->create();
        DB::table('users')->where('id', $user->id)->update(['theme_preference' => 'system']);

        $this->actingAs($user->fresh())->get('/')
            ->assertSee('<html lang="en" class="dark" data-theme="dark">', false);
    }

    public function test_theme_toggle_keeps_browser_and_account_persistence_hooks(): void
    {
        $this->actingAs(User::factory()->create())->get('/')->assertOk()
            ->assertSee('x-data="siteNavigation"', false)
            ->assertSee('name="theme-update-url" content="'.route('profile.theme.update').'"', false)
            ->assertSee('name="csrf-token"', false)
            ->assertSee("dark ? 'Switch to light theme' : 'Switch to dark theme'", false);

        $javascript = file_get_contents(resource_path('js/app.js'));
        $this->assertStringContainsString("Alpine.data('siteNavigation'", $javascript);
        $this->assertStringContainsString("localStorage.setItem('theme', theme);", $javascript);
        $this->assertStringContainsString('meta[name="csrf-token"]', $javascript);
        $this->assertStringContainsString('meta[name="theme-update-url"]', $javascript);
    }

    public function test_navigation_uses_named_state_without_malformed_inline_javascript(): void
    {
        $response = $this->actingAs(User::factory()->create())->get('/')->assertOk()
            ->assertSee('@click="toggleAccountMenu()"', false)
            ->assertSee('@click="toggleNotifications()"', false)
            ->assertSee('@click="toggleMobileMenu()"', false)
            ->assertSee('@keydown.escape.window="closeAll()"', false)
            ->assertSee('id="account-menu"', false)
            ->assertSee('id="notification-dropdown"', false);

        $response->assertDontSee('x-data="{', false)
            ->assertDontSee('toggleTheme() {', false)
            ->assertDontSee('meta[name=&quot;', false);
    }
}
