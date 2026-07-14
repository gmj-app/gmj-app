<?php

namespace App\Http\Controllers;

use App\Events\RequestCreated;
use App\Events\VoteAllocated;
use App\Http\Requests\StoreRecommendationRequest;
use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\User;
use App\Services\Accolades\AccoladeShowcaseService;
use App\Services\CreatorParticipationService;
use App\Services\UnfavoriteCreatorAction;
use App\Services\YouTubePlaylistMetadataService;
use App\Services\YouTubeUrlService;
use App\ViewModels\CreatorPageHeaderViewModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Throwable;

class RecommendationController extends Controller
{
    public function __construct(
        private readonly CreatorParticipationService $participation,
        private readonly UnfavoriteCreatorAction $unfavoriteCreator,
        private readonly YouTubeUrlService $youtubeUrls,
        private readonly YouTubePlaylistMetadataService $playlistMetadata,
        private readonly AccoladeShowcaseService $accoladeShowcase,
        private readonly CreatorPageHeaderViewModel $creatorPageHeader,
    ) {}

    public function showCreatorQueue(Request $request, Creator $creator): View
    {
        abort_if($creator->status !== 'active' && ! $request->user()?->isSuperAdmin(), 404);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
            'status' => (string) $request->query('status', ''),
            'category' => (string) $request->query('category', ''),
            'tag' => (string) $request->query('tag', ''),
            'sort' => (string) $request->query('sort', 'votes'),
        ];
        $activePublicStatuses = Recommendation::activePublicStatuses();

        $filters['status'] = in_array($filters['status'], $activePublicStatuses, true)
            ? $filters['status']
            : '';
        $filters['sort'] = in_array($filters['sort'], ['votes', 'newest', 'status', 'scheduled'], true)
            ? $filters['sort']
            : 'votes';
        $filters['tag'] = $creator->creatorTags()
            ->where('slug', $filters['tag'])
            ->value('slug') ?? '';

        $header = $this->creatorPageHeader->forCreator($creator, $request->user());
        $publicRecommendationsCount = $header['metrics'][0]['value'];
        $ownsCreator = $header['context']['is_creator_owner'];
        $topRequestedId = $creator->recommendations()
            ->activePubliclyVisible()
            ->votable()
            ->withSum('userPicks as user_picks_count', 'vote_count')
            ->orderByDesc('user_picks_count')
            ->orderBy('created_at')
            ->orderBy('id')
            ->first()
            ?->id;

        $categoryOptions = $creator->recommendations()
            ->activePubliclyVisible()
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
        $statusOptions = collect($activePublicStatuses)
            ->mapWithKeys(fn (string $status) => [
                $status => Recommendation::STATUS_LABELS[$status],
            ]);

        $recommendationsQuery = $creator->recommendations()
            ->activePubliclyVisible()
            ->when($filters['q'] !== '', function ($query) use ($filters): void {
                $query->where(function ($query) use ($filters): void {
                    $query
                        ->where('title', 'like', "%{$filters['q']}%")
                        ->orWhere('display_title_override', 'like', "%{$filters['q']}%")
                        ->orWhere('source_title', 'like', "%{$filters['q']}%")
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
            ->withSum('userPicks as user_picks_count', 'vote_count');

        if ($request->user()) {
            $recommendationsQuery
                ->withSum([
                    'userPicks as current_user_votes_count' => fn ($query) => $query->where('user_id', $request->user()->id),
                ], 'vote_count');
        }

        match ($filters['sort']) {
            'newest' => $recommendationsQuery->orderByDesc('created_at')->orderByDesc('id'),
            'status' => $recommendationsQuery->orderBy('status')->orderBy('created_at')->orderBy('id'),
            'scheduled' => $recommendationsQuery
                ->orderByRaw('scheduled_for IS NULL')
                ->orderBy('scheduled_for')
                ->orderBy('created_at')
                ->orderBy('id'),
            default => $recommendationsQuery
                ->orderByDesc('user_picks_count')
                ->orderBy('created_at')
                ->orderBy('id'),
        };

        $recommendations = $recommendationsQuery
            ->paginate(25)
            ->withQueryString();
        $tagOptions = $creator->creatorTags()
            ->whereHas('recommendations', fn ($query) => $query
                ->activePubliclyVisible())
            ->get();
        $recentPublishedRecommendations = $creator->recommendations()
            ->where('status', 'published')
            ->withSum('userPicks as user_picks_count', 'vote_count')
            ->orderByRaw('COALESCE(published_at, updated_at, created_at) DESC')
            ->latest()
            ->limit(4)
            ->get();

        $usage = $header['guide_activity'];
        $isFavorited = $header['actions']['favorite_state'];
        $creatorAccolades = $header['accolade_showcase'];

        return view('recommendations.creator-queue', compact(
            'categoryOptions',
            'creator',
            'creatorAccolades',
            'filters',
            'header',
            'isFavorited',
            'ownsCreator',
            'publicRecommendationsCount',
            'recentPublishedRecommendations',
            'recommendations',
            'statusOptions',
            'tagOptions',
            'topRequestedId',
            'usage',
        ));
    }

    public function cardDetails(Request $request, Recommendation $recommendation): View
    {
        $creator = $recommendation->creator;
        abort_if(! $creator || $creator->status !== 'active' || ! $recommendation->isPubliclyVisible(), 404);

        $user = $request->user();
        $ownsCreator = $user
            ? $creator->creatorOwners()->where('user_id', $user->id)->where('role', 'owner')->exists()
            : false;

        $recommendation->load([
            'submittedBy:id,name,guide_number,public_display_name,public_handle,public_profile_enabled,avatar_url,email',
            'submittedBy.guideAccolades',
            'creatorTags:id,creator_id,name,slug',
            'userPicks' => fn ($query) => $query->oldest()->limit(10),
            'userPicks.user:id,name,guide_number,public_display_name,public_handle,public_profile_enabled,avatar_url,email',
            'userPicks.user.guideAccolades',
        ])->loadSum('userPicks as user_picks_count', 'vote_count')
            ->loadCount('userPicks as active_supporters_count');

        if ($user) {
            $recommendation->loadSum([
                'userPicks as current_user_votes_count' => fn ($query) => $query->where('user_id', $user->id),
            ], 'vote_count');
        }

        if ($ownsCreator) {
            $recommendation->load([
                'alternatives' => fn ($query) => $query
                    ->with(['user:id,name,public_display_name,public_handle,avatar_url,email'])
                    ->latest(),
            ]);
        }

        $usage = $user ? $this->creatorPageHeader->viewerContextFor($user, $creator) : null;
        $topRequestedId = $request->boolean('top') ? $recommendation->id : null;

        return view('recommendations.partials.card-details', compact(
            'creator', 'ownsCreator', 'recommendation', 'topRequestedId', 'usage'
        ));
    }

    public function published(Request $request, Creator $creator): View
    {
        abort_if($creator->status !== 'active', 404);

        $filters = [
            'q' => trim((string) $request->query('q', '')),
        ];

        $publishedRecommendationsQuery = $creator->recommendations()
            ->where('status', 'published')
            ->when($filters['q'] !== '', function ($query) use ($creator, $filters): void {
                $query->where(function ($query) use ($creator, $filters): void {
                    $query
                        ->where('title', 'like', "%{$filters['q']}%")
                        ->orWhere('display_title_override', 'like', "%{$filters['q']}%")
                        ->orWhere('source_title', 'like', "%{$filters['q']}%")
                        ->orWhere('artist', 'like', "%{$filters['q']}%")
                        ->orWhere('channel_title', 'like', "%{$filters['q']}%")
                        ->orWhere('description', 'like', "%{$filters['q']}%")
                        ->orWhere('reason', 'like', "%{$filters['q']}%")
                        ->orWhere('category', 'like', "%{$filters['q']}%")
                        ->orWhere('youtube_url', 'like', "%{$filters['q']}%")
                        ->orWhere('published_title', 'like', "%{$filters['q']}%")
                        ->orWhere('published_channel', 'like', "%{$filters['q']}%")
                        ->orWhere('published_reaction_url', 'like', "%{$filters['q']}%")
                        ->orWhereHas('creatorTags', fn ($query) => $query
                            ->where('creator_tags.creator_id', $creator->id)
                            ->where(function ($query) use ($filters): void {
                                $query
                                    ->where('creator_tags.name', 'like', "%{$filters['q']}%")
                                    ->orWhere('creator_tags.slug', 'like', "%{$filters['q']}%");
                            }));
                });
            })
            ->with([
                'submittedBy:id,name,guide_number,public_display_name,public_handle,public_profile_enabled,avatar_url,email',
                'submittedBy.guideAccolades',
                'creatorTags:id,creator_id,name,slug',
                'userPicks.user:id,name,guide_number,public_display_name,public_handle,public_profile_enabled,avatar_url,email',
                'userPicks.user.guideAccolades',
            ])
            ->withSum('userPicks as user_picks_count', 'vote_count')
            ->orderByDesc('published_at')
            ->orderByDesc('updated_at')
            ->latest();

        $publishedRecommendations = $publishedRecommendationsQuery
            ->paginate(25)
            ->withQueryString();
        $publishedRecommendationsCount = $creator->recommendations()
            ->where('status', 'published')
            ->count();
        $creatorAccolades = $this->accoladeShowcase->forSubject('creator', $creator->id);

        return view('recommendations.published', compact(
            'creator',
            'creatorAccolades',
            'filters',
            'publishedRecommendations',
            'publishedRecommendationsCount',
        ));
    }

    public function closed(Request $request, Creator $creator): View
    {
        abort_if($creator->status !== 'active', 404);

        $filters = [
            'status' => in_array($request->query('status'), Recommendation::CLOSED_PUBLIC_STATUSES, true)
                ? (string) $request->query('status')
                : '',
        ];

        $closedRecommendations = $creator->recommendations()
            ->publicClosed()
            ->when($filters['status'] !== '', fn ($query) => $query->where('status', $filters['status']))
            ->with(['submittedBy:id,name,guide_number,public_display_name,public_handle,public_profile_enabled,avatar_url,email'])
            ->withSum('allUserPicks as historical_support_count', 'vote_count')
            ->withCount('allUserPicks as historical_supporters_count')
            ->orderByRaw('COALESCE(resolved_at, updated_at, created_at) DESC')
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        $closedCounts = $creator->recommendations()
            ->publicClosed()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        return view('recommendations.closed', compact('closedCounts', 'closedRecommendations', 'creator', 'filters'));
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
                'submissions' => 'This creator is not accepting new requests right now.',
            ]);
        }

        $validated = $request->validated();
        unset($validated['confirm_favorite']);

        $normalized = $validated['recommendation_type'] === 'youtube'
            ? $this->youtubeUrls->normalize($validated['youtube_url'])
            : ['canonical_url' => null, 'youtube_video_id' => null, 'youtube_playlist_id' => null, 'media_type' => 'topic'];
        $duplicateRecommendation = $this->findDuplicateRecommendation($creator, $normalized);

        if ($duplicateRecommendation) {
            return redirect()
                ->route('recommendations.create', $creator)
                ->withInput()
                ->with('duplicate_recommendation', $this->duplicateRecommendationMessage($creator, $duplicateRecommendation));
        }

        $playlistMetadata = $normalized['media_type'] === 'playlist'
            ? $this->playlistMetadata->fetch($normalized['youtube_playlist_id'])
            : null;

        $createdRequestId = DB::transaction(function () use ($creator, $request, $validated, $normalized, $playlistMetadata): int {
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
                        ? "You've used all your requests for this creator."
                        : 'You’ve reached your creator favorite limit. Remove a favorite before suggesting something for this journey.',
                ]);
            }

            $createdRequest = $creator->recommendations()->create([
                ...$validated,
                'submitted_by' => $user->id,
                'submission_source' => Recommendation::SUBMISSION_SOURCE_FAN,
                'media_type' => $validated['recommendation_type'] === 'youtube' ? $normalized['media_type'] : 'topic',
                'youtube_video_id' => $validated['recommendation_type'] === 'youtube' ? $normalized['youtube_video_id'] : null,
                'youtube_playlist_id' => $validated['recommendation_type'] === 'youtube' ? $normalized['youtube_playlist_id'] : null,
                'normalized_url' => $validated['recommendation_type'] === 'youtube' ? $normalized['canonical_url'] : null,
                'youtube_url' => $validated['recommendation_type'] === 'youtube'
                    ? $normalized['canonical_url']
                    : null,
                'channel_title' => $validated['recommendation_type'] === 'youtube'
                    ? ($playlistMetadata['channel_title'] ?? $validated['channel_title'] ?? null)
                    : null,
                'thumbnail_url' => $playlistMetadata['thumbnail_url'] ?? null,
                'source_title' => $playlistMetadata['title'] ?? null,
                'source_channel' => $playlistMetadata['channel_title'] ?? null,
                'source_item_count' => $playlistMetadata['item_count'] ?? null,
                'source_metadata' => $playlistMetadata,
                'title' => filled($playlistMetadata['title'] ?? null) ? $playlistMetadata['title'] : $validated['title'],
                'status' => $creator->defaultRecommendationStatus(),
            ]);

            return $createdRequest->id;
        });

        try {
            RequestCreated::dispatch(
                $createdRequestId,
                $creator->id,
                $request->user()->id,
                $creator->defaultRecommendationStatus(),
                $request->user()->id,
                'guide',
            );
        } catch (Throwable $exception) {
            Log::error('Unable to queue new request workflows.', [
                'request_id' => $createdRequestId,
                'creator_id' => $creator->id,
                'exception' => $exception,
            ]);
        }

        return redirect()
            ->route('creator.queue', $creator)
            ->with(
                'success',
                $creator->autoApprovesRecommendations()
                    ? 'Request submitted and added to the journey.'
                    : 'Request submitted and waiting for creator review.',
            );
    }

    public function withdraw(
        Request $request,
        Creator $creator,
        Recommendation $recommendation,
    ): RedirectResponse {
        abort_unless((int) $recommendation->creator_id === (int) $creator->id, 404);

        if (! $recommendation->canBeWithdrawnBy($request->user())) {
            throw ValidationException::withMessages([
                'withdraw' => 'This request can no longer be withdrawn.',
            ]);
        }

        DB::transaction(function () use ($request, $recommendation): void {
            $lockedRecommendation = Recommendation::query()
                ->lockForUpdate()
                ->findOrFail($recommendation->id);

            if (! $lockedRecommendation->canBeWithdrawnBy($request->user())) {
                throw ValidationException::withMessages([
                    'withdraw' => 'This request can no longer be withdrawn.',
                ]);
            }

            $lockedRecommendation->update([
                'status' => 'withdrawn',
                'withdrawn_at' => now(),
                'withdrawn_by_user_id' => $request->user()->id,
            ]);
        });

        return redirect()
            ->route('creator.queue', $creator)
            ->with('success', 'Your request was withdrawn.');
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
        $normalized = $this->youtubeUrls->normalize($youtubeUrl);
        $videoId = $normalized['youtube_video_id'];

        if ($normalized['media_type'] === 'playlist') {
            $metadata = $this->playlistMetadata->fetch($normalized['youtube_playlist_id']);

            return response()->json([
                'media_type' => 'playlist',
                'playlist_id' => $normalized['youtube_playlist_id'],
                'canonical_url' => $normalized['canonical_url'],
                'title' => $metadata['title'] ?? '',
                'channel_title' => $metadata['channel_title'] ?? '',
                'thumbnail_url' => $metadata['thumbnail_url'] ?? null,
                'item_count' => $metadata['item_count'] ?? null,
                'message' => ($metadata['available'] ?? false)
                    ? null
                    : 'We found the playlist URL, but couldn’t load its details. You can still submit it.',
                'metadata_unavailable' => ! ($metadata['available'] ?? false),
            ]);
        }

        if (! $videoId) {
            return response()->json([
                'message' => 'Enter a valid YouTube video or playlist URL.',
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
            'media_type' => 'video',
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
            $recommendation->isPubliclyVisible(),
            404,
        );

        if (! $recommendation->isVotable()) {
            throw ValidationException::withMessages([
                'limit' => 'This request is no longer accepting votes.',
            ]);
        }

        $removed = DB::transaction(function () use ($creator, $recommendation, $request, $voteAction): bool {
            /** @var User $user */
            $user = User::query()->lockForUpdate()->findOrFail($request->user()->id);
            $existingPick = $user->userPicks()
                ->where('recommendation_id', $recommendation->id)
                ->lockForUpdate()
                ->first();
            $voteAction ??= 'add';

            if ($existingPick) {
                if ($voteAction === 'remove') {
                    if ($existingPick->vote_count > 1) {
                        $existingPick->decrement('vote_count');

                        return true;
                    }

                    $existingPick->delete();

                    return true;
                }

                if ($user->votesRemainingFor($creator) === 0) {
                    throw ValidationException::withMessages([
                        'limit' => "You've used all your votes for this creator. You'll get them back when supported requests are published or closed.",
                    ]);
                }

                $existingPick->increment('vote_count');

                return false;
            }

            if ($voteAction === 'remove') {
                return true;
            }

            $this->participation->ensureFavoritedForUpvote($user, $creator);

            if ($user->votesRemainingFor($creator) === 0) {
                throw ValidationException::withMessages([
                    'limit' => "You've used all your votes for this creator. You'll get them back when supported requests are published or closed.",
                ]);
            }

            $user->userPicks()->firstOrCreate([
                'recommendation_id' => $recommendation->id,
            ], [
                'creator_id' => $creator->id,
                'vote_count' => 1,
            ]);

            return false;
        });

        if (! $removed) {
            VoteAllocated::dispatch($request->user()->id, $creator->id, $recommendation->id, 1);
        }

        return redirect()
            ->to(route('creator.queue', $creator)."#recommendation-{$recommendation->id}")
            ->with('recommendation_action', [
                'recommendation_id' => $recommendation->id,
                'message' => $removed ? 'Your vote was removed.' : 'Your vote was added.',
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

        if ($creator->creatorFavorites()->where('user_id', $request->user()->id)->whereNull('released_at')->exists()) {
            $result = $this->unfavoriteCreator->handle($request->user(), $creator);

            return back()->with(
                'success',
                $result['removed_upvotes'] > 0
                    ? 'Creator removed from your favorites. Your active votes for this creator were removed.'
                    : 'Creator removed from your favorites.',
            );
        }

        return DB::transaction(function () use ($creator, $request): RedirectResponse {
            $this->participation->ensureFavoritedForParticipation(
                $request->user(),
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

    /**
     * @param  array{canonical_url: string|null, youtube_video_id: string|null, youtube_playlist_id: string|null, media_type: string|null}  $normalized
     */
    private function findDuplicateRecommendation(Creator $creator, array $normalized): ?Recommendation
    {
        $canonicalUrl = $normalized['canonical_url'];
        $videoId = $normalized['youtube_video_id'];
        $playlistId = $normalized['youtube_playlist_id'];

        if (! $canonicalUrl && ! $videoId && ! $playlistId) {
            return null;
        }

        return $creator->recommendations()
            ->where('status', '!=', 'withdrawn')
            ->where(function ($query) use ($canonicalUrl, $videoId, $playlistId, $normalized): void {
                if ($normalized['media_type'] === 'playlist' && $playlistId) {
                    $query
                        ->where('youtube_playlist_id', $playlistId)
                        ->orWhere('published_playlist_id', $playlistId);
                }

                if ($videoId) {
                    $query
                        ->where('youtube_video_id', $videoId)
                        ->orWhere('published_video_id', $videoId);
                }

                if ($canonicalUrl) {
                    $query
                        ->orWhere('normalized_url', $canonicalUrl)
                        ->orWhere('published_normalized_url', $canonicalUrl)
                        ->orWhere('youtube_url', $canonicalUrl)
                        ->orWhere('published_reaction_url', $canonicalUrl);
                }
            })
            ->orderByRaw("case when status = 'published' or published_reaction_url is not null then 0 else 1 end")
            ->latest()
            ->first();
    }

    /**
     * @return array{type: string, title: string, body: string, primary_label: string, primary_url: string, secondary_label: string|null, secondary_url: string|null}
     */
    private function duplicateRecommendationMessage(Creator $creator, Recommendation $recommendation): array
    {
        $isPublishedDuplicate = $recommendation->status === 'published'
            || filled($recommendation->published_reaction_url)
            || in_array($recommendation->status, ['already_seen', 'passed'], true);

        if ($isPublishedDuplicate) {
            $isPlaylist = $recommendation->isYouTubePlaylist() || $recommendation->isPublishedYouTubePlaylist();

            return [
                'type' => 'published',
                'title' => 'Already published',
                'body' => $isPlaylist ? 'This playlist has already been published.' : 'This creator has already published something for this request.',
                'primary_label' => 'View published request',
                'primary_url' => route('creators.published', $creator)."#recommendation-{$recommendation->id}",
                'secondary_label' => filled($recommendation->published_reaction_url) ? ($isPlaylist ? 'Open published playlist' : 'Open published video') : null,
                'secondary_url' => filled($recommendation->published_reaction_url) ? $recommendation->published_reaction_url : null,
            ];
        }

        return [
            'type' => 'active',
            'title' => 'Already suggested',
            'body' => $recommendation->isYouTubePlaylist()
                ? 'This playlist has already been suggested.'
                : 'This URL is already in the active request list for this creator.',
            'primary_label' => 'View request',
            'primary_url' => route('creator.queue', $creator)."#recommendation-{$recommendation->id}",
            'secondary_label' => null,
            'secondary_url' => null,
        ];
    }
}
