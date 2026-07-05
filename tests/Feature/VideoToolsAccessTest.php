<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\YoutubeChannelToken;
use App\Services\Youtube\YoutubeApiClient;
use App\Services\Youtube\YoutubeVideoSnippet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VideoToolsAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_normal_user_cannot_access_internal_video_tools(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('tools.admin'))
            ->assertForbidden();
    }

    public function test_approved_user_can_access_internal_video_tools(): void
    {
        $user = User::factory()->create([
            'can_access_video_tools' => true,
        ]);

        $this->actingAs($user)
            ->get(route('tools.admin'))
            ->assertOk()
            ->assertSee('Creator operations')
            ->assertSee(route('tools.youtube.index'), false);
    }

    public function test_youtube_tool_is_marked_disabled_unless_enabled(): void
    {
        config(['services.youtube.enabled' => false]);

        $user = User::factory()->create([
            'can_access_video_tools' => true,
        ]);

        $this->actingAs($user)
            ->get(route('tools.youtube.index'))
            ->assertOk()
            ->assertSee('YouTube API tools are disabled');

        $this->post(route('tools.youtube.preview'), [
            'append_text' => 'https://facebook.com/jfragment',
            'append_only_if_missing' => '1',
            'add_separator' => '1',
        ])->assertForbidden();
    }

    public function test_apply_stores_backups_logs_updates_and_preserves_snippet_fields(): void
    {
        config(['services.youtube.enabled' => true]);

        $user = User::factory()->create([
            'can_access_video_tools' => true,
        ]);

        YoutubeChannelToken::query()->create([
            'user_id' => $user->id,
            'access_token' => 'access-token',
            'refresh_token' => 'refresh-token',
            'expires_at' => now()->addHour(),
        ]);

        $fakeClient = new class extends YoutubeApiClient
        {
            /** @var array<int, array<string, mixed>> */
            public array $updates = [];

            public function videoSnippet(YoutubeChannelToken $token, string $videoId): YoutubeVideoSnippet
            {
                return new YoutubeVideoSnippet($videoId, [
                    'title' => 'Existing YouTube title',
                    'description' => 'Old description',
                    'categoryId' => '10',
                    'tags' => ['keep-this-tag'],
                    'defaultLanguage' => 'en',
                ]);
            }

            public function updateDescription(YoutubeChannelToken $token, string $videoId, array $snippet, string $description): void
            {
                $this->updates[] = compact('videoId', 'snippet', 'description');
            }
        };

        $this->app->instance(YoutubeApiClient::class, $fakeClient);

        $this->actingAs($user)
            ->withSession([
                'youtube_tools.batch_id' => 'batch-123',
                'youtube_tools.preview' => [[
                    'video_id' => 'video-123',
                    'video_title' => 'Existing YouTube title',
                    'old_description' => 'Old description',
                    'new_description' => "Old description\n\n---\nFacebook: https://facebook.com/jfragment",
                    'action' => 'append',
                    'status' => 'changed',
                    'message' => null,
                ]],
            ])
            ->post(route('tools.youtube.apply'), [
                'confirm_bulk_update' => '1',
            ])
            ->assertRedirect(route('tools.youtube.index'));

        $this->assertDatabaseHas('youtube_description_backups', [
            'user_id' => $user->id,
            'video_id' => 'video-123',
            'operation_batch_id' => 'batch-123',
            'original_description' => 'Old description',
        ]);

        $this->assertDatabaseHas('video_tool_audit_logs', [
            'user_id' => $user->id,
            'video_id' => 'video-123',
            'operation_batch_id' => 'batch-123',
            'action' => 'append',
            'status' => 'updated',
        ]);

        $this->assertSame('Existing YouTube title', $fakeClient->updates[0]['snippet']['title']);
        $this->assertSame('10', $fakeClient->updates[0]['snippet']['categoryId']);
        $this->assertSame(['keep-this-tag'], $fakeClient->updates[0]['snippet']['tags']);
        $this->assertSame('en', $fakeClient->updates[0]['snippet']['defaultLanguage']);
        $this->assertStringContainsString('https://facebook.com/jfragment', $fakeClient->updates[0]['description']);
    }
}
