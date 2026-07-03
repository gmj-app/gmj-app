<?php

namespace Tests\Feature;

use App\Mail\BetaFeedbackSubmitted;
use App\Models\BetaFeedback;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class BetaFeedbackTest extends TestCase
{
    use RefreshDatabase;

    public function test_feedback_button_renders_when_enabled(): void
    {
        config(['gmj.beta_feedback_enabled' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Testing Feedback')
            ->assertSee('Tell us what happened. This form automatically includes the page and browser details so you do not have to.');
    }

    public function test_feedback_button_is_hidden_when_disabled(): void
    {
        config(['gmj.beta_feedback_enabled' => false]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Testing Feedback');
    }

    public function test_feedback_endpoint_is_unavailable_when_disabled(): void
    {
        config(['gmj.beta_feedback_enabled' => false]);

        $this->postJson(route('beta-feedback.store'), [
            'name' => 'Beta Tester',
            'email' => 'tester@example.com',
            'type' => 'Bug',
            'message' => 'Something happened.',
        ])->assertNotFound();
    }

    public function test_guest_feedback_requires_name_and_email(): void
    {
        config(['gmj.beta_feedback_enabled' => true]);

        $this->postJson(route('beta-feedback.store'), [
            'type' => 'Bug',
            'message' => 'Something happened.',
        ])->assertJsonValidationErrors(['name', 'email']);
    }

    public function test_guest_can_submit_beta_feedback_with_browser_context(): void
    {
        config(['gmj.beta_feedback_enabled' => true]);
        Mail::fake();

        $this->postJson(route('beta-feedback.store'), [
            'name' => 'Beta Tester',
            'email' => 'tester@example.com',
            'type' => 'Confusing UX',
            'message' => 'The button was hard to find.',
            'extra_context' => 'Screenshot: https://example.com/screenshot.png',
            'current_url' => 'https://www.guidemyjourney.org/jfragment',
            'user_agent' => 'Mozilla/5.0 Test Browser',
            'platform' => 'Win32',
            'timezone' => 'America/New_York',
            'app_environment' => 'production',
            'viewport_width' => 1440,
            'viewport_height' => 900,
            'screen_width' => 1920,
            'screen_height' => 1080,
            'meta' => json_encode([
                'language' => 'en-US',
                'languages' => ['en-US', 'en'],
                'devicePixelRatio' => 1,
                'referrer' => 'https://www.guidemyjourney.org/',
            ]),
        ])
            ->assertOk()
            ->assertJson([
                'message' => 'Thanks, your feedback was sent.',
            ]);

        $feedback = BetaFeedback::query()->firstOrFail();

        $this->assertSame('Beta Tester', $feedback->name);
        $this->assertSame('tester@example.com', $feedback->email);
        $this->assertSame('Confusing UX', $feedback->type);
        $this->assertSame('https://www.guidemyjourney.org/jfragment', $feedback->current_url);
        $this->assertSame('Mozilla/5.0 Test Browser', $feedback->user_agent);
        $this->assertSame('Win32', $feedback->platform);
        $this->assertSame('America/New_York', $feedback->timezone);
        $this->assertSame('production', $feedback->app_environment);
        $this->assertSame(1440, $feedback->viewport_width);
        $this->assertSame('en-US', $feedback->meta['language']);
    }

    public function test_feedback_submission_emails_the_configured_recipient(): void
    {
        config([
            'gmj.beta_feedback_enabled' => true,
            'gmj.beta_feedback_email' => 'discoveringfilipinomusic@gmail.com',
        ]);
        Mail::fake();

        $this->postJson(route('beta-feedback.store'), [
            'name' => 'Beta Tester',
            'email' => 'tester@example.com',
            'type' => 'Bug',
            'message' => 'The page went blank.',
            'current_url' => 'https://www.guidemyjourney.org/jfragment',
            'user_agent' => 'Mozilla/5.0 Test Browser',
            'platform' => 'MacIntel',
            'timezone' => 'America/New_York',
            'viewport_width' => 390,
            'viewport_height' => 844,
            'screen_width' => 390,
            'screen_height' => 844,
        ])->assertOk();

        Mail::assertSent(BetaFeedbackSubmitted::class, function (BetaFeedbackSubmitted $mail): bool {
            return $mail->hasTo('discoveringfilipinomusic@gmail.com')
                && $mail->feedback->type === 'Bug'
                && $mail->feedback->message === 'The page went blank.';
        });
    }

    public function test_feedback_submission_still_succeeds_when_email_fails(): void
    {
        config([
            'gmj.beta_feedback_enabled' => true,
            'gmj.beta_feedback_email' => 'discoveringfilipinomusic@gmail.com',
        ]);

        Mail::shouldReceive('to')
            ->once()
            ->with('discoveringfilipinomusic@gmail.com')
            ->andThrow(new RuntimeException('SMTP unavailable'));

        $this->postJson(route('beta-feedback.store'), [
            'name' => 'Beta Tester',
            'email' => 'tester@example.com',
            'type' => 'Other',
            'message' => 'Mail is down but this should save.',
        ])
            ->assertOk()
            ->assertJson([
                'message' => 'Thanks, your feedback was sent.',
            ]);

        $this->assertDatabaseHas('beta_feedback', [
            'name' => 'Beta Tester',
            'email' => 'tester@example.com',
            'type' => 'Other',
            'message' => 'Mail is down but this should save.',
        ]);
    }

    public function test_feedback_email_contains_the_useful_submission_details(): void
    {
        $feedback = BetaFeedback::query()->create([
            'name' => 'Beta Tester',
            'email' => 'tester@example.com',
            'type' => 'Content/data issue',
            'message' => 'The channel name looked wrong.',
            'extra_context' => 'Screenshot link here.',
            'current_url' => 'https://www.guidemyjourney.org/jfragment',
            'user_agent' => 'Mozilla/5.0 Test Browser',
            'platform' => 'Win32',
            'timezone' => 'America/New_York',
            'viewport_width' => 1440,
            'viewport_height' => 900,
            'screen_width' => 1920,
            'screen_height' => 1080,
        ]);

        $body = (new BetaFeedbackSubmitted($feedback))->render();

        $this->assertStringContainsString('New Guide My Journey testing feedback', $body);
        $this->assertStringContainsString('Feedback type: Content/data issue', $body);
        $this->assertStringContainsString('Name: Beta Tester', $body);
        $this->assertStringContainsString('Email: tester@example.com', $body);
        $this->assertStringContainsString('Current page URL: https://www.guidemyjourney.org/jfragment', $body);
        $this->assertStringContainsString('The channel name looked wrong.', $body);
        $this->assertStringContainsString('Mozilla/5.0 Test Browser', $body);
        $this->assertStringContainsString('Viewport size: 1440 x 900', $body);
        $this->assertStringContainsString('Screen size: 1920 x 1080', $body);
    }

    public function test_authenticated_feedback_defaults_to_user_name_and_email(): void
    {
        config(['gmj.beta_feedback_enabled' => true]);
        Mail::fake();

        $user = User::factory()->create([
            'name' => 'Logged In Tester',
            'email' => 'logged-in@example.com',
        ]);

        $this->actingAs($user)
            ->postJson(route('beta-feedback.store'), [
                'name' => '',
                'email' => '',
                'type' => 'Bug',
                'message' => 'The submit page got stuck.',
            ])
            ->assertOk();

        $this->assertDatabaseHas('beta_feedback', [
            'user_id' => $user->id,
            'name' => 'Logged In Tester',
            'email' => 'logged-in@example.com',
            'type' => 'Bug',
            'message' => 'The submit page got stuck.',
        ]);
    }

    public function test_feedback_validates_type_and_message(): void
    {
        config(['gmj.beta_feedback_enabled' => true]);

        $this->actingAs(User::factory()->create())
            ->postJson(route('beta-feedback.store'), [
                'type' => 'Support ticket',
                'message' => '',
            ])
            ->assertJsonValidationErrors(['type', 'message']);
    }
}
