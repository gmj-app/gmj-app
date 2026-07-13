<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\SuperAdminRemoveRecommendationRequest;
use App\Http\Requests\SuperAdminRestoreRecommendationRequest;
use App\Http\Requests\SuperAdminTransitionRecommendationRequest;
use App\Http\Requests\SuperAdminUpdateRecommendationRequest;
use App\Models\Creator;
use App\Models\Recommendation;
use App\Services\CreatorTagService;
use App\Services\RecommendationStatusTransitionService;
use App\Services\SuperAdminAuditService;
use App\Services\YouTubeUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CreatorRequestController extends Controller
{
    public function __construct(private readonly RecommendationStatusTransitionService $transitions, private readonly SuperAdminAuditService $audit, private readonly YouTubeUrlService $urls, private readonly CreatorTagService $tags) {}

    public function index(Request $request, Creator $creator): View
    {
        $filters = $request->validate(['q' => ['nullable', 'string', 'max:255'], 'status' => ['nullable', Rule::in([...Recommendation::STATUSES, 'soft_deleted', 'spam'])], 'type' => ['nullable', Rule::in(['video', 'playlist', 'topic', 'link'])], 'sort' => ['nullable', Rule::in(['newest', 'oldest', 'most_votes', 'least_votes', 'updated', 'status', 'published'])]]);
        $query = $creator->recommendations()->with(['submittedBy:id,name,public_display_name,email', 'creatorTags:id,creator_id,name,slug'])
            ->withSum('allUserPicks as total_vote_quantity', 'vote_count')->withCount('allUserPicks as voter_count');
        if (in_array(($filters['status'] ?? ''), ['soft_deleted', 'spam'], true)) {
            $query->onlyTrashed();
        } else {
            $query->withoutTrashed();
        }
        $query->when($filters['q'] ?? null, function ($query, $search): void {
            $query->where(function ($query) use ($search): void {
                $query->where('title', 'like', "%{$search}%")->orWhere('youtube_url', 'like', "%{$search}%")->orWhere('published_title', 'like', "%{$search}%")->orWhere('published_reaction_url', 'like', "%{$search}%")->orWhere('channel_title', 'like', "%{$search}%")->orWhere('description', 'like', "%{$search}%")->orWhere('id', $search)
                    ->orWhereHas('submittedBy', fn ($q) => $q->where('public_display_name', 'like', "%{$search}%")->orWhere('name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"))
                    ->orWhereHas('creatorTags', fn ($q) => $q->where('name', 'like', "%{$search}%"));
            });
        })->when(($filters['status'] ?? null) && ! in_array($filters['status'], ['soft_deleted', 'spam'], true), fn ($q) => $q->where('status', $filters['status']))
            ->when(($filters['status'] ?? '') === 'spam', fn ($q) => $q->where('moderation_status', 'removed'))
            ->when($filters['type'] ?? null, fn ($q, $type) => $q->where(fn ($q) => $q->where('media_type', $type)->orWhere('recommendation_type', $type)));
        match ($filters['sort'] ?? 'newest') {
            'oldest' => $query->oldest(), 'most_votes' => $query->orderByDesc('total_vote_quantity'), 'least_votes' => $query->orderBy('total_vote_quantity'), 'updated' => $query->latest('updated_at'), 'status' => $query->orderBy('status'), 'published' => $query->latest('published_at'), default => $query->latest()
        };

        return view('super-admin.creators.requests.index', ['creator' => $creator, 'requests' => $query->paginate(25)->withQueryString(), 'filters' => $filters]);
    }

    public function edit(Creator $creator, int $recommendation): View
    {
        $item = $this->owned($creator, $recommendation, true)->load(['submittedBy:id,name,public_display_name,email', 'creatorTags', 'allUserPicks']);
        $history = $item->adminAuditLogs()->with('admin:id,name')->latest()->limit(20)->get();

        return view('super-admin.creators.requests.edit', ['creator' => $creator, 'recommendation' => $item, 'history' => $history, 'categories' => Recommendation::CATEGORY_OPTIONS]);
    }

    public function update(SuperAdminUpdateRecommendationRequest $request, Creator $creator, int $recommendation): RedirectResponse
    {
        $item = $this->owned($creator, $recommendation);
        $validated = $request->validated();
        if (! $item->updated_at->equalTo($validated['updated_at'])) {
            throw ValidationException::withMessages(['updated_at' => 'This request changed after you opened it. Reload and review the newer version.']);
        }
        $before = $item->only(['title', 'youtube_url', 'normalized_url', 'artist', 'channel_title', 'description', 'reason', 'category']);
        $attributes = collect($validated)->except(['tags', 'updated_at'])->all();
        if (array_key_exists('youtube_url', $attributes)) {
            $normalized = $this->urls->normalize($attributes['youtube_url']);
            if (filled($normalized['canonical_url']) && Recommendation::withTrashed()->where('creator_id', $creator->id)->where('id', '!=', $item->id)->where(fn ($q) => $q->where('normalized_url', $normalized['canonical_url'])->orWhere('published_normalized_url', $normalized['canonical_url']))->exists()) {
                throw ValidationException::withMessages(['youtube_url' => 'That URL already belongs to another request for this creator.']);
            }
            $attributes += ['normalized_url' => $normalized['canonical_url'], 'youtube_video_id' => $normalized['youtube_video_id'], 'youtube_playlist_id' => $normalized['youtube_playlist_id'], 'media_type' => $normalized['media_type']];
        }
        DB::transaction(function () use ($item, $attributes, $validated, $creator): void {
            $item->update($attributes);
            $this->tags->syncFromCommaSeparated($creator, $item, $validated['tags'] ?? '');
        });
        Cache::flush();
        $item->refresh();
        $this->audit->record($request->user(), $item, 'request.updated', 'Request public content updated on behalf of the creator.', $before, $item->only(array_keys($before)), ['creator_id' => $creator->id], $request);

        return back()->with('success', 'Request updated. Original attribution was preserved.');
    }

    public function status(SuperAdminTransitionRecommendationRequest $request, Creator $creator, int $recommendation): RedirectResponse
    {
        $item = $this->owned($creator, $recommendation);
        $result = $this->transitions->transition($item, $request->validated('status'), $request->user(), $request->validated(), 'super_admin');
        $action = $request->validated('status') === 'published' ? 'request.published' : 'request.status_changed';
        $this->audit->record($request->user(), $item, $action, 'Request status changed on behalf of the creator.', $result['before'], $result['after'], ['creator_id' => $creator->id, 'reason' => $request->validated('reason'), 'released_votes' => $result['released_votes'], 'affected_guides' => $result['affected_guides']], $request);

        return back()->with('success', 'Request status updated. '.$result['released_votes'].' active votes released.');
    }

    public function destroy(SuperAdminRemoveRecommendationRequest $request, Creator $creator, int $recommendation): RedirectResponse
    {
        $item = $this->owned($creator, $recommendation);
        $votes = (int) $item->userPicks()->sum('vote_count');
        $guides = $item->userPicks()->distinct('user_id')->count('user_id');
        DB::transaction(function () use ($item, $request): void {
            $now = now();
            $item->userPicks()->update(['released_at' => $now, 'release_reason' => 'request_removed']);
            $item->update(['status' => 'hidden', 'moderation_status' => 'removed', 'moderation_reason' => $request->validated('moderation_reason'), 'moderation_note' => $request->validated('moderation_note'), 'moderated_by' => $request->user()->id, 'moderated_at' => $now, 'resource_released_at' => $now, 'resource_release_reason' => 'request_removed']);
            $item->delete();
        });
        Cache::flush();
        $this->audit->record($request->user(), $item, 'request.soft_deleted', 'Request removed as spam or abuse.', [], ['moderation_reason' => $request->validated('moderation_reason'), 'deleted_at' => $item->deleted_at], ['creator_id' => $creator->id, 'released_votes' => $votes, 'affected_guides' => $guides], $request);

        return redirect()->route('super-admin.creators.requests.index', $creator)->with('success', 'Request removed. Active votes and request capacity were released.');
    }

    public function restore(SuperAdminRestoreRecommendationRequest $request, Creator $creator, int $recommendation): RedirectResponse
    {
        $item = $this->owned($creator, $recommendation, true);
        abort_unless($item->trashed(), 404);
        DB::transaction(function () use ($item, $request): void {
            $item->restore();
            $item->update(['status' => $request->validated('status'), 'moderation_status' => 'restored']);
        });
        Cache::flush();
        $this->audit->record($request->user(), $item, 'request.restored', 'Request restored without reactivating released resources.', ['deleted_at' => $item->deleted_at], ['status' => $item->status, 'deleted_at' => null], ['creator_id' => $creator->id], $request);

        return redirect()->route('super-admin.creators.requests.edit', [$creator, $item])->with('success', 'Request restored. Previous votes and request capacity remain released.');
    }

    private function owned(Creator $creator, int $id, bool $withTrashed = false): Recommendation
    {
        return Recommendation::query()->when($withTrashed, fn ($q) => $q->withTrashed())->where('creator_id', $creator->id)->findOrFail($id);
    }
}
