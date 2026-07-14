<?php

namespace App\ViewModels;

use App\Models\Creator;
use App\Models\User;
use App\Services\Accolades\AccoladeShowcaseService;
use Illuminate\Support\Str;

class CreatorPageHeaderViewModel
{
    public function __construct(
        private readonly AccoladeShowcaseService $accolades,
    ) {}

    /** @return array<string, mixed> */
    public function forCreator(Creator $creator, ?User $user): array
    {
        $isOwner = $user
            ? $creator->creatorOwners()->where('user_id', $user->id)->where('role', 'owner')->exists()
            : false;
        $isAdminAssisting = $user?->isSuperAdmin() === true && ! $isOwner;
        $usage = $user ? $this->usageFor($user, $creator) : null;
        $showcase = $this->accolades->forSubject('creator', $creator->id);

        $requestCount = $creator->recommendations()->activePubliclyVisible()->count();
        $followerCount = $creator->creatorFavorites()->whereNull('released_at')->count();
        $voteCount = (int) $creator->userPicks()
            ->whereHas('recommendation', fn ($query) => $query->publiclyVisible()->votable())
            ->sum('vote_count');
        $publishedCount = $creator->recommendations()->where('status', 'published')->count();

        return [
            'identity' => [
                'name' => $creator->display_name,
                'handle' => '@'.$creator->slug,
                'bio' => filled($creator->bio)
                    ? Str::limit(trim((string) preg_replace('/\s+/', ' ', strip_tags($creator->bio))), 190)
                    : null,
                'avatar_url' => $creator->avatar_url,
                'hero_url' => $creator->hero_url,
            ],
            'actions' => [
                'request_url' => route('recommendations.create', $creator),
                'channel_url' => $creator->youtube_channel_url ?? $creator->channel_url,
                'submissions_open' => (bool) $creator->submissions_open,
                'can_add_request' => $user === null || ($usage['can_suggest'] && $creator->submissions_open),
                'request_label' => $this->requestLabel($creator, $user, $usage),
                'request_detail' => $this->requestDetail($creator, $user, $usage),
                'favorite_state' => $usage['is_favorited'] ?? false,
                'can_favorite' => $usage === null || $usage['is_favorited'] || $usage['reactors_remaining'] > 0,
                'favorite_label' => $usage && ! $usage['is_favorited'] && $usage['reactors_remaining'] === 0
                    ? 'Favorites Full'
                    : (($usage['is_favorited'] ?? false) ? 'Favorited' : 'Favorite'),
            ],
            'metrics' => [
                ['label' => 'Requests', 'value' => $requestCount],
                ['label' => 'Followers', 'value' => $followerCount],
                ['label' => 'Votes', 'value' => $voteCount],
                ['label' => 'Published', 'value' => $publishedCount],
            ],
            'featured_accolades' => $showcase['featured']->take(2)->values(),
            'accolade_showcase' => $showcase,
            'context' => [
                'is_creator_owner' => $isOwner,
                'is_super_admin_assisting' => $isAdminAssisting,
                'show_owner_toolbar' => $isOwner || $isAdminAssisting,
                'show_guide_activity' => $user !== null && ! $isOwner && ! $isAdminAssisting,
            ],
            'guide_activity' => $usage,
        ];
    }

    /** @return array<string, int|string|bool> */
    private function usageFor(User $user, Creator $creator): array
    {
        $limits = $user->membershipLimits();

        return [
            'tier' => $limits['label'],
            'reactors_limit' => $user->creatorFavoriteLimit(),
            'reactors_used' => $user->creatorFavoritesUsed(),
            'reactors_remaining' => $user->creatorFavoritesRemaining(),
            'suggestions_limit' => $limits['suggestions_per_reactor'],
            'suggestions_used' => $user->suggestionsUsedFor($creator),
            'suggestions_remaining' => $user->suggestionsRemainingFor($creator),
            'votes_limit' => $limits['votes_per_reactor'],
            'votes_used' => $user->votesUsedFor($creator),
            'votes_remaining' => $user->votesRemainingFor($creator),
            'can_suggest' => $user->canSuggestTo($creator),
            'is_favorited' => $user->hasFavoritedCreator($creator),
        ];
    }

    /** @param array<string, mixed>|null $usage */
    private function requestLabel(Creator $creator, ?User $user, ?array $usage): string
    {
        if (! $creator->submissions_open) {
            return 'Requests closed';
        }

        if ($user && $usage['is_favorited'] && $usage['suggestions_remaining'] === 0) {
            return 'Request limit reached';
        }

        return 'Add Request';
    }

    /** @param array<string, mixed>|null $usage */
    private function requestDetail(Creator $creator, ?User $user, ?array $usage): ?string
    {
        if (! $creator->submissions_open || ! $user || ! $usage['is_favorited']) {
            return null;
        }

        return $usage['suggestions_remaining'] === 0
            ? $usage['suggestions_used'].' / '.$usage['suggestions_limit'].' used'
            : $usage['suggestions_remaining'].' '.Str::plural('request', $usage['suggestions_remaining']).' available';
    }
}
