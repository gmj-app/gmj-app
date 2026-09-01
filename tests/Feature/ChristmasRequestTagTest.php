<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorOwner;
use App\Models\CreatorTag;
use App\Models\Recommendation;
use App\Models\User;
use App\Services\CreatorTagService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChristmasRequestTagTest extends TestCase
{
    use RefreshDatabase;

    public function test_guide_can_tag_a_normal_submission_as_christmas_and_the_creator_tag_is_reused(): void
    {
        $creator = Creator::factory()->create([
            'slug' => 'christmas-creator',
            'recommendation_approval_mode' => Creator::APPROVAL_MODE_AUTO,
        ]);
        $guide = User::factory()->create();

        $this->actingAs($guide)
            ->get(route('recommendations.create', $creator))
            ->assertOk()
            ->assertSee('name="christmas"', false)
            ->assertSee('Christmas Request')
            ->assertSee('It still uses one normal Request slot.');

        foreach (['Christmas song one', 'Christmas song two'] as $title) {
            $this->post(route('recommendations.store', $creator), [
                'recommendation_type' => 'topic',
                'title' => $title,
                'description' => "Please cover {$title}.",
                'christmas' => '1',
                'confirm_favorite' => '1',
            ])->assertRedirect(route('creator.queue', $creator));
        }

        $tag = CreatorTag::query()
            ->where('creator_id', $creator->id)
            ->where('slug', 'christmas')
            ->sole();

        $this->assertSame('Christmas', $tag->name);
        $this->assertSame(1, CreatorTag::query()->where('creator_id', $creator->id)->where('slug', 'christmas')->count());
        $this->assertSame(2, $tag->recommendations()->count());
    }

    public function test_christmas_requests_use_the_same_three_request_limit(): void
    {
        $creator = Creator::factory()->create([
            'recommendation_approval_mode' => Creator::APPROVAL_MODE_AUTO,
        ]);
        $guide = User::factory()->create();

        foreach ([true, false, true] as $index => $christmas) {
            $this->actingAs($guide)->post(route('recommendations.store', $creator), [
                'recommendation_type' => 'topic',
                'title' => 'Request '.($index + 1),
                'description' => 'A normal request description.',
                'christmas' => $christmas ? '1' : '0',
                'confirm_favorite' => '1',
            ])->assertRedirect(route('creator.queue', $creator));
        }

        $this->post(route('recommendations.store', $creator), [
            'recommendation_type' => 'topic',
            'title' => 'Fourth request',
            'description' => 'This request exceeds the shared limit.',
            'christmas' => '1',
        ])->assertSessionHasErrors([
            'limit' => 'You can have up to 3 active Requests for this Creator.',
        ]);

        $this->assertSame(3, $creator->recommendations()->count());
        $this->assertSame(2, $creator->recommendations()->whereHas('creatorTags', fn ($query) => $query->where('slug', 'christmas'))->count());
    }

    public function test_christmas_uses_existing_public_and_creator_filters_and_survives_publication_until_a_creator_removes_it(): void
    {
        $creator = Creator::factory()->create(['slug' => 'filter-christmas']);
        $owner = User::factory()->create();
        CreatorOwner::query()->create([
            'creator_id' => $creator->id,
            'user_id' => $owner->id,
            'role' => 'owner',
        ]);
        $christmas = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Christmas filter match',
            'status' => 'approved',
        ]);
        $ordinary = Recommendation::factory()->create([
            'creator_id' => $creator->id,
            'title' => 'Ordinary filter miss',
            'status' => 'approved',
        ]);
        app(CreatorTagService::class)->attach($creator, $christmas, CreatorTagService::CHRISTMAS_TAG);

        $this->get(route('creator.queue', [
            'creator' => $creator,
            'tag' => 'christmas',
            'q' => 'filter',
            'sort' => 'votes',
            'per_page' => 100,
        ]))
            ->assertOk()
            ->assertSee($christmas->title)
            ->assertDontSee($ordinary->title)
            ->assertSee('value="christmas" selected', false);

        $this->actingAs($owner)->get(route('creators.recommendations.index', [
            'creator' => $creator,
            'tag' => 'christmas',
            'q' => 'filter',
        ]))
            ->assertOk()
            ->assertSee($christmas->title)
            ->assertDontSee($ordinary->title)
            ->assertSee('value="christmas" selected', false);

        $this->patch(route('creators.recommendations.status', [$creator, $christmas]), [
            'status' => 'recorded',
        ])->assertRedirect();
        $this->patch(route('creators.recommendations.status', [$creator, $christmas]), [
            'status' => 'published',
        ])->assertRedirect();
        $this->assertTrue($christmas->fresh()->creatorTags()->where('slug', 'christmas')->exists());

        $this->patch(route('creators.recommendations.update', [$creator, $christmas]), [
            'title' => $christmas->title,
            'status' => 'published',
            'tags' => '',
        ])->assertRedirect();
        $this->assertFalse($christmas->fresh()->creatorTags()->where('slug', 'christmas')->exists());
        $this->assertTrue($creator->creatorTags()->where('slug', 'christmas')->exists());
    }
}
