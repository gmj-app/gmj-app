<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorOwner;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorSetupTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_cannot_access_or_submit_creator_setup(): void
    {
        $this->get(route('creators.create'))
            ->assertRedirect('/login');

        $this->post(route('creators.store'), $this->validPayload())
            ->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_creator_setup_form(): void
    {
        $this->actingAs(User::factory()->create())
            ->get(route('creators.create'))
            ->assertOk()
            ->assertSee('Set up your creator page')
            ->assertSee('Creator pages are manually created during beta.')
            ->assertSee('Hold for review')
            ->assertSee('Auto-approve')
            ->assertSee('Accept new suggestions')
            ->assertSee('action="'.route('creators.store').'"', false);
    }

    public function test_user_can_create_a_manual_creator_page_and_become_its_owner(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)
            ->post(route('creators.store'), $this->validPayload());

        $creator = Creator::query()->where('slug', 'russell-reacts')->firstOrFail();

        $response
            ->assertRedirect(route('creators.starter-suggestions.create', $creator));

        $this->assertDatabaseHas('creators', [
            'id' => $creator->id,
            'display_name' => 'Russell Reacts',
            'slug' => 'russell-reacts',
            'channel_url' => 'https://www.youtube.com/@russellreacts',
            'youtube_channel_url' => 'https://www.youtube.com/@russellreacts',
            'bio' => 'Music, movies, and culture from around the world.',
            'submission_instructions' => 'Share thoughtful topics, videos, and links.',
            'recommendation_approval_mode' => Creator::APPROVAL_MODE_MANUAL,
            'submissions_open' => true,
            'verification_status' => 'unverified',
            'status' => 'active',
        ]);
        $this->assertDatabaseHas('creator_owners', [
            'creator_id' => $creator->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);
        $this->assertSame(10, $creator->creatorTags()->count());
        $this->assertDatabaseHas('creator_tags', [
            'creator_id' => $creator->id,
            'name' => 'Deep Dive',
            'slug' => 'deep-dive',
        ]);

        $this->get(route('creators.starter-suggestions.create', $creator))
            ->assertOk()
            ->assertSee('Seed your journey');

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('Russell Reacts')
            ->assertSee('Music, movies, and culture from around the world.')
            ->assertDontSee('Verified');

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Russell Reacts')
            ->assertSee(route('creator.queue', $creator), false);

        $this->get(route('creators.settings.edit', $creator))
            ->assertOk()
            ->assertSee('value="Russell Reacts"', false)
            ->assertSee('value="russell-reacts"', false)
            ->assertSee('value="https://www.youtube.com/@russellreacts"', false);

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSee('Manage creator page')
            ->assertSee(route('creators.dashboard', $creator), false)
            ->assertDontSee('Set up creator page');
    }

    public function test_auto_approval_and_closed_submissions_are_saved(): void
    {
        $user = User::factory()->create();
        $payload = $this->validPayload([
            'slug' => 'auto-creator',
            'recommendation_approval_mode' => Creator::APPROVAL_MODE_AUTO,
            'submissions_open' => '0',
        ]);

        $this->actingAs($user)
            ->post(route('creators.store'), $payload)
            ->assertRedirect();

        $this->assertDatabaseHas('creators', [
            'slug' => 'auto-creator',
            'recommendation_approval_mode' => Creator::APPROVAL_MODE_AUTO,
            'submissions_open' => false,
        ]);
    }

    public function test_creator_setup_validates_required_and_formatted_fields(): void
    {
        $user = User::factory()->create();
        Creator::factory()->create(['slug' => 'existing-creator']);

        $this->actingAs($user)
            ->from(route('creators.create'))
            ->post(route('creators.store'), [
                'display_name' => '',
                'slug' => 'Existing Creator',
                'youtube_channel_url' => 'not-a-url',
                'bio' => str_repeat('a', 2001),
                'submission_instructions' => str_repeat('b', 2001),
                'recommendation_approval_mode' => 'sometimes',
            ])
            ->assertRedirect(route('creators.create'))
            ->assertSessionHasErrors([
                'display_name',
                'slug',
                'youtube_channel_url',
                'bio',
                'submission_instructions',
                'recommendation_approval_mode',
                'submissions_open',
            ]);

        $this->assertDatabaseCount('creator_owners', 0);
    }

    public function test_creator_setup_rejects_a_duplicate_slug(): void
    {
        $user = User::factory()->create();
        Creator::factory()->create(['slug' => 'russell-reacts']);

        $this->actingAs($user)
            ->post(route('creators.store'), $this->validPayload())
            ->assertSessionHasErrors('slug');

        $this->assertDatabaseCount('creator_owners', 0);
    }

    public function test_existing_owner_can_create_multiple_creator_pages(): void
    {
        $user = User::factory()->create();
        $existingCreator = Creator::factory()->create();
        CreatorOwner::query()->create([
            'creator_id' => $existingCreator->id,
            'user_id' => $user->id,
            'role' => 'owner',
        ]);

        $this->actingAs($user)
            ->post(route('creators.store'), $this->validPayload())
            ->assertRedirect();

        $this->assertSame(2, $user->fresh()->ownedCreators()->count());

        $this->get(route('creators.index'))
            ->assertOk()
            ->assertSee('Set up another creator page')
            ->assertSee(route('creators.create'), false);
    }

    /**
     * @param  array<string, mixed>  $overrides
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return [
            'display_name' => 'Russell Reacts',
            'slug' => 'russell-reacts',
            'youtube_channel_url' => 'https://www.youtube.com/@russellreacts',
            'bio' => 'Music, movies, and culture from around the world.',
            'submission_instructions' => 'Share thoughtful topics, videos, and links.',
            'recommendation_approval_mode' => Creator::APPROVAL_MODE_MANUAL,
            'submissions_open' => '1',
            ...$overrides,
        ];
    }
}
