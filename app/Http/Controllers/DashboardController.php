<?php

namespace App\Http\Controllers;

use App\Services\GuideActivityService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GuideActivityService $activity): View
    {
        $user = $request->user();
        $ownedCreators = $user->ownedCreators()
            ->wherePivot('role', 'owner')
            ->orderBy('display_name')
            ->get();
        $activitySummary = $activity->summaryFor($user);

        $resources = [
            'creator_favorites_used' => $user->creatorFavoritesUsed(),
            'creator_favorites_limit' => $user->creatorFavoriteLimit(),
            'active_upvotes' => $activitySummary['active_vote_count'],
            'suggestions_submitted' => $activitySummary['suggestion_count'],
        ];

        return view('dashboard', compact(
            'activitySummary',
            'ownedCreators',
            'resources',
        ));
    }
}
