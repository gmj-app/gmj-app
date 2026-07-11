<?php

namespace App\Http\Controllers;

use App\Models\Recommendation;
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
        $guideActivity = $activity->forUser($user);
        $favoriteCreators = $guideActivity['creators'];
        $activeVotesByCreator = $guideActivity['activeVotesByCreator'];
        $suggestionsByCreator = $guideActivity['suggestionsByCreator'];

        $resources = [
            'creator_favorites_used' => $user->creatorFavoritesUsed(),
            'creator_favorites_limit' => $user->creatorFavoriteLimit(),
            'active_upvotes' => $user->userPicks()
                ->whereHas('recommendation', fn ($query) => $query->votable())
                ->sum('vote_count'),
            'suggestions_submitted' => $user->recommendationsSubmitted()
                ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)
                ->count(),
        ];

        return view('dashboard', compact(
            'activeVotesByCreator',
            'favoriteCreators',
            'ownedCreators',
            'resources',
            'suggestionsByCreator',
        ));
    }
}
