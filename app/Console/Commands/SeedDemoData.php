<?php

namespace App\Console\Commands;

use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\CreatorOwner;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use App\Services\CreatorTagService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;

class SeedDemoData extends Command
{
    protected $signature = 'gmj:seed-demo {--fresh : Delete demo data created by this command before seeding}';

    protected $description = 'Seed local Guide My Journey demo creators, fans, recommendations, and votes.';

    /**
     * @var array<int, array{slug: string, display_name: string, bio?: string, recommendation_approval_mode?: string}>
     */
    private array $demoCreators = [
        ['slug' => 'metal-mom-reacts', 'display_name' => 'Metal Mom Reacts'],
        ['slug' => 'pinoy-rock-discoveries', 'display_name' => 'Pinoy Rock Discoveries'],
        ['slug' => 'vocals-with-vanessa', 'display_name' => 'Vocals with Vanessa', 'bio' => 'Breaking down music, performance, and the creative choices behind memorable vocals.'],
        ['slug' => 'movie-night-mike', 'display_name' => 'Movie Night Mike', 'bio' => 'Exploring filmmaking, visual storytelling, and the ideas behind memorable movies.'],
        ['slug' => 'culture-curious', 'display_name' => 'Culture Curious', 'bio' => 'Exploring culture, history, food, language, and the stories behind communities.', 'recommendation_approval_mode' => 'auto'],
        ['slug' => 'prog-dad-reacts', 'display_name' => 'Prog Dad Reacts', 'bio' => 'Breaking down ambitious music, production details, and creative experiments.'],
        ['slug' => 'soul-sunday', 'display_name' => 'Soul Sunday', 'bio' => 'Discovering music history, performance, and the stories behind influential artists.'],
        ['slug' => 'karaoke-queen-reacts', 'display_name' => 'Karaoke Queen Reacts', 'bio' => 'Big vocals, fun performances, and community-requested singalong moments.'],
        ['slug' => 'history-and-harmonies', 'display_name' => 'History and Harmonies', 'bio' => 'Connecting historical context with the music and media people love.'],
        ['slug' => 'first-listen-frank', 'display_name' => 'First Listen Frank', 'bio' => 'First-time reactions to music, movies, and cultural moments from around the world.', 'recommendation_approval_mode' => 'auto'],
        ['slug' => 'global-grooves', 'display_name' => 'Global Grooves', 'bio' => 'Discovering music scenes and live performances from every corner of the world.', 'recommendation_approval_mode' => 'auto'],
        ['slug' => 'riff-and-rewind', 'display_name' => 'Riff and Rewind', 'bio' => 'Making practical music guides, creative deep dives, and thoughtful video essays.'],
    ];

    /**
     * @var array<int, array{title: string, artist: string, category: string, status: string, video_id: string}>
     */
    private array $demoRecommendations = [
        ['title' => 'Demo: First listen to a legendary synth anthem', 'artist' => 'The Midnight Echoes', 'category' => 'music', 'status' => 'approved', 'video_id' => 'DEMO0000001'],
        ['title' => 'Demo: Vocal coach reacts to a powerhouse ballad', 'artist' => 'Maya North', 'category' => 'music', 'status' => 'coming_soon', 'video_id' => 'DEMO0000002'],
        ['title' => 'Demo: Documentary on street food history', 'artist' => 'Global Stories', 'category' => 'documentary', 'status' => 'recorded', 'video_id' => 'DEMO0000003'],
        ['title' => 'Demo: Culture shock moments from a travel vlog', 'artist' => 'Nomad Notes', 'category' => 'culture', 'status' => 'published', 'video_id' => 'DEMO0000004'],
        ['title' => 'Demo: Interview with an indie producer', 'artist' => 'Studio Window', 'category' => 'interview', 'status' => 'approved', 'video_id' => 'DEMO0000005'],
        ['title' => 'Demo: Hidden gem guitar solo breakdown', 'artist' => 'Riff Harbor', 'category' => 'music', 'status' => 'pending', 'video_id' => 'DEMO0000006'],
        ['title' => 'Demo: Why old movie trailers feel different', 'artist' => 'Cinema Shelf', 'category' => 'other', 'status' => 'passed', 'video_id' => 'DEMO0000007'],
        ['title' => 'Demo: Filipino rock chorus that surprised everyone', 'artist' => 'Luzon Lights', 'category' => 'music', 'status' => 'hidden', 'video_id' => 'DEMO0000008'],
        ['title' => 'Demo: The story behind a protest song', 'artist' => 'Archive Avenue', 'category' => 'documentary', 'status' => 'approved', 'video_id' => 'DEMO0000009'],
        ['title' => 'Demo: Live performance with unusual harmonies', 'artist' => 'Velvet Junction', 'category' => 'music', 'status' => 'scheduled', 'video_id' => 'DEMO0000010'],
        ['title' => 'Demo: Interview with a tour photographer', 'artist' => 'Backstage Desk', 'category' => 'interview', 'status' => 'recorded', 'video_id' => 'DEMO0000011'],
        ['title' => 'Demo: Traditional dance explained for beginners', 'artist' => 'Heritage House', 'category' => 'culture', 'status' => 'published', 'video_id' => 'DEMO0000012'],
        ['title' => 'Demo: Drum groove that changed pop music', 'artist' => 'Pocket Theory', 'category' => 'music', 'status' => 'approved', 'video_id' => 'DEMO0000013'],
        ['title' => 'Demo: Mini documentary on vinyl collectors', 'artist' => 'Needle Drop North', 'category' => 'documentary', 'status' => 'coming_soon', 'video_id' => 'DEMO0000014'],
        ['title' => 'Demo: Comedian explains regional slang', 'artist' => 'Laugh Lines', 'category' => 'culture', 'status' => 'pending', 'video_id' => 'DEMO0000015'],
        ['title' => 'Demo: Producer interview about sampling ethics', 'artist' => 'Sample Room', 'category' => 'interview', 'status' => 'approved', 'video_id' => 'DEMO0000016'],
        ['title' => 'Demo: Acoustic cover with a twist ending', 'artist' => 'Porchlight Sessions', 'category' => 'music', 'status' => 'recorded', 'video_id' => 'DEMO0000017'],
        ['title' => 'Demo: Explainer on festival rituals', 'artist' => 'Open Map Media', 'category' => 'culture', 'status' => 'published', 'video_id' => 'DEMO0000018'],
        ['title' => 'Demo: Forgotten TV theme song reaction', 'artist' => 'Retro Frame', 'category' => 'other', 'status' => 'already_seen', 'video_id' => 'DEMO0000019'],
        ['title' => 'Demo: Bass line that deserves more attention', 'artist' => 'Low End Theory', 'category' => 'music', 'status' => 'approved', 'video_id' => 'DEMO0000020'],
        ['title' => 'Demo: Short documentary about buskers', 'artist' => 'Corner Stage', 'category' => 'documentary', 'status' => 'hidden', 'video_id' => 'DEMO0000021'],
        ['title' => 'Demo: First listen to progressive metal odd meters', 'artist' => 'Fractal Bridge', 'category' => 'music', 'status' => 'scheduled', 'video_id' => 'DEMO0000022'],
        ['title' => 'Demo: Interview with a choir arranger', 'artist' => 'Harmony Lab', 'category' => 'interview', 'status' => 'pending', 'video_id' => 'DEMO0000023'],
        ['title' => 'Demo: Street art culture around album covers', 'artist' => 'Wall Notes', 'category' => 'culture', 'status' => 'recorded', 'video_id' => 'DEMO0000024'],
        ['title' => 'Demo: Wild card internet classic reaction', 'artist' => 'Early Web Archive', 'category' => 'other', 'status' => 'published', 'video_id' => 'DEMO0000025'],
        ['title' => 'Demo: Fan request for an overlooked live duet', 'artist' => 'Northern Signals', 'category' => 'music', 'status' => 'pending', 'video_id' => 'DEMO0000026'],
        ['title' => 'Demo: Cultural history of neighborhood record shops', 'artist' => 'City Archive', 'category' => 'culture', 'status' => 'pending', 'video_id' => 'DEMO0000027'],
        ['title' => 'Demo: Reaction to a cinematic percussion showcase', 'artist' => 'Frame Drummers', 'category' => 'music', 'status' => 'published', 'video_id' => 'DEMO0000028'],
        ['title' => 'Demo: Interview with a touring vocal director', 'artist' => 'Backline Stories', 'category' => 'interview', 'status' => 'published', 'video_id' => 'DEMO0000029'],
        ['title' => 'Demo: Deep dive into harmony in game soundtracks', 'artist' => 'Pixel Orchestra', 'category' => 'documentary', 'status' => 'coming_soon', 'video_id' => 'DEMO0000030'],
        ['title' => 'Demo: Premiere night for an international rock set', 'artist' => 'Borderless Stage', 'category' => 'music', 'status' => 'scheduled', 'video_id' => 'DEMO0000031'],
        ['title' => 'Demo: Unusual instruments in modern pop', 'artist' => 'Curious Frequencies', 'category' => 'other', 'status' => 'approved', 'video_id' => 'DEMO0000032'],
        ['title' => 'Demo: Withdrawn duplicate request', 'artist' => 'Archive Signal', 'category' => 'other', 'status' => 'withdrawn', 'video_id' => 'DEMO0000033'],
    ];

    public function handle(): int
    {
        if (app()->environment('production')) {
            $this->error('Refusing to seed demo data in production.');

            return self::FAILURE;
        }

        $summary = DB::transaction(function (): array {
            if ($this->option('fresh')) {
                $this->deleteDemoData();
            }

            $creatorsCreated = 0;
            $usersCreated = 0;
            $recommendationsCreated = 0;
            $votesCreated = 0;
            $favoritesCreated = 0;

            $mainCreator = $this->updateOrCreateCreator([
                'slug' => 'jfragment',
                'display_name' => 'JFragment',
                'bio' => 'A musician exploring Filipino music, culture, documentaries, and creator stories.',
                'recommendation_approval_mode' => 'manual',
                ...$this->verifiedChannelData('jfragment', 'JFragment'),
            ]);
            $creatorsCreated += (int) $mainCreator->wasRecentlyCreated;
            $this->seedCreatorTags($mainCreator);

            $demoCreators = collect();
            foreach ($this->demoCreators as $creatorData) {
                $creator = $this->updateOrCreateCreator([
                    ...$creatorData,
                    ...$this->verifiedChannelData($creatorData['slug'], $creatorData['display_name']),
                ]);
                $creatorsCreated += (int) $creator->wasRecentlyCreated;
                $demoCreators->push($creator);
                $this->seedCreatorTags($creator);

                $user = $this->updateOrCreateUser(
                    "{$creatorData['slug']}@example.test",
                    $creatorData['display_name'],
                    'free',
                );
                $usersCreated += (int) $user->wasRecentlyCreated;
            }

            $fans = collect();
            for ($fan = 1; $fan <= 8; $fan++) {
                $user = $this->updateOrCreateUser("fan{$fan}@example.test", "Demo Fan {$fan}", 'free');
                $usersCreated += (int) $user->wasRecentlyCreated;
                $fans->push($user);
            }

            foreach ($demoCreators->values() as $index => $creator) {
                $this->assignOwner($creator, $fans[$index % $fans->count()]);
            }

            $testUser = $this->updateOrCreateUser('jason@example.test', 'Jason Demo Tester', 'pro');
            $usersCreated += (int) $testUser->wasRecentlyCreated;
            $this->assignOwner($mainCreator, $testUser);
            $this->resetTestUserQuota($testUser, $mainCreator);

            if (Schema::hasTable('creator_favorites')) {
                $favoritesCreated += $this->seedFavorites($mainCreator, $demoCreators, $fans);
            }

            $submitters = $fans->values();
            $recommendations = collect();
            foreach ($this->demoRecommendations as $index => $recommendationData) {
                $submitter = $submitters[$index % $submitters->count()];
                $recommendation = $this->updateOrCreateRecommendation($mainCreator, $submitter, $recommendationData);
                $recommendationsCreated += (int) $recommendation->wasRecentlyCreated;
                $recommendations->push($recommendation);
                $this->assignDemoTags($mainCreator, $recommendation, $recommendationData);
            }

            $voters = User::query()
                ->whereIn('email', $this->demoUserEmails())
                ->where('email', '!=', 'jason@example.test')
                ->orderBy('email')
                ->get();

            $votesCreated += $this->seedVotes($mainCreator, $recommendations, $voters);

            return [
                'creators_created' => $creatorsCreated,
                'users_created' => $usersCreated,
                'recommendations_created' => $recommendationsCreated,
                'votes_created' => $votesCreated,
                'favorites_created' => $favoritesCreated,
                'test_user_created' => $testUser->wasRecentlyCreated,
            ];
        });

        $this->info('Guide My Journey demo data seeded.');
        $this->line("Creators created: {$summary['creators_created']}");
        $this->line("Users created: {$summary['users_created']}");
        $this->line("Recommendations created: {$summary['recommendations_created']}");
        $this->line("Upvotes created: {$summary['votes_created']}");
        $this->line("Favorites/followers created: {$summary['favorites_created']}");
        $this->newLine();
        $this->line('Test login: jason@example.test');
        $this->line('Test password: password');

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function updateOrCreateCreator(array $data): Creator
    {
        return Creator::query()->updateOrCreate(
            ['slug' => $data['slug']],
            collect($data)->only($this->creatorColumns())->all(),
        );
    }

    private function updateOrCreateUser(string $email, string $name, string $membershipTier): User
    {
        $data = [
            'name' => $name,
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ];

        if (Schema::hasColumn('users', 'membership_tier')) {
            $data['membership_tier'] = $membershipTier;
        }

        if (Schema::hasColumn('users', 'plan_slug')) {
            $data['plan_slug'] = $membershipTier;
        }

        return User::query()->updateOrCreate(['email' => $email], $data);
    }

    /**
     * @param  array{title: string, artist: string, category: string, status: string, video_id: string}  $data
     */
    private function updateOrCreateRecommendation(Creator $creator, User $submitter, array $data): Recommendation
    {
        $attributes = [
            'creator_id' => $creator->id,
            'title' => $data['title'],
        ];

        $values = [
            'submitted_by' => $submitter->id,
            'youtube_url' => null,
            'youtube_video_id' => null,
            'title' => $data['title'],
            'artist' => $data['artist'],
            'category' => $data['category'],
            'reason' => "Demo seed data for local testing: {$data['title']}.",
            'status' => $data['status'],
            'is_pinned' => in_array($data['video_id'], ['DEMO0000001', 'DEMO0000002'], true),
            'published_reaction_url' => $data['status'] === 'published'
                ? "https://www.youtube.com/watch?v={$data['video_id']}"
                : null,
        ];

        if (Schema::hasColumn('recommendations', 'scheduled_for')) {
            $values['scheduled_for'] = $data['status'] === 'scheduled'
                ? now()->startOfDay()->addDays(((int) substr($data['video_id'], -2)) + 1)->setTime(19, 30)
                : null;
        }

        if (Schema::hasColumn('recommendations', 'published_at')) {
            $values['published_at'] = $data['status'] === 'published'
                ? now()->startOfDay()->subDays(max(1, (int) substr($data['video_id'], -2)))
                : null;
        }

        if (Schema::hasColumn('recommendations', 'recommendation_type')) {
            $values['recommendation_type'] = 'topic';
        }

        if (Schema::hasColumn('recommendations', 'channel_title')) {
            $values['channel_title'] = 'Demo YouTube Channel';
        }

        if (Schema::hasColumn('recommendations', 'description')) {
            $values['description'] = "Demo topic for local testing: {$data['title']}.";
        }

        if (Schema::hasColumn('recommendations', 'normalized_url')) {
            $values['normalized_url'] = null;
        }

        if (Schema::hasColumn('recommendations', 'published_normalized_url')) {
            $values['published_normalized_url'] = $data['status'] === 'published'
                ? "https://www.youtube.com/watch?v={$data['video_id']}"
                : null;
        }

        if (Schema::hasColumn('recommendations', 'published_video_id')) {
            $values['published_video_id'] = $data['status'] === 'published'
                ? $data['video_id']
                : null;
        }

        if (Schema::hasColumn('recommendations', 'withdrawn_at')) {
            $values['withdrawn_at'] = $data['status'] === 'withdrawn'
                ? now()->subDays(2)
                : null;
        }

        if (Schema::hasColumn('recommendations', 'withdrawn_by_user_id')) {
            $values['withdrawn_by_user_id'] = $data['status'] === 'withdrawn'
                ? $submitter->id
                : null;
        }

        return Recommendation::query()->updateOrCreate($attributes, $values);
    }

    private function seedVotes(Creator $creator, $recommendations, $voters): int
    {
        $desiredVotes = [9, 7, 4, 4, 3, 0, 0, 0, 4, 3, 2, 0, 5, 3, 0, 3, 2, 1, 0, 4, 0, 3, 0, 2, 1, 0, 0, 4, 3, 2, 2, 1, 0];
        $usageByUser = [];
        $created = 0;

        UserPick::query()
            ->whereIn(
                'recommendation_id',
                $recommendations
                    ->reject(fn (Recommendation $recommendation) => $recommendation->consumesUpvotes())
                    ->pluck('id'),
            )
            ->delete();

        foreach ($voters as $voter) {
            $usageByUser[$voter->id] = UserPick::query()
                ->where('user_id', $voter->id)
                ->where('creator_id', $creator->id)
                ->count();
        }

        foreach ($recommendations->values() as $index => $recommendation) {
            if (! $recommendation->consumesUpvotes()) {
                continue;
            }

            $target = $desiredVotes[$index] ?? 0;
            if ($target === 0) {
                continue;
            }

            $existingVoterIds = UserPick::query()
                ->where('recommendation_id', $recommendation->id)
                ->whereIn('user_id', $voters->pluck('id'))
                ->pluck('user_id');
            $votesForRecommendation = $existingVoterIds->count();

            if ($votesForRecommendation >= $target) {
                continue;
            }

            $eligibleVoters = $voters
                ->sortBy(fn (User $voter) => [$usageByUser[$voter->id], $voter->email])
                ->values();

            foreach ($eligibleVoters as $voter) {
                if ($votesForRecommendation >= $target) {
                    break;
                }

                if ($existingVoterIds->contains($voter->id)) {
                    continue;
                }

                $voteLimit = $voter->membershipLimits()['votes_per_reactor'];
                if ($usageByUser[$voter->id] >= $voteLimit) {
                    continue;
                }

                $pick = UserPick::query()->updateOrCreate(
                    [
                        'user_id' => $voter->id,
                        'recommendation_id' => $recommendation->id,
                    ],
                    [
                        'creator_id' => $creator->id,
                        'rank' => null,
                    ],
                );

                if ($pick->wasRecentlyCreated) {
                    $created++;
                    $usageByUser[$voter->id]++;
                }

                $votesForRecommendation++;
            }
        }

        return $created;
    }

    private function seedCreatorTags(Creator $creator): void
    {
        if (! Schema::hasTable('creator_tags')) {
            return;
        }

        app(CreatorTagService::class)->createDefaults($creator);

        if ($creator->slug === 'jfragment') {
            app(CreatorTagService::class)->resolve($creator, [
                'OPM',
                'Live Performance',
                'Documentary',
                'Culture',
                'Metal',
            ]);
        }
    }

    /**
     * @param  array{title: string, artist: string, category: string, status: string, video_id: string}  $data
     */
    private function assignDemoTags(Creator $creator, Recommendation $recommendation, array $data): void
    {
        if (! Schema::hasTable('recommendation_tag')) {
            return;
        }

        $tags = match ($data['category']) {
            'documentary' => ['Documentary', 'Deep Dive'],
            'culture' => ['Culture', 'Community Favorite'],
            'music' => str_contains(strtolower($data['title']), 'live')
                ? ['Live Performance', 'Review']
                : ['OPM', 'Review'],
            default => ['Quick Take'],
        };

        $resolved = app(CreatorTagService::class)->resolve($creator, $tags);
        $recommendation->creatorTags()->sync($resolved->pluck('id')->all());
    }

    private function resetTestUserQuota(User $user, Creator $creator): void
    {
        UserPick::query()
            ->where('user_id', $user->id)
            ->where('creator_id', $creator->id)
            ->delete();

        Recommendation::query()
            ->where('submitted_by', $user->id)
            ->where('creator_id', $creator->id)
            ->delete();
    }

    private function assignOwner(Creator $creator, User $user): void
    {
        CreatorOwner::query()->updateOrCreate(
            [
                'creator_id' => $creator->id,
                'user_id' => $user->id,
            ],
            ['role' => 'owner'],
        );
    }

    private function seedFavorites(Creator $mainCreator, $demoCreators, $fans): int
    {
        $created = 0;

        foreach ($fans->take(6) as $fan) {
            $favorite = CreatorFavorite::query()->updateOrCreate([
                'creator_id' => $mainCreator->id,
                'user_id' => $fan->id,
            ]);
            $created += (int) $favorite->wasRecentlyCreated;
        }

        foreach ($demoCreators->values() as $index => $creator) {
            foreach ([$fans[($index + 1) % $fans->count()], $fans[($index + 2) % $fans->count()]] as $fan) {
                $favorite = CreatorFavorite::query()->updateOrCreate([
                    'creator_id' => $creator->id,
                    'user_id' => $fan->id,
                ]);
                $created += (int) $favorite->wasRecentlyCreated;
            }
        }

        return $created;
    }

    /**
     * @return array<string, mixed>
     */
    private function verifiedChannelData(string $slug, string $title): array
    {
        $channelId = 'UC'.strtoupper(substr(hash('sha256', "gmj-demo-{$slug}"), 0, 22));
        $channelUrl = "https://www.youtube.com/channel/{$channelId}";

        return [
            'channel_url' => $channelUrl,
            'youtube_channel_id' => $channelId,
            'youtube_channel_title' => $title,
            'youtube_channel_url' => $channelUrl,
            'youtube_thumbnail_url' => null,
            'avatar_path' => null,
            'youtube_banner_url' => null,
            'hero_path' => null,
            'verification_status' => 'verified',
            'verified_at' => now(),
            'submissions_open' => true,
            'status' => 'active',
            'deactivated_at' => null,
        ];
    }

    private function deleteDemoData(): void
    {
        $demoCreatorIds = Creator::query()
            ->whereIn('slug', $this->demoCreatorSlugs())
            ->pluck('id');

        $jfragmentId = Creator::query()
            ->where('slug', 'jfragment')
            ->value('id');

        $demoRecommendationIds = Recommendation::query()
            ->where(function ($query) use ($demoCreatorIds, $jfragmentId): void {
                $query->whereIn('creator_id', $demoCreatorIds);

                if ($jfragmentId) {
                    $query->orWhere(function ($nested) use ($jfragmentId): void {
                        $nested->where('creator_id', $jfragmentId)
                            ->where('title', 'like', 'Demo:%');
                    });
                }
            })
            ->pluck('id');

        UserPick::query()
            ->whereIn('recommendation_id', $demoRecommendationIds)
            ->orWhereIn('user_id', User::query()->whereIn('email', $this->demoUserEmails())->select('id'))
            ->delete();

        Recommendation::query()
            ->whereIn('id', $demoRecommendationIds)
            ->delete();

        Creator::query()
            ->whereIn('slug', $this->demoCreatorSlugs())
            ->delete();

        User::query()
            ->whereIn('email', $this->demoUserEmails())
            ->delete();
    }

    /**
     * @return array<int, string>
     */
    private function demoCreatorSlugs(): array
    {
        return array_column($this->demoCreators, 'slug');
    }

    /**
     * @return array<int, string>
     */
    private function demoUserEmails(): array
    {
        return [
            ...array_map(fn (array $creator) => "{$creator['slug']}@example.test", $this->demoCreators),
            ...array_map(fn (int $fan) => "fan{$fan}@example.test", range(1, 8)),
            'jason@example.test',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function creatorColumns(): array
    {
        return collect([
            'slug',
            'display_name',
            'channel_url',
            'email',
            'membership_tier',
            'youtube_channel_id',
            'youtube_channel_title',
            'youtube_channel_url',
            'youtube_thumbnail_url',
            'avatar_path',
            'youtube_banner_url',
            'hero_path',
            'verification_status',
            'verified_at',
            'bio',
            'submission_instructions',
            'submissions_open',
            'recommendation_approval_mode',
            'status',
            'deactivated_at',
        ])
            ->filter(fn (string $column) => Schema::hasColumn('creators', $column))
            ->values()
            ->all();
    }
}
