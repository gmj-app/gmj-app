<?php

namespace Tests\Feature;

use App\Models\User;
use App\Notifications\BaseDatabaseNotification;
use App\Presenters\NotificationPresenter;
use App\Services\NotificationDispatchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationSystemTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_model_uses_laravel_notifiable(): void
    {
        $this->assertContains(Notifiable::class, class_uses_recursive(User::class));
    }

    public function test_notification_pages_require_authentication(): void
    {
        $this->get(route('notifications.index'))->assertRedirect(route('login'));
        $this->post(route('notifications.read-all'))->assertRedirect(route('login'));
        $this->get(route('super-admin.notifications.test'))->assertRedirect(route('login'));
    }

    public function test_dispatch_stores_normalized_payload_and_deduplicates_per_recipient(): void
    {
        $first = User::factory()->create();
        $second = User::factory()->create();
        $dispatch = app(NotificationDispatchService::class);
        $notification = fn () => new BaseDatabaseNotification(
            key: 'request.42:created',
            title: '<b>Request received</b>',
            message: '<script>alert(1)</script>Your request is ready.',
            category: 'request',
            audience: 'guide',
            actionUrl: '/notifications',
            actionLabel: 'View request',
            icon: 'list-check',
            severity: 'success',
            context: ['request_id' => 42, 'metadata' => ['source' => 'test']],
        );

        $this->assertTrue($dispatch->send($first, $notification()));
        $this->assertFalse($dispatch->send($first, $notification()));
        $this->assertTrue($dispatch->send($second, $notification()));

        $this->assertCount(1, $first->notifications);
        $stored = $first->notifications()->firstOrFail();
        $this->assertSame('request.42:created', $stored->deduplication_key);
        $this->assertSame(1, $stored->data['schema_version']);
        $this->assertSame('Request received', $stored->data['title']);
        $this->assertSame('alert(1)Your request is ready.', $stored->data['message']);
        $this->assertSame('/notifications', $stored->data['action_url']);
        $this->assertSame(42, $stored->data['request_id']);
    }

    public function test_unsafe_action_urls_fall_back_to_the_notification_center(): void
    {
        $user = User::factory()->create();
        app(NotificationDispatchService::class)->send($user, new BaseDatabaseNotification(
            key: 'system.unsafe:1',
            title: 'Unsafe URL test',
            message: 'External redirects are not allowed.',
            actionUrl: 'https://example.com/phishing',
        ));

        $notification = $user->notifications()->firstOrFail();
        $this->assertSame('/notifications', $notification->data['action_url']);

        $this->actingAs($user)
            ->get(route('notifications.open', $notification))
            ->assertRedirect('/notifications');
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_invalid_payload_values_receive_safe_defaults(): void
    {
        $payload = (new BaseDatabaseNotification(
            key: '',
            title: '',
            message: '',
            category: 'unknown',
            audience: 'unknown',
            actionUrl: 'javascript:alert(1)',
            icon: 'arbitrary-class',
            severity: 'extreme',
        ))->toArray(User::factory()->make());

        $this->assertSame('Notification', $payload['title']);
        $this->assertSame('You have a new update.', $payload['message']);
        $this->assertSame('system', $payload['category']);
        $this->assertSame('all', $payload['audience']);
        $this->assertSame('bell', $payload['icon']);
        $this->assertSame('info', $payload['severity']);
        $this->assertSame('/notifications', $payload['action_url']);
        $this->assertStringStartsWith('system.notification:', $payload['notification_key']);
    }

    public function test_users_can_only_change_their_own_notifications(): void
    {
        $owner = User::factory()->create();
        $other = User::factory()->create();
        $notification = $this->rawNotification($owner, ['title' => 'Private update']);

        $this->actingAs($other)->post(route('notifications.read', $notification))->assertNotFound();
        $this->assertNull($notification->fresh()->read_at);

        $this->actingAs($owner)->post(route('notifications.read', $notification))->assertRedirect();
        $this->assertNotNull($notification->fresh()->read_at);

        $this->actingAs($owner)->post(route('notifications.unread', $notification))->assertRedirect();
        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_mark_all_read_is_scoped_to_the_signed_in_user(): void
    {
        $user = User::factory()->create();
        $other = User::factory()->create();
        $own = $this->rawNotification($user);
        $theirs = $this->rawNotification($other);

        $this->actingAs($user)->post(route('notifications.read-all'))->assertRedirect();

        $this->assertNotNull($own->fresh()->read_at);
        $this->assertNull($theirs->fresh()->read_at);
    }

    public function test_notification_center_filters_and_renders_malformed_legacy_payloads_safely(): void
    {
        $user = User::factory()->create();
        $requestNotification = $this->rawNotification($user, ['title' => 'Request update', 'category' => 'request']);
        $this->rawNotification($user, ['title' => 'System update', 'category' => 'system'], now());
        $legacy = $this->rawNotification($user, ['title' => [], 'message' => null, 'icon' => 'unknown', 'action_url' => '//evil.example']);

        $presenter = new NotificationPresenter($legacy);
        $this->assertSame('Notification', $presenter->title());
        $this->assertSame('bell', $presenter->icon());
        $this->assertSame('/notifications', $presenter->actionUrl());

        $unreadResponse = $this->actingAs($user)->get(route('notifications.index', ['filter' => 'unread']));
        $unreadResponse->assertOk();
        $this->assertSame(
            ['Notification', 'Request update'],
            $unreadResponse->viewData('notifications')->getCollection()->map->title()->all(),
        );

        $categoryResponse = $this->actingAs($user)->get(route('notifications.index', ['category' => 'request']));
        $categoryResponse->assertOk();
        $this->assertSame(['Request update'], $categoryResponse->viewData('notifications')->getCollection()->map->title()->all());
        $this->actingAs($user)->get(route('notifications.open', $requestNotification))->assertRedirect('/notifications');
    }

    public function test_header_caps_unread_count_and_only_loads_ten_recent_items(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 101) as $number) {
            $this->rawNotification($user, ['title' => "Bell item {$number}"]);
        }

        $response = $this->actingAs($user)->get(route('home'));

        $response->assertOk()->assertSee('99+')->assertSee('Bell item 101');
        $this->assertSame(10, substr_count($response->getContent(), 'class="block border-b border-slate-100'));
    }

    public function test_full_notification_page_paginates_twenty_five_at_a_time_and_has_an_empty_state(): void
    {
        $user = User::factory()->create();
        foreach (range(1, 26) as $number) {
            $this->rawNotification($user, ['title' => "Page item {$number}"]);
        }

        $response = $this->actingAs($user)->get(route('notifications.index'));
        $response->assertOk();
        $this->assertCount(25, $response->viewData('notifications'));
        $this->assertTrue($response->viewData('notifications')->hasMorePages());

        $emptyUser = User::factory()->create();
        $this->actingAs($emptyUser)->get(route('notifications.index'))
            ->assertOk()->assertSee('No notifications yet.');
    }

    public function test_super_admin_can_search_and_send_a_test_notification_with_audit_log(): void
    {
        config(['super_admin.emails' => ['admin@example.com']]);
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $recipient = User::factory()->create(['name' => 'Searchable Recipient', 'email' => 'recipient@example.com', 'google_id' => 'private-oauth-identifier']);
        $nonAdmin = User::factory()->create();

        $this->actingAs($nonAdmin)->get(route('super-admin.notifications.test'))->assertForbidden();
        $this->actingAs($admin)->get(route('super-admin.notifications.test', ['q' => 'Searchable']))
            ->assertOk()->assertSee('recipient@example.com')->assertDontSee('private-oauth-identifier');

        $payload = [
            'recipient_id' => $recipient->id,
            'category' => 'system',
            'audience' => 'all',
            'title' => 'Admin test',
            'message' => 'Delivery test',
            'action_url' => '/notifications',
            'action_label' => 'Open',
            'icon' => 'bell',
            'severity' => 'info',
            'deduplication_key' => 'system.admin-test:recipient-'.$recipient->id,
        ];

        $this->actingAs($admin)->post(route('super-admin.notifications.test.store'), $payload)
            ->assertSessionHasNoErrors()->assertSessionHas('success', 'Test notification sent.');
        $this->actingAs($admin)->post(route('super-admin.notifications.test.store'), $payload)
            ->assertSessionHas('success', 'Duplicate notification was not sent.');

        $this->assertCount(1, $recipient->notifications);
        $this->assertDatabaseHas('super_admin_audit_logs', [
            'admin_user_id' => $admin->id,
            'action' => 'notification.test_sent',
            'auditable_id' => $recipient->id,
        ]);
    }

    public function test_super_admin_tool_rejects_external_action_urls(): void
    {
        config(['super_admin.emails' => ['admin@example.com']]);
        $admin = User::factory()->create(['email' => 'admin@example.com']);
        $recipient = User::factory()->create();

        $this->actingAs($admin)->post(route('super-admin.notifications.test.store'), [
            'recipient_id' => $recipient->id,
            'category' => 'system',
            'audience' => 'all',
            'title' => 'Unsafe',
            'message' => 'Unsafe redirect',
            'action_url' => '//evil.example',
            'icon' => 'bell',
            'severity' => 'warning',
        ])->assertSessionHasErrors('action_url');

        $this->assertCount(0, $recipient->notifications);
    }

    private function rawNotification(User $user, array $data = [], $readAt = null): DatabaseNotification
    {
        return $user->notifications()->create([
            'id' => Str::uuid()->toString(),
            'type' => BaseDatabaseNotification::class,
            'deduplication_key' => 'raw:'.Str::uuid(),
            'data' => array_merge([
                'schema_version' => 1,
                'notification_key' => 'raw:'.Str::uuid(),
                'category' => 'system',
                'audience' => 'all',
                'title' => 'Raw notification',
                'message' => 'Raw notification message.',
                'action_url' => '/notifications',
                'icon' => 'bell',
                'severity' => 'info',
            ], $data),
            'read_at' => $readAt,
        ]);
    }
}
