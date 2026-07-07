<?php

namespace App\Http\Controllers;

use App\Models\Creator;
use App\Models\Recommendation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CreatorDashboardController extends Controller
{
    public function index(Request $request): View
    {
        $creators = $request->user()
            ->ownedCreators()
            ->wherePivot('role', 'owner')
            ->orderBy('display_name')
            ->get();

        return view('creators.index', compact('creators'));
    }

    public function show(Request $request, Creator $creator): View
    {
        Gate::authorize('viewDashboard', $creator);

        $stats = [
            'recommendations' => $creator->recommendations()->count(),
            'pending' => $creator->recommendations()->where('status', 'pending')->count(),
            'votes' => $creator->userPicks()
                ->whereHas('recommendation', fn ($query) => $query
                    ->whereIn('status', Recommendation::upvoteConsumingStatuses()))
                ->sum('vote_count'),
            'followers' => $creator->creatorFavorites()->count(),
            'published' => $creator->recommendations()->where('status', 'published')->count(),
        ];

        $pendingRecommendations = $creator->recommendations()
            ->where('status', 'pending')
            ->withSum('userPicks as user_picks_count', 'vote_count')
            ->latest()
            ->limit(5)
            ->get();

        return view('creators.dashboard', compact('creator', 'pendingRecommendations', 'stats'));
    }

    public function followers(Request $request, Creator $creator): View
    {
        Gate::authorize('manage', $creator);

        $followers = $creator->favoritedBy()
            ->orderBy('public_display_name')
            ->orderBy('public_handle')
            ->paginate(25);

        return view('creators.followers', compact('creator', 'followers'));
    }
}
