<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreRecommendationRequest;
use App\Models\Creator;
use App\Models\CreatorFavorite;
use App\Models\Recommendation;
use App\Models\User;
use App\Services\CreatorParticipationService;
use App\Services\YouTubeUrlService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RecommendationController extends Controller
{
    public function __construct(
        private readonly CreatorParticipationService $participation,
        private readonly YouTubeUrlService $youtubeUrls,
    ) {}

    public function showCreatorQueue(Request $request, Creator $creator): View
    {
        abort_if($creator->status !== 'active', 404);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'category' => (string) $request->query('category', ''),
            'tag' => (string) $request->query('tag', ''),
            'sort' => (string) $request->query('sort', 'votes'),
        ];
        $filters['status'] = in_array($filters['status'], Recommendation::PUBLIC_STATUSES, true)
            ? $filters['status']
            : '';
        $filters['sort'] = in_array($filters['sort'], ['votes', 'newest', 'status', 'scheduled'], true)
            ? $filters['sort']
            : 'votes';
        $filters['tag'] = $creator->creatorTags()
            ->where('slug', $filters['tag'])
            ->value('slug') ?? '';

        $publicRecommendationsCount = $creator->recommendations()
            ->whereIn('status', Recommendation::PUBLIC_STATUSES)
            ->count();
        $publicVotesCount = $creator->userPicks()
            ->whereHas('recommendation', fn ($query) => $query
                ->whereIn('status', Recommendation::upvoteConsumingStatuses()))
            ->count();
        $topRequestedId = $creator->recommendations()
            ->whereIn('status', Recommendation::PUBLIC_STATUSES)
            ->whereIn('status', Recommendation::upvoteConsumingStatuses())
            ->withCount('userPicks')
            ->orderByDesc('user_picks_count')
            ->orderByDesc('is_pinned')
            ->latest()
            ->first()
            ?->id;

        $categoryOptions = $creator->recommendations()
            ->whereIn('status', Recommendation::PUBLIC_STATUSES)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
        $statusOptions = collect(Recommendation::PUBLIC_STATUSES)
            ->mapWithKeys(fn (string $status) => [
                $status => Recommendation::STATUS_LABELS[$status],
            ]);

        $recommendationsQuery = $creator->recommendations()
            ->whereIn('status', Recommendation::PUBLIC_STATUSES)
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $query->where(function ($query) use ($filters): void {
                    $query
                        ->where('title', 'like', "%{$filters['q']}%")
                        ->orWhere('artist', 'like', "%{$filters['q']}%")
                        ->orWhere('channel_title', 'like', "%{$filters['q']}%")
                        ->orWhere('youtube_url', 'like', "%{$filters['q']}%");
                });
            })
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->when($filters['category'] !== '', fn ($query) => $query->where('category', $filters['category']))
            ->when($filters['tag'] !== '', fn ($query) => $query
                ->whereHas('creatorTags', fn ($query) => $query
                    ->where('creator_tags.creator_id', $creator->id)
                    ->where('creator_tags.slug', $filters['tag'])))
            ->with(['submittedBy:id,name', 'creatorTags:id,creator_id,name,slug'])
            ->withCount('userPicks');

        if ($request->user()) {
            $recommendationsQuery->withExists([
                'userPicks as picked_by_user' => fn ($query) => $query->where('user_id', $request->user()->id),
            ]);
        }

        $recommendationsQuery->orderByDesc('is_pinned');

        match ($filters['sort']) {
            'newest' => $recommendationsQuery->latest(),
            'status' => $recommendationsQuery->orderBy('status')->latest(),
            'scheduled' => $recommendationsQuery
                ->orderByRaw('scheduled_for IS NULL')
                ->orderBy('scheduled_for')
                ->latest(),
            default => $recommendationsQuery
                ->orderByDesc('user_picks_count')
                ->latest(),
        };

        $recommendations = $recommendationsQuery->get();
        $tagOptions = $creator->creatorTags()
            ->whereHas('recommendations', fn ($query) => $query
                ->whereIn('recommendations.status', Recommendation::PUBLIC_STATUSES))
            ->get();

        $usage = $request->user()
            ? $this->usageFor($request->user(), $creator)
            : null;
        $favoritesCount = $creator->creatorFavorites()->count();
        $isFavorited = $request->user()
            ? $creator->creatorFavorites()->where('user_id', $request->user()->id)->exists()
            : false;
        $ownsCreator = $request->user()
            ? $creator->creatorOwners()
                ->where('user_id', $request->user()->id)
                ->where('role', 'owner')
                ->exists()
            : false;

        return view('recommendations.creator-queue', compact(
            'categoryOptions',
            'creator',
            'filters',
            'favoritesCount',
            'isFavorited',
            'ownsCreator',
            'publicRecommendationsCount',
            'publicVotesCount',
            'recommendations',
            'statusOptions',
            'tagOptions',
            'topRequestedId',
            'usage',
        ));
    }

    public function create(Request $request, Creator $creator): View
    {
        $usage = $this->usageFor($request->user(), $creator);

        return view('recommendations.submit', compact('creator', 'usage'));
    }

    public function store(StoreRecommendationRequest $request, Creator $creator): RedirectResponse
    {
        if (! $creator->submissions_open) {
            throw ValidationException::withMessages([
                'submissions' => 'This creator is not accepting new recommendations right now.',
            ]);
        }

        $validated = $request->validated();
        unset($validated['confirm_favorite']);

        DB::transaction(function () use ($creator, $request, $validated): void {
            /** @var User $user */
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);

            $this->participation->ensureFavoritedForParticipation(
                $user,
                $creator,
                $request->boolean('confirm_favorite'),
                'suggest',
            );

            if (! $user->canSuggestTo($creator)) {
                throw ValidationException::withMessages([
                    'limit' => $user->suggestionsRemainingFor($creator) === 0
                        ? "You have used all suggestions for {$creator->display_name}."
                        : 'You’ve reached your creator favorite limit. Remove a favorite before suggesting something for this journey.',
                ]);
            }

            $creator->recommendations()->create([
                ...$validated,
                'submitted_by' => $user->id,
                'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
                'youtube_video_id' => $validated['recommendation_type'] === 'youtube'
                    ? $this->youtubeUrls->extractVideoId($validated['youtube_url'])
                    : null,
                'youtube_url' => $validated['recommendation_type'] === 'youtube'
                    ? $validated['youtube_url']
                    : null,
                'channel_title' => $validated['recommendation_type'] === 'youtube'
                    ? ($validated['channel_title'] ?? null)
                    : null,
                'status' => $creator->defaultRecommendationStatus(),
            ]);
        });

        return redirect()
            ->route('creator.queue', $creator)
            ->with(
                'success',
                $creator->autoApprovesRecommendations()
                    ? 'Recommendation submitted and added to the journey.'
                    : 'Recommendation submitted and waiting for creator review.',
            );
    }

    public function youtubeMetadata(Request $request, Creator $creator): JsonResponse
    {
        $request->merge([
            'youtube_url' => $request->input('youtube_url', $request->input('url')),
        ]);

        $validated = $request->validate([
            'youtube_url' => ['required', 'url'],
        ]);

        $youtubeUrl = $validated['youtube_url'];
        $videoId = $this->youtubeUrls->extractVideoId($youtubeUrl);

        if (! $videoId) {
            return response()->json([
                'message' => 'Enter a valid YouTube video URL.',
            ], 422);
        }

        try {
            $response = Http::timeout(5)
                ->acceptJson()
                ->get('https://www.youtube.com/oembed', [
                    'format' => 'json',
                    'url' => $youtubeUrl,
                ])
                ->throw()
                ->json();
        } catch (Throwable) {
            return response()->json([
                'video_id' => $videoId,
                'title' => '',
                'channel_title' => '',
                'message' => 'Could not load video details. You can still enter them manually.',
                'metadata_unavailable' => true,
            ]);
        }

        return response()->json([
            'video_id' => $videoId,
            'title' => $response['title'] ?? '',
            'channel_title' => $response['author_name'] ?? '',
        ]);
    }

    public function toggleVote(
        Request $request,
        Creator $creator,
        Recommendation $recommendation,
    ): RedirectResponse {
        $validated = $request->validate([
            'vote_action' => ['nullable', 'in:add,remove'],
        ]);
        $voteAction = $validated['vote_action'] ?? null;

        abort_unless(
            in_array($recommendation->status, Recommendation::PUBLIC_STATUSES, true),
            404,
        );

        if (! $recommendation->consumesUpvotes()) {
            throw ValidationException::withMessages([
                'limit' => 'This suggestion is no longer accepting upvotes.',
            ]);
        }

        $removed = DB::transaction(function () use ($creator, $recommendation, $request, $voteAction): bool {
            /** @var User $user */
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            $existingPick = $user->userPicks()
                ->where('recommendation_id', $recommendation->id)
                ->first();

            if ($existingPick) {
                if ($voteAction === 'add') {
                    return false;
                }

                $existingPick->delete();

                return true;
            }

            if ($voteAction === 'remove') {
                return true;
            }

            $this->participation->ensureFavoritedForUpvote($user, $creator);

            if ($user->votesRemainingFor($creator) === 0) {
                throw ValidationException::withMessages([
                    'limit' => 'You’ve used all your upvotes for this creator.',
                ]);
            }

            $user->userPicks()->firstOrCreate([
                'recommendation_id' => $recommendation->id,
            ], [
                'creator_id' => $creator->id,
            ]);

            return false;
        });

        return redirect()
            ->to(route('creator.queue', $creator)."#recommendation-{$recommendation->id}")
            ->with('recommendation_action', [
                'recommendation_id' => $recommendation->id,
                'message' => $removed ? 'Your upvote was removed.' : 'Your upvote was added.',
                'type' => $removed ? 'removed' : 'added',
            ]);
    }

    public function toggleFavorite(Request $request, Creator $creator): RedirectResponse
    {
        abort_if($creator->status !== 'active', 404);

        $isOwner = $creator->creatorOwners()
            ->where('user_id', $request->user()->id)
            ->where('role', 'owner')
            ->exists();

        if ($isOwner) {
            throw ValidationException::withMessages([
                'favorite' => 'Creators cannot favorite their own creator page.',
            ]);
        }

        return DB::transaction(function () use ($creator, $request): RedirectResponse {
            /** @var User $user */
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            $favorite = CreatorFavorite::query()
                ->where('creator_id', $creator->id)
                ->where('user_id', $user->id)
                ->first();

            if ($favorite) {
                $removedUpvotes = $user->userPicks()
                    ->where('creator_id', $creator->id)
                    ->delete();
                $favorite->delete();

                return back()->with(
                    'success',
                    $removedUpvotes > 0
                        ? 'Creator removed from your favorites. Your upvotes for this creator were removed.'
                        : 'Creator removed from your favorites.',
                );
            }

            $this->participation->ensureFavoritedForParticipation(
                $user,
                $creator,
                true,
                'favorite',
            );

            return back()->with('success', 'Creator added to your favorites.');
        });
    }

    /**
     * @return array<string, int|string|bool>
     */
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
}
