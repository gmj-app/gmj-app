<?php

namespace App\Http\Controllers;

use App\Models\Creator;
use App\Models\Recommendation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HomeController extends Controller
{
    public function index(Request $request): View
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
                    ->whereIn('status', Recommendation::PUBLIC_STATUSES),
                'userPicks as total_votes_count' => fn ($query) => $query
                    ->whereHas('recommendation', fn ($query) => $query
                        ->whereIn('status', Recommendation::upvoteConsumingStatuses())),
            ])
            ->orderByDesc('total_votes_count')
            ->orderByDesc('visible_recommendations_count')
            ->orderBy('display_name')
            ->paginate(12)
            ->withQueryString();

        $topRequests = Recommendation::query()
            ->select(['id', 'creator_id', 'title', 'is_pinned', 'created_at'])
            ->whereIn('creator_id', $creators->pluck('id'))
            ->whereIn('status', Recommendation::PUBLIC_STATUSES)
            ->withCount('userPicks')
            ->orderByDesc('user_picks_count')
            ->orderByDesc('is_pinned')
            ->latest()
            ->get()
            ->groupBy('creator_id')
            ->map(fn ($requests) => $requests->take(3)->values());

        return view('home', compact('creators', 'search', 'topRequests'));
    }
}
