<?php

namespace Tests\Feature;

use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\CreatorOwner;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeedDemoDataCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_refuses_to_run_in_production(): void
    {
        $this->app->detectEnvironment(fn () => 'production');

        $this->artisan('gmj:seed-demo')
            ->expectsOutput('Refusing to seed demo data in production.')
            ->assertExitCode(1);
    }

    public function test_it_seeds_demo_creators_users_recommendations_votes_and_test_login(): void
    {
        $this->artisan('gmj:seed-demo')
            ->expectsOutput('Guide My Journey demo data seeded.')
            ->expectsOutput('Test login: jason@example.test')
            ->expectsOutput('Test password: password')
            ->assertSuccessful();

        $this->assertDatabaseHas('creators', [
            'slug' => 'jfragment',
            'display_name' => 'JFragment',
            'verification_status' => 'verified',
        ]);
        $this->assertSame(13, Creator::query()->count());
        $this->assertSame(21, User::query()->whereIn('email', $this->demoEmails())->count());
        $this->assertSame(33, Recommendation::query()->where('title', 'like', 'Demo:%')->count());
        $this->assertSame(
            0,
            Recommendation::query()
                ->where('title', 'like', 'Demo:%')
                ->whereNotNull('youtube_video_id')
                ->count(),
        );
        $this->assertGreaterThan(0, UserPick::query()->count());
        $this->assertDatabaseHas('users', [
            'email' => 'jason@example.test',
            'membership_tier' => 'pro',
        ]);
        $testUser = User::query()->where('email', 'jason@example.test')->firstOrFail();
        $jfragment = Creator::query()->where('slug', 'jfragment')->firstOrFail();
        $this->assertSame(0, $testUser->recommendationsSubmitted()->count());
        $this->assertSame(0, $testUser->userPicks()->count());
        $this->assertTrue($testUser->ownedCreators->contains($jfragment));
        $this->assertNotNull($jfragment->youtube_channel_id);
        $this->assertStringStartsWith('UC', $jfragment->youtube_channel_id);
        $this->assertSame($jfragment->channel_url, $jfragment->youtube_channel_url);
        $this->assertNotNull($jfragment->verified_at);
        $this->assertSame(
            'A musician exploring Filipino music, culture, documentaries, and creator stories.',
            $jfragment->bio,
        );
        $this->assertSame('manual', $jfragment->recommendation_approval_mode);
        $this->assertSame(
            'First-time reactions to music, movies, and cultural moments from around the world.',
            Creator::query()->where('slug', 'first-listen-frank')->value('bio'),
        );
        $this->assertSame(
            'auto',
            Creator::query()->where('slug', 'first-listen-frank')->value('recommendation_approval_mode'),
        );
        $this->assertSame(13, CreatorOwner::query()->count());
        $this->assertGreaterThanOrEqual(6, $jfragment->creatorFavorites()->count());
        $this->assertGreaterThan(0, CreatorFavorite::query()->count());
        $this->assertGreaterThanOrEqual(15, $jfragment->creatorTags()->count());
        $this->assertDatabaseHas('creator_tags', [
            'creator_id' => $jfragment->id,
            'slug' => 'opm',
        ]);
        $this->assertTrue(
            $jfragment->recommendations()
                ->where('title', 'like', 'Demo:%')
                ->whereHas('creatorTags')
                ->exists(),
        );

        $this->assertGreaterThanOrEqual(5, $jfragment->recommendations()->where('status', 'pending')->count());
        $this->assertGreaterThanOrEqual(
            5,
            $jfragment->recommendations()->whereIn('status', ['coming_soon', 'scheduled', 'recorded'])->count(),
        );
        $this->assertGreaterThanOrEqual(5, $jfragment->recommendations()->where('status', 'published')->count());
        $this->assertGreaterThanOrEqual(
            3,
            $jfragment->recommendations()->whereIn('status', ['already_seen', 'passed', 'hidden'])->count(),
        );

        foreach (Recommendation::STATUSES as $status) {
            $this->assertTrue(
                $jfragment->recommendations()->where('status', $status)->exists(),
                "Expected demo recommendations with status {$status}.",
            );
        }

        $this->assertFalse(
            $jfragment->recommendations()
                ->where('status', 'scheduled')
                ->whereNull('scheduled_for')
                ->exists(),
        );
        $this->assertFalse(
            $jfragment->recommendations()
                ->where('status', 'published')
                ->where(function ($query): void {
                    $query->whereNull('published_at')
                        ->orWhereNull('published_reaction_url');
                })
                ->exists(),
        );

        foreach ($this->demoCreatorSlugs() as $slug) {
            $creator = Creator::query()->where('slug', $slug)->firstOrFail();

            $this->assertSame('verified', $creator->verification_status);
            $this->assertCount(1, $creator->owners);
            $this->assertStringEndsWith('@example.test', $creator->owners->first()->email);
            $this->assertGreaterThanOrEqual(2, $creator->creatorFavorites()->count());
            $this->assertFalse($creator->favoritedByUsers->contains($creator->owners->first()));
        }

        $topRecommendationVotes = Recommendation::query()
            ->where('title', 'Demo: First listen to a legendary synth anthem')
            ->firstOrFail()
            ->userPicks()
            ->count();

        $this->assertGreaterThan(5, $topRecommendationVotes);

        User::query()
            ->whereIn('email', $this->demoEmails())
            ->each(function (User $user): void {
                $this->assertLessThanOrEqual(
                    $user->membershipLimits()['votes_per_reactor'],
                    $user->userPicks()->count(),
                );
            });

        $voteCount = UserPick::query()->count();
        $this->artisan('gmj:seed-demo')->assertSuccessful();
        $this->assertSame($voteCount, UserPick::query()->count());
    }

    public function test_fresh_only_removes_predictable_demo_data(): void
    {
        $this->artisan('gmj:seed-demo')->assertSuccessful();

        $realCreator = Creator::factory()->create(['slug' => 'real-reactor']);
        $realUser = User::factory()->create(['email' => 'real@example.com']);
        $realRecommendation = Recommendation::factory()->create([
            'creator_id' => $realCreator->id,
            'submitted_by' => $realUser->id,
            'title' => 'A real local recommendation',
            'status' => 'approved',
        ]);

        $this->artisan('gmj:seed-demo --fresh')->assertSuccessful();

        $this->assertDatabaseHas('creators', ['slug' => 'real-reactor']);
        $this->assertDatabaseHas('users', ['email' => 'real@example.com']);
        $this->assertDatabaseHas('recommendations', ['id' => $realRecommendation->id]);
        $this->assertSame(33, Recommendation::query()->where('title', 'like', 'Demo:%')->count());
        $this->assertSame(13, Creator::query()->whereIn('slug', [
            'jfragment',
            ...$this->demoCreatorSlugs(),
        ])->count());
    }

    /**
     * @return array<int, string>
     */
    private function demoEmails(): array
    {
        return [
            ...array_map(fn (string $slug) => "{$slug}@example.test", $this->demoCreatorSlugs()),
            ...array_map(fn (int $fan) => "fan{$fan}@example.test", range(1, 8)),
            'jason@example.test',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function demoCreatorSlugs(): array
    {
        return [
            'metal-mom-reacts',
            'pinoy-rock-discoveries',
            'vocals-with-vanessa',
            'movie-night-mike',
            'culture-curious',
            'prog-dad-reacts',
            'soul-sunday',
            'karaoke-queen-reacts',
            'history-and-harmonies',
            'first-listen-frank',
            'global-grooves',
            'riff-and-rewind',
        ];
    }
}
