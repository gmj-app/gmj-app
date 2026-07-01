<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class EmailVerificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_email_verification_prompt_redirects_and_marks_user_verified_for_google_only_mvp(): void
    {
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->get('/verify-email')
            ->assertRedirect(route('dashboard', absolute: false));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_email_verification_notification_is_not_sent(): void
    {
        Notification::fake();
        $user = User::factory()->unverified()->create();

        $this->actingAs($user)
            ->post('/email/verification-notification')
            ->assertRedirect(route('dashboard'));

        Notification::assertNothingSent();
        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }

    public function test_signed_email_verification_route_redirects_without_email_flow(): void
    {
        $user = User::factory()->unverified()->create();

        $verificationUrl = URL::temporarySignedRoute(
            'verification.verify',
            now()->addMinutes(60),
            ['id' => $user->id, 'hash' => sha1('wrong-email')]
        );

        $this->actingAs($user)
            ->get($verificationUrl)
            ->assertRedirect(route('dashboard'));

        $this->assertTrue($user->fresh()->hasVerifiedEmail());
    }
}
