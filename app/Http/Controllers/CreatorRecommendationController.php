<?php

namespace App\Http\Controllers;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\RequestPresentationRevision;
use App\Services\CreatorTagService;
use App\Services\RecommendationStatusTransitionService;
use App\Services\RequestPresentationService;
use App\Services\YouTubePlaylistMetadataService;
use App\Services\YouTubeUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Http;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use Throwable;

class CreatorRecommendationController extends Controller
{
    public function __construct(
        private readonly CreatorTagService $tags,
        private readonly YouTubeUrlService $youtubeUrls,
        private readonly YouTubePlaylistMetadataService $playlistMetadata,
        private readonly RecommendationStatusTransitionService $transitions,
    ) {}

    public function index(Request $request, Creator $creator): View
    {
        Gate::authorize('manage', $creator);

        $filters = $request->validate([
            'q' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', Rule::in(Recommendation::STATUSES)],
            'category' => ['nullable', Rule::in(['music', 'documentary', 'culture', 'interview', 'other'])],
            'tag' => ['nullable', 'string', 'max:60'],
            'sort' => ['nullable', Rule::in(['newest', 'votes', 'status', 'scheduled'])],
        ]);
        $filters['tag'] = $creator->creatorTags()
            ->where('slug', $filters['tag'] ?? '')
            ->value('slug');

        $sort = $filters['sort'] ?? 'newest';

        $recommendations = $creator->recommendations()
            ->with(['submittedBy:id,name,email', 'creatorTags:id,creator_id,name,slug', 'presentationRevisions' => fn ($query) => $query->with('actor:id,name')->latest()->limit(5), 'identityCorrections' => fn ($query) => $query->where('status', 'pending')->with('requester:id,name,email')->latest()])
            ->withSum('userPicks as user_picks_count', 'vote_count')
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('title', 'like', "%{$search}%")
                        ->orWhere('display_title_override', 'like', "%{$search}%")
                        ->orWhere('source_title', 'like', "%{$search}%")
                        ->orWhere('artist', 'like', "%{$search}%")
                        ->orWhere('channel_title', 'like', "%{$search}%")
                        ->orWhere('youtube_url', 'like', "%{$search}%");
                });
            })
            ->when($filters['status'] ?? null, fn ($query, string $status) => $query->where('status', $status))
            ->when($filters['category'] ?? null, fn ($query, string $category) => $query->where('category', $category))
            ->when($filters['tag'] ?? null, fn ($query, string $tag) => $query
                ->whereHas('creatorTags', fn ($query) => $query
                    ->where('creator_tags.creator_id', $creator->id)
                    ->where('creator_tags.slug', $tag)))
            ->when($sort === 'votes', fn ($query) => $query->orderByDesc('user_picks_count')->latest())
            ->when($sort === 'status', fn ($query) => $query->orderBy('status')->latest())
            ->when($sort === 'scheduled', fn ($query) => $query->orderByRaw('scheduled_for is null')->orderBy('scheduled_for')->latest())
            ->when($sort === 'newest', fn ($query) => $query->latest())
            ->paginate(25)
            ->withQueryString();

        $categories = ['music', 'documentary', 'culture', 'interview', 'other'];
        $statuses = Recommendation::STATUSES;
        $tagOptions = $creator->creatorTags()->get();

        return view('creators.recommendations.index', compact('categories', 'creator', 'filters', 'recommendations', 'statuses', 'tagOptions'));
    }

    public function update(Request $request, Creator $creator, Recommendation $recommendation): RedirectResponse
    {
        Gate::authorize('manage', $creator);
        $previousStatus = (string) $recommendation->status;

        $validated = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'artist' => ['nullable', 'string', 'max:255'],
            'channel_title' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(['music', 'documentary', 'culture', 'interview', 'other'])],
            'description' => ['nullable', 'string', 'max:2000'],
            'reason' => ['nullable', 'string', 'max:2000'],
            'youtube_url' => ['nullable', 'url', 'max:2048'],
            'status' => ['required', Rule::in(Recommendation::STATUSES)],
            'scheduled_for' => ['nullable', 'date'],
            'published_at' => ['nullable', 'date'],
            'published_reaction_url' => ['nullable', 'url', 'max:2048'],
            'moderation_reason' => ['nullable', 'string', 'max:255'],
            'moderation_note' => ['nullable', 'string', 'max:2000'],
            'is_pinned' => ['sometimes', 'boolean'],
            'tags' => ['nullable', 'string', 'max:300'],
        ]);
        $shouldSyncTags = $request->exists('tags');
        $tagInput = $validated['tags'] ?? '';
        unset($validated['tags']);

        $publishedAttributes = $this->publishedAttributesFromRequest($validated, $recommendation, $request->exists('published_reaction_url'));
        $originalUrlAttributes = $request->exists('youtube_url')
            ? $this->originalUrlAttributesFromRequest($validated)
            : [];

        $releasedVotes = DB::transaction(function () use ($creator, $recommendation, $request, $shouldSyncTags, $tagInput, $validated, $publishedAttributes, $originalUrlAttributes): int {
            $releasedVotes = $this->updateRecommendation($recommendation, [
                ...$validated,
                ...$originalUrlAttributes,
                ...$publishedAttributes,
                'is_pinned' => $request->boolean('is_pinned'),
                'published_at' => ($validated['status'] ?? $recommendation->status) === 'published'
                    ? ($validated['published_at'] ?? $recommendation->published_at ?? now())
                    : ($validated['published_at'] ?? null),
                'moderated_by' => $request->user()->id,
                'moderated_at' => now(),
            ]);

            if ($shouldSyncTags) {
                $this->tags->syncFromCommaSeparated($creator, $recommendation, $tagInput);
            }

            return $releasedVotes;
        });
        $recommendation->refresh();
        $this->transitions->dispatchPublicationIfNew($recommendation, $previousStatus, $request->user(), 'creator');

        return back()->with(
            'success',
            $this->statusMessage('Request updated.', $releasedVotes),
        );
    }

    public function updateStatus(
        Request $request,
        Creator $creator,
        Recommendation $recommendation,
    ): RedirectResponse {
        Gate::authorize('manage', $creator);

        $validated = $request->validate([
            'status' => ['required', Rule::in(Recommendation::STATUSES)],
            'scheduled_for' => ['nullable', 'date'],
            'published_at' => ['nullable', 'date'],
            'published_reaction_url' => ['nullable', 'url', 'max:2048'],
        ]);

        $result = $this->transitions->transition($recommendation, $validated['status'], $request->user(), $validated, 'creator');

        return back()->with(
            'success',
            $this->statusMessage('Status updated.', $result['released_votes']),
        );
    }

    public function hide(
        Request $request,
        Creator $creator,
        Recommendation $recommendation,
    ): RedirectResponse {
        Gate::authorize('manage', $creator);

        $validated = $request->validate([
            'moderation_reason' => ['nullable', Rule::in(['inappropriate', 'creator_hidden'])],
            'moderation_note' => ['nullable', 'string', 'max:2000'],
        ]);

        $result = $this->transitions->transition($recommendation, 'hidden', $request->user(), [
            ...$validated,
            'moderation_reason' => $validated['moderation_reason'] ?? 'creator_hidden',
        ], 'creator');

        return back()->with(
            'success',
            $this->statusMessage('Request hidden.', $result['released_votes']),
        );
    }

    public function destroy(
        Request $request,
        Creator $creator,
        Recommendation $recommendation,
    ): RedirectResponse {
        Gate::authorize('manage', $creator);

        $recommendation->forceDelete();

        return redirect()
            ->route('creators.recommendations.index', $creator)
            ->with('success', 'Request permanently deleted.');
    }

    public function clearPresentation(Request $request, Creator $creator, Recommendation $recommendation, RequestPresentationService $service): RedirectResponse
    {
        Gate::authorize('manage', $creator);
        abort_unless((int) $recommendation->creator_id === (int) $creator->id, 404);
        $service->update($recommendation, $request->user(), ['display_title_override' => null, 'request_context' => null], 'creator', 'request.display_title_override_cleared');

        return back()->with('success', 'Guide presentation override cleared. Canonical request content is now shown.');
    }

    public function revertPresentation(Request $request, Creator $creator, Recommendation $recommendation, RequestPresentationRevision $revision, RequestPresentationService $service): RedirectResponse
    {
        Gate::authorize('manage', $creator);
        abort_unless((int) $recommendation->creator_id === (int) $creator->id, 404);
        $service->revert($recommendation, $revision, $request->user(), 'creator');

        return back()->with('success', 'Guide presentation reverted to the selected revision.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function updateRecommendation(
        Recommendation $recommendation,
        array $attributes,
    ): int {
        return DB::transaction(function () use ($recommendation, $attributes): int {
            $lockedRecommendation = Recommendation::query()
                ->lockForUpdate()
                ->findOrFail($recommendation->id);
            $newStatus = $attributes['status'] ?? $lockedRecommendation->status;
            $releasedVotes = $lockedRecommendation->shouldClearUpvotesWhenStatusIs($newStatus)
                ? (int) $lockedRecommendation->userPicks()->sum('vote_count')
                : 0;

            $lockedRecommendation->update($attributes);

            return $releasedVotes;
        });
    }

    private function statusMessage(string $message, int $releasedVotes): string
    {
        if ($releasedVotes === 0) {
            return $message;
        }

        $votes = str('vote')->plural($releasedVotes);
        $verb = $releasedVotes === 1 ? 'is' : 'are';

        return "{$message} {$releasedVotes} {$votes} {$verb} no longer active and returned to Guides.";
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function publishedAttributesFromRequest(
        array $validated,
        Recommendation $recommendation,
        bool $urlWasSubmitted,
    ): array {
        if (! $urlWasSubmitted) {
            return [
                'published_reaction_url' => $recommendation->published_reaction_url,
                'published_normalized_url' => $recommendation->published_normalized_url,
            ];
        }

        $url = $validated['published_reaction_url'] ?? null;

        if (blank($url)) {
            return [
                'published_reaction_url' => null,
                'published_normalized_url' => null,
                'published_title' => null,
                'published_channel' => null,
                'published_thumbnail_url' => null,
                'published_video_id' => null,
                'published_media_type' => null,
                'published_playlist_id' => null,
                'published_item_count' => null,
                'published_metadata' => null,
            ];
        }

        $attributes = [
            'published_reaction_url' => $url,
        ];
        $normalized = $this->youtubeUrls->normalize($url);
        $videoId = $normalized['youtube_video_id'];
        $playlistId = $normalized['youtube_playlist_id'];
        $attributes['published_normalized_url'] = $normalized['canonical_url'];
        $attributes['published_media_type'] = $normalized['media_type'];
        $attributes['published_playlist_id'] = null;
        $attributes['published_item_count'] = null;

        if ($normalized['media_type'] === 'playlist' && $playlistId) {
            $metadata = $this->playlistMetadata->fetch($playlistId);

            return [
                ...$attributes,
                'published_reaction_url' => $normalized['canonical_url'],
                'published_video_id' => null,
                'published_playlist_id' => $playlistId,
                'published_title' => $metadata['title'] ?? null,
                'published_channel' => $metadata['channel_title'] ?? null,
                'published_thumbnail_url' => $metadata['thumbnail_url'] ?? null,
                'published_item_count' => $metadata['item_count'] ?? null,
                'published_metadata' => $metadata,
            ];
        }

        if (! $videoId) {
            return [
                ...$attributes,
                'published_title' => null,
                'published_channel' => null,
                'published_thumbnail_url' => null,
                'published_video_id' => null,
                'published_playlist_id' => null,
                'published_item_count' => null,
                'published_metadata' => null,
            ];
        }

        $attributes['published_video_id'] = $videoId;
        $attributes['published_thumbnail_url'] = "https://img.youtube.com/vi/{$videoId}/hqdefault.jpg";

        try {
            $metadata = Http::timeout(5)
                ->acceptJson()
                ->get('https://www.youtube.com/oembed', [
                    'format' => 'json',
                    'url' => $url,
                ])
                ->throw()
                ->json();
        } catch (Throwable) {
            return [
                ...$attributes,
                'published_title' => null,
                'published_channel' => null,
                'published_metadata' => [
                    'metadata_unavailable' => true,
                ],
            ];
        }

        return [
            ...$attributes,
            'published_title' => $metadata['title'] ?? null,
            'published_channel' => $metadata['author_name'] ?? null,
            'published_metadata' => $metadata,
        ];
    }

    /**
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function originalUrlAttributesFromRequest(array $validated): array
    {
        $url = $validated['youtube_url'] ?? null;

        if (blank($url)) {
            return [
                'normalized_url' => null,
                'youtube_video_id' => null,
                'youtube_playlist_id' => null,
                'media_type' => $validated['recommendation_type'] === 'topic' ? 'topic' : null,
            ];
        }

        $normalized = $this->youtubeUrls->normalize($url);

        return [
            'normalized_url' => $normalized['canonical_url'],
            'youtube_video_id' => $normalized['youtube_video_id'],
            'youtube_playlist_id' => $normalized['youtube_playlist_id'],
            'media_type' => $normalized['media_type'],
        ];
    }
}
