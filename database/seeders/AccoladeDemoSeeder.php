<?php

namespace Database\Seeders;

use App\Models\AccoladeProgress;
use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserAccolade;
use App\Models\UserPick;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use RuntimeException;

class AccoladeDemoSeeder extends Seeder
{
    public const PASSWORD = 'password';

    /** @var array<string, array{below:int,exact:int,above:int}> */
    public const SCENARIOS = [
        'submitted' => ['below' => 4, 'exact' => 5, 'above' => 6],
        'supported' => ['below' => 4, 'exact' => 5, 'above' => 6],
        'favorites' => ['below' => 2, 'exact' => 3, 'above' => 4],
        'creator-publications' => ['below' => 4, 'exact' => 5, 'above' => 6],
        'creator-consistency' => ['below' => 2, 'exact' => 3, 'above' => 4],
        'creator-reach' => ['below' => 24, 'exact' => 25, 'above' => 26],
    ];

    public function run(): void
    {
        if (app()->environment('production')) {
            throw new RuntimeException('AccoladeDemoSeeder refuses to run in production.');
        }

        DB::transaction(function (): void {
            $this->removeExistingFixtures();
            $utilityCreator = $this->creator('accolade-source-records', 'Accolade Source Records', $this->user('source-owner', 'Source Owner'));

            foreach (['below', 'exact', 'above'] as $boundary) {
                $this->seedSubmitted($boundary, self::SCENARIOS['submitted'][$boundary], $utilityCreator);
                $this->seedSupported($boundary, self::SCENARIOS['supported'][$boundary], $utilityCreator);
                $this->seedFavorites($boundary, self::SCENARIOS['favorites'][$boundary]);
                $this->seedCreatorPublications($boundary, self::SCENARIOS['creator-publications'][$boundary]);
                $this->seedCreatorConsistency($boundary, self::SCENARIOS['creator-consistency'][$boundary]);
                $this->seedCreatorReach($boundary, self::SCENARIOS['creator-reach'][$boundary]);
            }
        });

        $this->command?->info('Accolade boundary fixtures created. Password: '.self::PASSWORD);
    }

    private function seedSubmitted(string $boundary, int $count, Creator $creator): void
    {
        $guide = $this->user("submitted.{$boundary}", "Submitted {$boundary}");
        for ($index = 1; $index <= $count; $index++) {
            $this->request($creator, $guide, "submitted-{$boundary}-{$index}", now()->subDays($index));
        }
    }

    private function seedSupported(string $boundary, int $count, Creator $creator): void
    {
        $guide = $this->user("supported.{$boundary}", "Supported {$boundary}");
        for ($index = 1; $index <= $count; $index++) {
            $submitter = $this->user("supported.{$boundary}.source{$index}", "Support Source {$boundary} {$index}");
            $request = $this->request($creator, $submitter, "supported-{$boundary}-{$index}", now()->subDays($index));
            UserPick::create(['user_id' => $guide->id, 'creator_id' => $creator->id, 'recommendation_id' => $request->id,
                'vote_count' => $index === 1 ? 7 : 1, 'released_at' => now(), 'release_reason' => 'request_published']);
        }
    }

    private function seedFavorites(string $boundary, int $count): void
    {
        $guide = $this->user("favorites.{$boundary}", "Favorites {$boundary}");
        for ($index = 1; $index <= $count; $index++) {
            $creator = $this->creator("accolade-favorite-{$boundary}-{$index}", "Favorite {$boundary} {$index}", $this->user("favorites.{$boundary}.owner{$index}", "Favorite Owner {$boundary} {$index}"));
            CreatorFavorite::create(['user_id' => $guide->id, 'creator_id' => $creator->id]);
        }
    }

    private function seedCreatorPublications(string $boundary, int $count): void
    {
        $owner = $this->user("creator-publications.{$boundary}", "Creator Publications {$boundary}");
        $creator = $this->creator("accolade-creator-publications-{$boundary}", "Creator Publications {$boundary}", $owner);
        for ($index = 1; $index <= $count; $index++) {
            $this->request($creator, $this->user("creator-publications.{$boundary}.guide{$index}", "Publication Guide {$boundary} {$index}"), "creator-publications-{$boundary}-{$index}", now()->subDays($index));
        }
        // Explicit creator-origin control record: never included in the community metric.
        $this->request($creator, $owner, "creator-added-control-{$boundary}", now(), Recommendation::SUBMISSION_SOURCE_CREATOR);
    }

    private function seedCreatorConsistency(string $boundary, int $count): void
    {
        $owner = $this->user("creator-consistency.{$boundary}", "Creator Consistency {$boundary}");
        $creator = $this->creator("accolade-creator-consistency-{$boundary}", "Creator Consistency {$boundary}", $owner);
        $guide = $this->user("creator-consistency.{$boundary}.guide", "Consistency Guide {$boundary}");
        for ($month = 0; $month < $count; $month++) {
            $this->request($creator, $guide, "creator-consistency-{$boundary}-{$month}", now()->startOfMonth()->subMonths($month)->addDays(3));
        }
        // A second publication in one month proves month distinctness.
        $this->request($creator, $guide, "creator-consistency-{$boundary}-same-month", now()->startOfMonth()->addDays(8));
    }

    private function seedCreatorReach(string $boundary, int $count): void
    {
        $owner = $this->user("creator-reach.{$boundary}", "Creator Reach {$boundary}");
        $creator = $this->creator("accolade-creator-reach-{$boundary}", "Creator Reach {$boundary}", $owner);
        for ($index = 1; $index <= $count; $index++) {
            $guide = $this->user("creator-reach.{$boundary}.guide{$index}", "Reach Guide {$boundary} {$index}");
            CreatorFavorite::create(['user_id' => $guide->id, 'creator_id' => $creator->id]);
        }
        // Owner interaction is an exclusion control.
        CreatorFavorite::create(['user_id' => $owner->id, 'creator_id' => $creator->id]);
    }

    private function user(string $key, string $name): User
    {
        return User::create([
            'name' => $name,
            'public_display_name' => $name,
            'public_handle' => 'accolade-'.str($key)->replace('.', '-'),
            'public_profile_completed_at' => now(),
            'public_profile_enabled' => true,
            'email_verified_at' => now(),
            'email' => "accolade.{$key}@example.test",
            'password' => Hash::make(self::PASSWORD),
        ]);
    }

    private function creator(string $slug, string $name, User $owner): Creator
    {
        $creator = Creator::create(['slug' => $slug, 'display_name' => $name, 'status' => 'active', 'submissions_open' => true]);
        $creator->owners()->attach($owner, ['role' => 'owner']);

        return $creator;
    }

    private function request(Creator $creator, User $submitter, string $key, Carbon $publishedAt, string $source = Recommendation::SUBMISSION_SOURCE_FAN): Recommendation
    {
        $videoId = substr(strtoupper(hash('sha256', $key)), 0, 11);

        return Recommendation::create([
            'creator_id' => $creator->id, 'submitted_by' => $submitter->id, 'submission_source' => $source,
            'recommendation_type' => 'youtube', 'media_type' => 'video', 'title' => "Accolade fixture {$key}",
            'youtube_url' => "https://www.youtube.com/watch?v={$videoId}", 'normalized_url' => "https://www.youtube.com/watch?v={$videoId}",
            'youtube_video_id' => $videoId, 'status' => 'published', 'published_at' => $publishedAt,
        ]);
    }

    private function removeExistingFixtures(): void
    {
        $creatorIds = Creator::withTrashed()->where('slug', 'like', 'accolade-%')->pluck('id');
        $userIds = User::withTrashed()->where('email', 'like', 'accolade.%@example.test')->pluck('id');
        AccoladeProgress::where(fn ($query) => $query->where('subject_type', 'creator')->whereIn('subject_id', $creatorIds))
            ->orWhere(fn ($query) => $query->where('subject_type', 'guide')->whereIn('subject_id', $userIds))->delete();
        UserAccolade::whereIn('user_id', $userIds)->orWhere(fn ($query) => $query->where('subject_type', 'creator')->whereIn('subject_id', $creatorIds))->delete();
        Creator::withTrashed()->whereIn('id', $creatorIds)->forceDelete();
        User::withTrashed()->whereIn('id', $userIds)->forceDelete();
    }
}
