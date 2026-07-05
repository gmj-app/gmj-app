<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorOwner;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CreatorStarterSuggestionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_creator_owners_can_access_starter_suggestions(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();
        $nonOwner = User::factory()->create();

        $this->get(route('creators.starter-suggestions.create', $creator))
            ->assertRedirect('/login');

        $this->actingAs($nonOwner)
            ->get(route('creators.starter-suggestions.create', $creator))
            ->assertForbidden();

        $this->post(route('creators.starter-suggestions.store', $creator), [
            'suggestions' => [['title' => 'Not allowed']],
        ])->assertForbidden();

        $this->actingAs($owner)
            ->get(route('creators.starter-suggestions.create', $creator))
            ->assertOk()
            ->assertSee('Seed your journey')
            ->assertSee('Add up to 20 starter suggestions')
            ->assertSee('Add another suggestion')
            ->assertSee('Skip for now');
    }

    public function test_owner_can_add_approved_starter_suggestions_without_using_guide_resources(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();

        $this->actingAs($owner)
            ->post(route('creators.starter-suggestions.store', $creator), [
                'suggestions' => [
                    [
                        'title' => 'React to this live performance',
                        'url' => 'https://youtu.be/dQw4w9WgXcQ',
                        'category' => 'music',
                        'note' => 'A strong first community vote.',
                    ],
                    [
                        'title' => 'Explore the history of local bridges',
                        'url' => 'https://example.com/bridge-history',
                        'category' => 'documentary',
                        'note' => 'Useful source material.',
                    ],
                    [
                        'title' => 'Answer community questions',
                        'url' => null,
                        'category' => 'interview',
                        'note' => null,
                    ],
                    [],
                ],
            ])
            ->assertRedirect(route('creators.dashboard', $creator))
            ->assertSessionHas('success', 'Starter suggestions added. Your creator page is ready.');

        $this->assertDatabaseCount('recommendations', 3);
        $this->assertDatabaseHas('recommendations', [
            'creator_id' => $creator->id,
            'submitted_by' => $owner->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_CREATOR,
            'recommendation_type' => 'youtube',
            'youtube_url' => 'https://youtu.be/dQw4w9WgXcQ',
            'youtube_video_id' => 'dQw4w9WgXcQ',
            'title' => 'React to this live performance',
            'category' => 'music',
            'reason' => 'A strong first community vote.',
            'status' => 'approved',
        ]);
        $this->assertDatabaseHas('recommendations', [
            'creator_id' => $creator->id,
            'submission_source' => Recommendation::SUBMISSION_SOURCE_CREATOR,
            'recommendation_type' => 'topic',
            'youtube_url' => 'https://example.com/bridge-history',
            'youtube_video_id' => null,
            'status' => 'approved',
        ]);

        $this->assertSame(0, $owner->fresh()->suggestionsUsedFor($creator));
        $this->assertSame(0, $owner->creatorFavorites()->count());

        $this->get(route('dashboard'))
            ->assertOk()
            ->assertSeeInOrder(['Suggestions submitted', '0']);

        $this->post(route('logout'))->assertRedirect('/');

        $this->get(route('creator.queue', $creator))
            ->assertOk()
            ->assertSee('React to this live performance')
            ->assertSee('Explore the history of local bridges')
            ->assertSee('Added by creator')
            ->assertSee('aria-label="Open original link for Explore the history of local bridges"', false)
            ->assertSee('rel="noopener noreferrer nofollow ugc"', false)
            ->assertSee('aria-label="Upvote this recommendation"', false);

        $fan = User::factory()->create();
        $youtubeSuggestion = Recommendation::query()
            ->where('title', 'React to this live performance')
            ->firstOrFail();

        $this->actingAs($fan)
            ->post(route('recommendations.vote', [$creator, $youtubeSuggestion]))
            ->assertRedirect(
                route('creator.queue', $creator)."#recommendation-{$youtubeSuggestion->id}",
            );

        $this->assertDatabaseHas('user_picks', [
            'user_id' => $fan->id,
            'creator_id' => $creator->id,
            'recommendation_id' => $youtubeSuggestion->id,
        ]);

        $this->actingAs($owner)
            ->get(route('creators.recommendations.index', $creator))
            ->assertOk()
            ->assertSee('Creator-added')
            ->assertSee('Open link');
    }

    public function test_owner_can_skip_starter_suggestions(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();

        $this->actingAs($owner)
            ->post(route('creators.starter-suggestions.skip', $creator))
            ->assertRedirect(route('creators.dashboard', $creator))
            ->assertSessionHas('success', 'You can add suggestions later from your dashboard.');

        $this->assertDatabaseCount('recommendations', 0);
    }

    public function test_saving_an_empty_starter_form_is_allowed(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();

        $this->actingAs($owner)
            ->post(route('creators.starter-suggestions.store', $creator), [
                'suggestions' => [[], [], []],
            ])
            ->assertRedirect(route('creators.dashboard', $creator))
            ->assertSessionHas('success', 'No starter suggestions added. You can add them later.');

        $this->assertDatabaseCount('recommendations', 0);
    }

    public function test_starter_suggestions_validate_rows_and_the_twenty_item_limit(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();

        $this->actingAs($owner)
            ->from(route('creators.starter-suggestions.create', $creator))
            ->post(route('creators.starter-suggestions.store', $creator), [
                'suggestions' => [
                    [
                        'title' => '',
                        'url' => 'not-a-url',
                        'category' => 'invalid',
                        'note' => str_repeat('n', 1001),
                    ],
                ],
            ])
            ->assertRedirect(route('creators.starter-suggestions.create', $creator))
            ->assertSessionHasErrors([
                'suggestions.0.title',
                'suggestions.0.url',
                'suggestions.0.category',
                'suggestions.0.note',
            ]);

        $this->post(route('creators.starter-suggestions.store', $creator), [
            'suggestions' => array_fill(0, 21, ['title' => 'Too many']),
        ])->assertSessionHasErrors('suggestions');

        $this->assertDatabaseCount('recommendations', 0);
    }

    public function test_starter_suggestion_title_length_is_limited(): void
    {
        [$creator, $owner] = $this->creatorWithOwner();

        $this->actingAs($owner)
            ->post(route('creators.starter-suggestions.store', $creator), [
                'suggestions' => [
                    ['title' => str_repeat('t', 256)],
                ],
            ])
            ->assertSessionHasErrors('suggestions.0.title');

        $this->assertDatabaseCount('recommendations', 0);
    }

    /**
     * @return array{Creator, User}
     */
    private function creatorWithOwner(): array
    {
        $creator = Creator::factory()->create([
            'display_name' => 'Seeded Creator',
            'slug' => 'seeded-creator',
        ]);
        $owner = User::factory()->create();

        CreatorOwner::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);

        return [$creator, $owner];
    }
}
