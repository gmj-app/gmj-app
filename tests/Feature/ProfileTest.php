<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\Recommendation;
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
            ->assertSee('<div class="mx-auto min-w-0 max-w-5xl">', false)
            ->assertSee('<main class="mx-auto min-w-0 max-w-5xl space-y-6 px-4 sm:px-6 lg:px-8" data-profile-content>', false)
            ->assertSeeInOrder(['data-profile-content', 'border border-gray-200 bg-white', 'max-w-xl'], false)
            ->assertSee('This is how other people will see you when you request, vote, or support requests.')
            ->assertDontSee('This is how other people will see you when you suggest, vote, or support requests.')
            ->assertSee('action="'.route('profile.public-identity.update').'"', false)
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

    public function test_default_public_display_name_prompt_appears_for_guide_names(): void
    {
        $user = User::factory()->create([
            'public_display_name' => ' guide ',
            'public_handle' => 'quietguide',
            'public_profile_completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Choose your public display name')
            ->assertSee('name="public_display_name"', false)
            ->assertSee('Save display name')
            ->assertSee("Don't show this again", false)
            ->assertDontSee('name="public_handle"', false);
    }

    public function test_default_public_display_name_prompt_appears_for_missing_display_name_on_public_pages(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $user = User::factory()->create([
            'public_display_name' => null,
            'public_handle' => 'quietguide',
            'public_profile_completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Choose your public display name');
    }

    public function test_default_public_display_name_prompt_is_hidden_for_custom_or_dismissed_names(): void
    {
        $customUser = User::factory()->create([
            'public_display_name' => 'Cher Ree',
            'public_handle' => 'cherree',
            'public_profile_completed_at' => now(),
        ]);

        $this->actingAs($customUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Choose your public display name');

        $guideMeUser = User::factory()->create([
            'public_display_name' => 'GuideMe',
            'public_handle' => 'guideme',
            'public_profile_completed_at' => now(),
        ]);

        $this->actingAs($guideMeUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Choose your public display name');

        $dismissedUser = User::factory()->create([
            'public_display_name' => 'Guide',
            'public_handle' => 'dismissedguide',
            'public_profile_completed_at' => now(),
            'display_name_prompt_dismissed_at' => now(),
        ]);

        $this->actingAs($dismissedUser)
            ->get(route('dashboard'))
            ->assertOk()
            ->assertDontSee('Choose your public display name');
    }

    public function test_display_name_prompt_updates_only_the_display_name(): void
    {
        $user = User::factory()->create([
            'public_display_name' => 'Guide',
            'public_handle' => 'steadyguide',
            'public_profile_completed_at' => null,
        ]);

        $this->actingAs($user)
            ->patch(route('profile.display-name.update'), [
                'public_display_name' => '  Cher   Ree  ',
            ])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $user->refresh();

        $this->assertSame('Cher Ree', $user->public_display_name);
        $this->assertSame('steadyguide', $user->public_handle);
        $this->assertNotNull($user->public_profile_completed_at);
        $this->assertFalse($user->shouldSeeDisplayNamePrompt());
    }

    public function test_display_name_prompt_can_be_dismissed_permanently(): void
    {
        $user = User::factory()->create([
            'public_display_name' => 'Guide',
            'public_handle' => 'steadyguide',
            'public_profile_completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('profile.display-name-prompt.dismiss'), [
                'dont_show_again' => '1',
            ])
            ->assertRedirect();

        $user->refresh();

        $this->assertSame('Guide', $user->public_display_name);
        $this->assertNotNull($user->display_name_prompt_dismissed_at);
        $this->assertFalse($user->shouldSeeDisplayNamePrompt());
    }

    public function test_display_name_prompt_not_now_without_checkbox_is_not_persistent(): void
    {
        $user = User::factory()->create([
            'public_display_name' => 'Guide',
            'public_handle' => 'steadyguide',
            'public_profile_completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->post(route('profile.display-name-prompt.dismiss'))
            ->assertRedirect();

        $user->refresh();

        $this->assertNull($user->display_name_prompt_dismissed_at);
        $this->assertTrue($user->shouldSeeDisplayNamePrompt());
    }

    public function test_display_name_prompt_rejects_default_html_links_and_long_names(): void
    {
        $user = User::factory()->create([
            'public_display_name' => 'Guide',
            'public_handle' => 'steadyguide',
            'public_profile_completed_at' => now(),
        ]);

        $this->actingAs($user)
            ->patch(route('profile.display-name.update'), [
                'public_display_name' => 'GUIDE',
            ])
            ->assertSessionHasErrors(['public_display_name' => 'Please choose a display name other than "Guide".'], null, 'displayNamePrompt');

        $this->actingAs($user)
            ->patch(route('profile.display-name.update'), [
                'public_display_name' => '<script>alert(1)</script>',
            ])
            ->assertSessionHasErrors(['public_display_name' => "Display name can't include links or HTML."], null, 'displayNamePrompt');

        $this->actingAs($user)
            ->patch(route('profile.display-name.update'), [
                'public_display_name' => 'https://example.com',
            ])
            ->assertSessionHasErrors(['public_display_name' => "Display name can't include links or HTML."], null, 'displayNamePrompt');

        $this->actingAs($user)
            ->patch(route('profile.display-name.update'), [
                'public_display_name' => str_repeat('A', 41),
            ])
            ->assertSessionHasErrors(['public_display_name' => 'Display name must be 40 characters or fewer.'], null, 'displayNamePrompt');
    }

    public function test_public_attribution_uses_display_name_updated_from_prompt(): void
    {
        $creator = Creator::factory()->create(['slug' => 'jfragment']);
        $user = User::factory()->create([
            'public_display_name' => 'Guide',
            'public_handle' => 'steadyguide',
            'public_profile_completed_at' => now(),
        ]);

        Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'submitted_by' => $user->id,
            'title' => 'Guide name attribution request',
            'status' => 'approved',
        ]);

        $this->actingAs($user)
            ->patch(route('profile.display-name.update'), [
                'public_display_name' => 'Cher Ree',
            ])
            ->assertSessionHasNoErrors();

        $this->actingAs($user)
            ->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Suggested by Cher Ree')
            ->assertDontSee('Suggested by Guide');
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
