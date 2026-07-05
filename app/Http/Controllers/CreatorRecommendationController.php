<?php

namespace App\Http\Controllers;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Services\CreatorTagService;
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
            ->with(['submittedBy:id,name,email', 'creatorTags:id,creator_id,name,slug'])
            ->withCount('userPicks')
            ->when($filters['q'] ?? null, function ($query, string $search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('title', 'like', "%{$search}%")
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

        $releasedUpvotes = DB::transaction(function () use ($creator, $recommendation, $request, $shouldSyncTags, $tagInput, $validated, $publishedAttributes): int {
            $releasedUpvotes = $this->updateRecommendationAndReleaseUpvotes($recommendation, [
                ...$validated,
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

            return $releasedUpvotes;
        });

        return back()->with(
            'success',
            $this->statusMessage('Recommendation updated.', $releasedUpvotes),
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

        $publishedAttributes = $this->publishedAttributesFromRequest($validated, $recommendation, $request->exists('published_reaction_url'));

        $releasedUpvotes = $this->updateRecommendationAndReleaseUpvotes($recommendation, [
            'status' => $validated['status'],
            'scheduled_for' => $validated['status'] === 'scheduled'
                ? ($validated['scheduled_for'] ?? $recommendation->scheduled_for)
                : $recommendation->scheduled_for,
            'published_at' => $validated['status'] === 'published'
                ? ($validated['published_at'] ?? $recommendation->published_at ?? now())
                : $recommendation->published_at,
            ...$publishedAttributes,
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        return back()->with(
            'success',
            $this->statusMessage('Status updated.', $releasedUpvotes),
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

        $releasedUpvotes = $this->updateRecommendationAndReleaseUpvotes($recommendation, [
            ...$validated,
            'moderation_reason' => $validated['moderation_reason'] ?? 'creator_hidden',
            'status' => 'hidden',
            'moderated_by' => $request->user()->id,
            'moderated_at' => now(),
        ]);

        return back()->with(
            'success',
            $this->statusMessage('Recommendation hidden.', $releasedUpvotes),
        );
    }

    public function destroy(
        Request $request,
        Creator $creator,
        Recommendation $recommendation,
    ): RedirectResponse {
        Gate::authorize('manage', $creator);

        $recommendation->delete();

        return redirect()
            ->route('creators.recommendations.index', $creator)
            ->with('success', 'Recommendation permanently deleted.');
    }

    /**
     * @param  array<string, mixed>  $attributes
     */
    private function updateRecommendationAndReleaseUpvotes(
        Recommendation $recommendation,
        array $attributes,
    ): int {
        return DB::transaction(function () use ($recommendation, $attributes): int {
            $lockedRecommendation = Recommendation::query()
                ->lockForUpdate()
                ->findOrFail($recommendation->id);
            $newStatus = $attributes['status'] ?? $lockedRecommendation->status;
            $shouldReleaseUpvotes = $lockedRecommendation->shouldClearUpvotesWhenStatusIs($newStatus);

            $lockedRecommendation->update($attributes);

            return $shouldReleaseUpvotes
                ? $lockedRecommendation->userPicks()->delete()
                : 0;
        });
    }

    private function statusMessage(string $message, int $releasedUpvotes): string
    {
        if ($releasedUpvotes === 0) {
            return $message;
        }

        $upvotes = str('upvote')->plural($releasedUpvotes);
        $verb = $releasedUpvotes === 1 ? 'was' : 'were';

        return "{$message} {$releasedUpvotes} {$upvotes} {$verb} released back to users.";
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
            ];
        }

        $url = $validated['published_reaction_url'] ?? null;

        if (blank($url)) {
            return [
                'published_reaction_url' => null,
                'published_title' => null,
                'published_channel' => null,
                'published_thumbnail_url' => null,
                'published_video_id' => null,
                'published_metadata' => null,
            ];
        }

        $attributes = [
            'published_reaction_url' => $url,
        ];
        $videoId = $this->youtubeUrls->extractVideoId($url);

        if (! $videoId) {
            return [
                ...$attributes,
                'published_title' => null,
                'published_channel' => null,
                'published_thumbnail_url' => null,
                'published_video_id' => null,
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
}
