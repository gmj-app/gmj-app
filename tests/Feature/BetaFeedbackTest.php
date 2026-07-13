<?php

namespace Tests\Feature;

use App\Mail\BetaFeedbackSubmitted;
use App\Models\BetaFeedback;
use App\Models\SuperAdminAuditLog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Mail;
use RuntimeException;
use Tests\TestCase;

class BetaFeedbackTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        File::delete(storage_path('framework/testing/changelog.json'));
        parent::tearDown();
    }

    private function useTestChangelogPath(): string
    {
        $path = storage_path('framework/testing/changelog.json');
        config(['changelog.path' => $path]);

        return $path;
    }

    public function test_feedback_button_renders_when_enabled(): void
    {
        config(['gmj.beta_feedback_enabled' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Testing Feedback')
            ->assertSee('Tell us what happened. This form automatically includes the page and browser details so you do not have to.');
    }

    public function test_admin_feedback_viewer_sees_inbox_modal_instead_of_submit_form(): void
    {
        config([
            'gmj.beta_feedback_enabled' => true,
            'gmj.admin_emails' => ['jfragment@gmail.com'],
        ]);

        $admin = User::factory()->create([
            'name' => 'Jason Admin',
            'email' => 'jfragment@gmail.com',
            'avatar_url' => 'https://example.test/avatar.jpg',
        ]);
        $older = BetaFeedback::query()->create([
            'name' => 'Older Tester',
            'email' => 'older@example.test',
            'type' => 'Bug',
            'message' => 'Older feedback message.',
            'current_url' => 'https://www.guidemyjourney.org/older',
            'created_at' => now()->subHour(),
            'updated_at' => now()->subHour(),
        ]);
        $newer = BetaFeedback::query()->create([
            'user_id' => $admin->id,
            'name' => 'Jason Admin',
            'email' => 'jfragment@gmail.com',
            'type' => 'Confusing UX',
            'message' => 'Newest feedback message.',
            'extra_context' => 'Screenshot link.',
            'current_url' => 'https://www.guidemyjourney.org/newer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get('/')
            ->assertOk()
            ->assertSee('Testing Feedback Inbox')
            ->assertSee('Latest beta feedback from testers.')
            ->assertSee('Open testing feedback inbox')
            ->assertSee('Newest feedback message.')
            ->assertSee('Older feedback message.')
            ->assertSeeInOrder(['Newest feedback message.', 'Older feedback message.'])
            ->assertSee('https://example.test/avatar.jpg', false)
            ->assertSee('Screenshot link.')
            ->assertSee('Unread')
            ->assertSee('Mark read')
            ->assertSee('Mark as spam')
            ->assertSee('Spam (')
            ->assertSee('Showing latest 25')
            ->assertSee('Feedback Inbox')
            ->assertSee('Change Log')
            ->assertSee("activeTab: 'inbox'", false)
            ->assertDontSee('Tell us what happened. This form automatically includes the page and browser details so you do not have to.');

        $this->assertNull($older->read_at);
        $this->assertNull($newer->read_at);
    }

    public function test_tester_defaults_to_send_feedback_and_can_open_changelog_tab(): void
    {
        config(['gmj.beta_feedback_enabled' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Send Feedback')
            ->assertSee('Change Log')
            ->assertSee("activeTab: 'feedback'", false)
            ->assertSee("x-on:click=\"activeTab = 'changelog'\"", false)
            ->assertSee('role="tablist"', false)
            ->assertSee('role="tabpanel"', false);
    }

    public function test_missing_changelog_has_a_safe_unavailable_state(): void
    {
        config(['gmj.beta_feedback_enabled' => true]);
        $this->useTestChangelogPath();

        $this->get('/')
            ->assertOk()
            ->assertSee('Change log is temporarily unavailable.')
            ->assertDontSee(storage_path(), false);
    }

    public function test_valid_changelog_is_sorted_escaped_and_does_not_render_sensitive_fields(): void
    {
        config(['gmj.beta_feedback_enabled' => true]);
        $path = $this->useTestChangelogPath();
        File::ensureDirectoryExists(dirname($path));
        File::put($path, json_encode([
            ['hash' => 'old1234', 'date' => '2026-07-10T10:00:00Z', 'subject' => 'Older public update', 'author_email' => 'secret@example.test'],
            ['hash' => 'new1234', 'date' => '2026-07-11T10:00:00Z', 'subject' => '<script>alert("x")</script> New update', 'author_email' => 'private@example.test'],
        ], JSON_THROW_ON_ERROR));

        $response = $this->get('/')->assertOk();

        $response
            ->assertSeeInOrder(['Jul 11, 2026', '&lt;script&gt;alert(&quot;x&quot;)&lt;/script&gt; New update', 'Jul 10, 2026', 'Older public update'], false)
            ->assertDontSee('<script>alert', false)
            ->assertDontSee('secret@example.test')
            ->assertDontSee('private@example.test')
            ->assertDontSee('new1234');
    }

    public function test_admin_feedback_inbox_routes_are_hidden_from_normal_users(): void
    {
        config([
            'gmj.beta_feedback_enabled' => true,
            'gmj.admin_emails' => ['admin@example.test'],
        ]);

        $feedback = BetaFeedback::query()->create([
            'name' => 'Beta Tester',
            'email' => 'tester@example.test',
            'type' => 'Bug',
            'message' => 'A private note.',
        ]);

        $this->actingAs(User::factory()->create(['email' => 'guide@example.test']))
            ->get(route('internal.beta-feedback.index'))
            ->assertNotFound();

        $this->postJson(route('internal.beta-feedback.mark-read', $feedback))
            ->assertNotFound();

        auth()->logout();

        $this->get(route('internal.beta-feedback.index'))
            ->assertNotFound();
    }

    public function test_admin_feedback_inbox_routes_are_unavailable_when_beta_feedback_is_disabled(): void
    {
        config([
            'gmj.beta_feedback_enabled' => false,
            'gmj.admin_emails' => ['admin@example.test'],
        ]);

        $admin = User::factory()->create(['email' => 'admin@example.test']);

        $this->actingAs($admin)
            ->get(route('internal.beta-feedback.index'))
            ->assertNotFound();
    }

    public function test_admin_can_fetch_latest_feedback_and_mark_it_read(): void
    {
        config([
            'gmj.beta_feedback_enabled' => true,
            'gmj.admin_emails' => ['admin@example.test'],
        ]);

        $admin = User::factory()->create([
            'name' => 'Admin Tester',
            'email' => 'admin@example.test',
        ]);
        $older = BetaFeedback::query()->create([
            'name' => 'Older Tester',
            'email' => 'older@example.test',
            'type' => 'Other',
            'message' => 'Older message.',
            'created_at' => now()->subDay(),
            'updated_at' => now()->subDay(),
        ]);
        $newer = BetaFeedback::query()->create([
            'name' => 'Newer Tester',
            'email' => 'newer@example.test',
            'type' => 'Bug',
            'message' => 'Newer message.',
            'current_url' => 'https://www.guidemyjourney.org/newer',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->getJson(route('internal.beta-feedback.index'))
            ->assertOk()
            ->assertJsonPath('unread_count', 2)
            ->assertJsonPath('feedback.0.id', $newer->id)
            ->assertJsonPath('feedback.1.id', $older->id)
            ->assertJsonPath('feedback.0.message', 'Newer message.');

        $this->postJson(route('internal.beta-feedback.mark-read', $newer))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('read_by', 'Admin Tester')
            ->assertJsonPath('unread_count', 1);

        $this->assertNotNull($newer->fresh()->read_at);
        $this->assertSame($admin->id, $newer->fresh()->read_by_user_id);
    }

    public function test_admin_can_mark_feedback_as_spam_and_it_leaves_inbox_and_unread_count(): void
    {
        config(['gmj.beta_feedback_enabled' => true, 'gmj.admin_emails' => ['admin@example.test']]);
        $admin = User::factory()->create(['name' => 'Spam Moderator', 'email' => 'admin@example.test']);
        $spam = BetaFeedback::query()->create(['name' => 'Sales Bot', 'email' => 'sales@example.test', 'type' => 'Other', 'message' => 'Buy our service.']);
        $legitimate = BetaFeedback::query()->create(['name' => 'Real Tester', 'email' => 'real@example.test', 'type' => 'Bug', 'message' => 'A real bug.']);

        $this->actingAs($admin)->postJson(route('internal.beta-feedback.spam', $spam), ['spam_reason' => 'unsolicited_sales'])
            ->assertOk()->assertJsonPath('unread_count', 1)->assertJsonPath('message', 'Feedback marked as spam.');

        $spam->refresh();
        $this->assertTrue($spam->isSpam());
        $this->assertSame($admin->id, $spam->spam_by_user_id);
        $this->assertSame('unsolicited_sales', $spam->spam_reason);
        $this->assertNull($spam->read_at);
        $this->getJson(route('internal.beta-feedback.index'))->assertOk()
            ->assertJsonMissing(['id' => $spam->id])->assertJsonFragment(['id' => $legitimate->id])->assertJsonPath('unread_count', 1);
        $this->assertDatabaseHas('beta_feedback', ['id' => $spam->id]);
        $this->assertDatabaseHas('super_admin_audit_logs', ['action' => 'beta_feedback.marked_spam', 'auditable_id' => $spam->id]);
    }

    public function test_spam_can_be_reviewed_and_restored_without_changing_read_state(): void
    {
        config(['gmj.beta_feedback_enabled' => true, 'gmj.admin_emails' => ['admin@example.test']]);
        $admin = User::factory()->create(['email' => 'admin@example.test']);
        $readAt = now()->subHour();
        $feedback = BetaFeedback::query()->create(['name' => 'Gemma Marshall', 'email' => 'gemma@example.test', 'type' => 'Bug', 'message' => 'Review me.', 'read_at' => $readAt, 'spam_at' => now(), 'spam_by_user_id' => $admin->id]);

        $this->actingAs($admin)->get('/')->assertOk()->assertSee('Review me.')->assertSee('Restore to inbox')->assertSee('Marked');
        $this->postJson(route('internal.beta-feedback.restore', $feedback))->assertOk()->assertJsonPath('message', 'Feedback restored to inbox.');

        $feedback->refresh();
        $this->assertFalse($feedback->isSpam());
        $this->assertSame($readAt->format('Y-m-d H:i:s'), $feedback->read_at->format('Y-m-d H:i:s'));
        $this->getJson(route('internal.beta-feedback.index'))->assertJsonFragment(['id' => $feedback->id]);
        $this->assertDatabaseHas('super_admin_audit_logs', ['action' => 'beta_feedback.restored_from_spam', 'auditable_id' => $feedback->id]);
    }

    public function test_spam_actions_are_idempotent_and_hidden_from_non_admins(): void
    {
        config(['gmj.beta_feedback_enabled' => true, 'gmj.admin_emails' => ['admin@example.test']]);
        $admin = User::factory()->create(['email' => 'admin@example.test']);
        $feedback = BetaFeedback::query()->create(['name' => 'Tester', 'type' => 'Other', 'message' => 'Message']);

        $this->postJson(route('internal.beta-feedback.spam', $feedback))->assertNotFound();
        $this->actingAs(User::factory()->create())->postJson(route('internal.beta-feedback.spam', $feedback))->assertNotFound();
        $this->actingAs($admin)->postJson(route('internal.beta-feedback.spam', $feedback))->assertOk();
        $firstSpamAt = $feedback->fresh()->spam_at;
        $this->postJson(route('internal.beta-feedback.spam', $feedback))->assertOk();
        $this->assertTrue($feedback->fresh()->spam_at->equalTo($firstSpamAt));
        $this->assertSame(1, SuperAdminAuditLog::query()->where('action', 'beta_feedback.marked_spam')->count());

        $this->postJson(route('internal.beta-feedback.restore', $feedback))->assertOk();
        $this->postJson(route('internal.beta-feedback.restore', $feedback))->assertOk();
        $this->assertSame(1, SuperAdminAuditLog::query()->where('action', 'beta_feedback.restored_from_spam')->count());
    }

    public function test_feedback_modal_uses_direct_open_state_and_posts_to_feedback_route(): void
    {
        config(['gmj.beta_feedback_enabled' => true]);

        $this->get('/')
            ->assertOk()
            ->assertSee('type="button"', false)
            ->assertSee('x-on:click="openModal()"', false)
            ->assertSee('x-show="open"', false)
            ->assertSee('role="dialog"', false)
            ->assertSee('aria-labelledby="beta-feedback-title"', false)
            ->assertSee('method="POST"', false)
            ->assertSee('action="'.route('beta-feedback.store').'"', false)
            ->assertSee('x-on:keydown.escape.window="open ? closeModal() : null"', false)
            ->assertDontSee('$dispatch(&#039;open-modal&#039;, &#039;beta-feedback&#039;)', false)
            ->assertDontSee('data-modal-root="beta-feedback"', false);
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
            'public_display_name' => 'Public Tester',
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
            'name' => 'Public Tester',
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
