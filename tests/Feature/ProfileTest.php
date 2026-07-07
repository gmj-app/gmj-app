<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_is_displayed(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->get('/profile');

        $response->assertOk()
            ->assertSee('Your account uses Google sign-in.')
            ->assertDontSee('Update Password')
            ->assertDontSee('name="current_password"', false);
    }

    public function test_incomplete_public_profile_is_sent_to_setup_before_using_protected_pages(): void
    {
        $user = User::factory()->publicProfileIncomplete()->create();

        $this->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('profile.setup'));

        $this->assertSame('/dashboard', session('public_profile.intended'));
    }

    public function test_public_profile_setup_saves_normalized_identity_and_returns_to_intended_page(): void
    {
        $user = User::factory()->publicProfileIncomplete()->create();

        $this->actingAs($user)
            ->withSession(['public_profile.intended' => '/creator/create'])
            ->post(route('profile.setup.store'), [
                'public_display_name' => '  Cher   Ree  ',
                'public_handle' => '@Cher_Ree',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect('/creator/create');

        $user->refresh();

        $this->assertSame('Cher Ree', $user->public_display_name);
        $this->assertSame('cher_ree', $user->public_handle);
        $this->assertNotNull($user->public_profile_completed_at);
    }

    public function test_completed_public_profile_skips_setup_page(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('profile.setup'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_public_profile_setup_rejects_private_or_reserved_identity_values(): void
    {
        $taken = User::factory()->create(['public_handle' => 'taken']);
        $user = User::factory()->publicProfileIncomplete()->create();

        $this->actingAs($user)
            ->post(route('profile.setup.store'), [
                'public_display_name' => '<script>Private Name</script>',
                'public_handle' => $taken->public_handle,
            ])
            ->assertSessionHasErrors(['public_display_name', 'public_handle']);

        $this->actingAs($user)
            ->post(route('profile.setup.store'), [
                'public_display_name' => 'Public Guide',
                'public_handle' => 'jfragment',
            ])
            ->assertSessionHasErrors(['public_handle']);

        Creator::factory()->create(['slug' => 'active-creator']);

        $this->actingAs($user)
            ->post(route('profile.setup.store'), [
                'public_display_name' => 'Public Guide',
                'public_handle' => 'active-creator',
            ])
            ->assertSessionHasErrors(['public_handle']);
    }

    public function test_public_identity_can_be_updated_from_profile_without_resetting_completion(): void
    {
        $completedAt = now()->subDay()->startOfSecond();
        $user = User::factory()->create([
            'public_display_name' => 'Old Guide',
            'public_handle' => 'oldguide',
            'public_profile_completed_at' => $completedAt,
        ]);

        $this->actingAs($user)
            ->patch(route('profile.public-identity.update'), [
                'public_display_name' => 'New Guide',
                'public_handle' => 'new-guide',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('profile.edit'));

        $user->refresh();

        $this->assertSame('New Guide', $user->public_display_name);
        $this->assertSame('new-guide', $user->public_handle);
        $this->assertTrue($user->public_profile_completed_at->equalTo($completedAt));
    }

    public function test_profile_information_can_be_updated(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => 'test@example.com',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $user->refresh();

        $this->assertSame('Test User', $user->name);
        $this->assertSame('test@example.com', $user->email);
        $this->assertNull($user->email_verified_at);
    }

    public function test_email_verification_status_is_unchanged_when_the_email_address_is_unchanged(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->patch('/profile', [
                'name' => 'Test User',
                'email' => $user->email,
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/profile');

        $this->assertNotNull($user->refresh()->email_verified_at);
    }

    public function test_user_can_delete_their_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->delete('/profile', [
                'password' => 'password',
            ]);

        $response
            ->assertSessionHasNoErrors()
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_correct_password_must_be_provided_to_delete_account(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user)
            ->from('/profile')
            ->delete('/profile', [
                'password' => 'wrong-password',
            ]);

        $response
            ->assertSessionHasErrorsIn('userDeletion', 'password')
            ->assertRedirect('/profile');

        $this->assertNotNull($user->fresh());
    }
}
