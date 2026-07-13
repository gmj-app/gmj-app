<?php

namespace App\Http\Controllers;

use App\Models\Creator;
use App\Models\HomepageAdvertisement;
use App\Models\Recommendation;
use App\Services\HomepageStatsService;
use App\Services\PopularCreatorGridService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request, HomepageStatsService $homepageStats, PopularCreatorGridService $gridService): View
    {
        $search = trim((string) $request->query('q', ''));

        $creators = Creator::query()
            ->active()
            ->when($search !== '', function ($query) use ($search): void {
                $query->where(function ($query) use ($search): void {
                    $query
                        ->where('display_name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%")
                        ->orWhere('youtube_channel_title', 'like', "%{$search}%")
                        ->orWhere('youtube_channel_url', 'like', "%{$search}%");
                });
            })
            ->withCount([
                'recommendations as visible_recommendations_count' => fn ($query) => $query
                    ->publiclyVisible(),
                'recommendations as published_recommendations_count' => fn ($query) => $query
                    ->where('status', 'published'),
            ])
            ->withSum([
                'userPicks as total_votes_count' => fn ($query) => $query
                    ->whereHas('recommendation', fn ($query) => $query->publiclyVisible()->votable()),
            ], 'vote_count')
            ->orderByDesc('total_votes_count')
            ->orderByDesc('visible_recommendations_count')
            ->orderBy('display_name')
            ->paginate(12)
            ->withQueryString();

        $topRequests = Recommendation::query()
            ->select(['id', 'creator_id', 'submitted_by', 'submission_source', 'title', 'source_title', 'display_title_override', 'is_pinned', 'created_at'])
            ->whereIn('creator_id', $creators->pluck('id'))
            ->publiclyVisible()
            ->votable()
            ->withSum('userPicks as user_picks_count', 'vote_count')
            ->orderByDesc('user_picks_count')
            ->orderByDesc('is_pinned')
            ->latest()
            ->get()
            ->groupBy('creator_id')
            ->map(fn ($requests) => $requests->take(3)->values());

        [
            'creatorCount' => $creatorCount,
            'guideCount' => $guideCount,
        ] = $homepageStats->counts();

        $advertisements = $search === ''
            ? HomepageAdvertisement::active()->orderBy('placement')->orderBy('id')->get()
            : collect();
        $gridItems = $gridService->compose($creators->getCollection(), $advertisements, $search === '');

        return view('home', compact('creatorCount', 'creators', 'gridItems', 'guideCount', 'search', 'topRequests'));
    }
}
