<?php

namespace App\Http\Controllers;

use App\Models\Recommendation;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();
        $ownedCreators = $user->ownedCreators()
            ->wherePivot('role', 'owner')
            ->orderBy('display_name')
            ->get();
        $favoriteCreators = $user->favoriteCreators()
            ->active()
            ->orderBy('display_name')
            ->limit(6)
            ->get();

        $resources = [
            'creator_favorites_used' => $user->creatorFavoritesUsed(),
            'creator_favorites_limit' => $user->creatorFavoriteLimit(),
            'active_upvotes' => $user->userPicks()
                ->whereHas('recommendation', fn ($query) => $query
                    ->whereIn('status', Recommendation::upvoteConsumingStatuses()))
                ->count(),
            'suggestions_submitted' => $user->recommendationsSubmitted()
                ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)
                ->count(),
        ];

        return view('dashboard', compact('favoriteCreators', 'ownedCreators', 'resources'));
    }
}
