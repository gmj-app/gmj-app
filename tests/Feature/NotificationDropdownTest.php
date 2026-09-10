<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\NotificationReadService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class NotificationDropdownTest extends TestCase
{
    use RefreshDatabase;

    private function notification(User $user, $readAt = null)
    {
        return $user->notifications()->create([
            'id' => (string) Str::uuid(), 'type' => 'test',
            'data' => ['title' => 'Update', 'message' => 'New update'], 'read_at' => $readAt,
        ]);
    }

    public function test_open_acknowledges_entire_inbox_only_for_authenticated_owner(): void
    {
        $user = User::factory()->create();
        $other = $this->notification(User::factory()->create());
        $read = $this->notification($user, now()->subDay());
        $timestamp = $read->read_at;
        for ($i = 0; $i < 15; $i++) {
            $this->notification($user);
        }
        $this->actingAs($user)->getJson(route('notifications.dropdown'))->assertOk()->assertJsonPath('unread_count', 15);
        $this->assertSame(15, $user->unreadNotifications()->count());
        $this->postJson(route('notifications.acknowledge-open'))->assertOk()->assertJsonPath('unread_count', 0);
        $this->assertSame(0, $user->unreadNotifications()->count());
        $this->assertNull($other->fresh()->read_at);
        $this->assertTrue($timestamp->equalTo($read->fresh()->read_at));
        $this->postJson(route('notifications.acknowledge-open'))->assertOk()->assertJsonPath('opened_unread_ids', []);
    }

    public function test_snapshot_excludes_later_arrivals_and_foreign_ids(): void
    {
        $user = User::factory()->create();
        $first = $this->notification($user);
        $other = $this->notification(User::factory()->create());
        $reads = app(NotificationReadService::class);
        $snapshot = $reads->unreadSnapshot($user);
        $later = $this->notification($user);
        $reads->acknowledge($user, $snapshot->push($other->id));
        $this->assertNotNull($first->fresh()->read_at);
        $this->assertNull($later->fresh()->read_at);
        $this->assertNull($other->fresh()->read_at);
        $this->actingAs($user)->getJson(route('notifications.dropdown'))->assertJsonPath('unread_count', 1);
        $this->assertNull($later->fresh()->read_at);
    }

    public function test_endpoints_require_authentication(): void
    {
        $this->getJson(route('notifications.dropdown'))->assertUnauthorized();
        $this->postJson(route('notifications.acknowledge-open'))->assertUnauthorized();
    }
}
