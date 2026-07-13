<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreStarterSuggestionsRequest;
use App\Http\Requests\UpdateCreatorProfileRequest;
use App\Models\Creator;
use App\Models\Recommendation;
use App\Services\CreatorLifecycleService;
use App\Services\CreatorProfileUpdateService;
use App\Services\CreatorSetupCompletenessService;
use App\Services\SuperAdminAuditService;
use App\Services\YouTubeUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class CreatorController extends Controller
{
    public function __construct(private readonly CreatorSetupCompletenessService $completeness, private readonly CreatorProfileUpdateService $profiles, private readonly SuperAdminAuditService $audit) {}

    public function index(Request $request): View
    {
        $filter = (string) $request->query('filter');
        $sort = (string) $request->query('sort', 'newest');
        $search = trim((string) $request->query('q'));
        $query = Creator::query()->with(['creatorOwners.user:id,name,public_display_name,email', 'creatorTags'])
            ->withCount(['recommendations as request_count', 'recommendations as published_count' => fn ($q) => $q->where('status', 'published'), 'creatorFavorites as follower_count' => fn ($q) => $q->whereNull('released_at')]);

        if ($filter === 'soft_deleted') {
            $query->onlyTrashed();
        } else {
            $query->withoutTrashed();
        }
        $query->when($search !== '', fn ($q) => $q->where(function ($q) use ($search) {
            $q->where('display_name', 'like', "%{$search}%")->orWhere('slug', 'like', "%{$search}%")
                ->orWhere('youtube_channel_title', 'like', "%{$search}%")->orWhere('youtube_channel_url', 'like', "%{$search}%")
                ->orWhereHas('creatorOwners.user', fn ($q) => $q->where('name', 'like', "%{$search}%")->orWhere('public_display_name', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%"));
        }));
        $query->when($filter === 'active', fn ($q) => $q->availableForGuides())
            ->when($filter === 'disabled', fn ($q) => $q->where(fn ($q) => $q->where('status', '!=', 'active')->orWhereNotNull('deactivated_at')))
            ->when($filter === 'moderation_enabled', fn ($q) => $q->where('recommendation_approval_mode', Creator::APPROVAL_MODE_MANUAL))
            ->when($filter === 'moderation_disabled', fn ($q) => $q->where('recommendation_approval_mode', Creator::APPROVAL_MODE_AUTO))
            ->when($filter === 'recent', fn ($q) => $q->where('created_at', '>=', now()->subDays(30)));
        match ($sort) {
            'oldest' => $query->oldest(), 'name' => $query->orderBy('display_name'), 'most_followers' => $query->orderByDesc('follower_count'),
            'most_requests' => $query->orderByDesc('request_count'), 'recently_updated' => $query->latest('updated_at'), default => $query->latest(),
        };
        $creators = $query->paginate(25)->withQueryString();
        $creators->getCollection()->transform(function ($creator) {
            $creator->setup = $this->completeness->evaluate($creator);

            return $creator;
        });
        if ($filter === 'incomplete') {
            $creators->setCollection($creators->getCollection()->filter(fn ($c) => $c->setup['percentage'] < 100)->values());
        }
        if ($sort === 'least_complete') {
            $creators->setCollection($creators->getCollection()->sortBy(fn ($c) => $c->setup['percentage'])->values());
        }

        return view('super-admin.creators.index', compact('creators', 'filter', 'search', 'sort'));
    }

    public function assist(Creator $creator): View
    {
        $creator->load(['creatorOwners.user:id,name,public_display_name,email', 'creatorTags']);
        $history = $creator->adminAuditLogs()->with('admin:id,name')->latest()->limit(10)->get();

        return view('super-admin.creators.assist', ['creator' => $creator, 'setup' => $this->completeness->evaluate($creator), 'history' => $history, 'categories' => Recommendation::CATEGORY_OPTIONS]);
    }

    public function update(UpdateCreatorProfileRequest $request, Creator $creator): RedirectResponse
    {
        $result = $this->profiles->update($creator, $request->validated(), $request->allFiles());
        $this->audit->record($request->user(), $creator, 'creator.profile.updated', 'Creator public-space settings updated.', $result['before'], $result['after'], ['assets' => $result['assets']], $request);

        return redirect()->route($request->input('save_action') === 'preview' ? 'super-admin.creators.preview' : 'super-admin.creators.assist', $creator)->with('success', 'Creator space updated successfully.');
    }

    public function starter(StoreStarterSuggestionsRequest $request, Creator $creator, YouTubeUrlService $youtube): RedirectResponse
    {
        $created = [];
        DB::transaction(function () use ($request, $creator, $youtube, &$created) {
            foreach ($request->filledSuggestions() as $item) {
                $videoId = $youtube->extractVideoId($item['url']);
                if (filled($item['url']) && $creator->recommendations()
                    ->where(function ($query) use ($item, $videoId): void {
                        $query->where('youtube_url', $item['url']);
                        if ($videoId) {
                            $query->orWhere('youtube_video_id', $videoId);
                        }
                    })->where('status', '!=', 'withdrawn')->exists()) {
                    throw ValidationException::withMessages([
                        'suggestions.0.url' => 'That link already exists in this creator’s requests.',
                    ]);
                }
                $created[] = $creator->recommendations()->create(['submitted_by' => $creator->creatorOwners()->where('role', 'owner')->value('user_id'), 'submission_source' => Recommendation::SUBMISSION_SOURCE_CREATOR, 'recommendation_type' => $videoId ? 'youtube' : 'topic', 'youtube_url' => $item['url'], 'youtube_video_id' => $videoId, 'title' => $item['title'], 'category' => $item['category'], 'reason' => $item['note'], 'status' => 'approved'])->id;
            }
        });
        $this->audit->record($request->user(), $creator, 'creator.starter_request.created', count($created).' starter request(s) created on behalf of the creator.', [], [], ['recommendation_ids' => $created], $request);
        Cache::flush();

        return back()->with('success', 'Starter requests added.');
    }

    public function preview(Creator $creator): RedirectResponse
    {
        return redirect()->route('creator.queue', $creator);
    }

    public function disable(Request $request, Creator $creator): RedirectResponse
    {
        $before = ['status' => $creator->status, 'deactivated_at' => $creator->deactivated_at];
        $creator->update(['status' => 'inactive', 'deactivated_at' => now(), 'submissions_open' => false]);
        $this->audit->record($request->user(), $creator, 'creator.disabled', 'Creator disabled and public resources released.', $before, ['status' => 'inactive'], [], $request);
        Cache::flush();

        return back()->with('success', 'Creator disabled.');
    }

    public function enable(Request $request, Creator $creator): RedirectResponse
    {
        $creator->update(['status' => 'active', 'deactivated_at' => null]);
        $this->audit->record($request->user(), $creator, 'creator.enabled', 'Creator enabled. Previously released Guide resources were not restored.', [], ['status' => 'active'], [], $request);
        Cache::flush();

        return back()->with('success', 'Creator enabled.');
    }

    public function destroy(Request $request, Creator $creator, CreatorLifecycleService $lifecycle): RedirectResponse
    {
        DB::transaction(function () use ($creator, $lifecycle) {
            $lifecycle->releaseResources($creator);
            $creator->delete();
        });
        $this->audit->record($request->user(), $creator, 'creator.soft_deleted', 'Creator soft deleted; Guide resources released and history preserved.', [], ['deleted_at' => $creator->deleted_at], [], $request);

        return back()->with('success', 'Creator soft deleted.');
    }

    public function restore(Request $request, int $creator): RedirectResponse
    {
        $model = Creator::withTrashed()->findOrFail($creator);
        $model->restore();
        $this->audit->record($request->user(), $model, 'creator.restored', 'Creator restored. Previously released Guide resources were not restored.', [], ['deleted_at' => null], [], $request);
        Cache::flush();

        return back()->with('success', 'Creator restored. Previous Guide allocations remain released.');
    }
}
