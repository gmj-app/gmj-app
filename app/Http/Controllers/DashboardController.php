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
            ->orderByPivot('created_at', 'desc')
            ->get();
        $favoriteCreatorIds = $favoriteCreators->pluck('id');

        $activeVotesByCreator = $user->userPicks()
            ->whereIn('creator_id', $favoriteCreatorIds)
            ->whereHas('recommendation', fn ($query) => $query->votable())
            ->with(['recommendation' => fn ($query) => $query->select([
                'id',
                'creator_id',
                'title',
                'status',
                'recommendation_type',
                'media_type',
            ])])
            ->orderByDesc('vote_count')
            ->latest()
            ->get()
            ->groupBy('creator_id');

        $suggestionsByCreator = $user->recommendationsSubmitted()
            ->whereIn('creator_id', $favoriteCreatorIds)
            ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)
            ->where('status', '!=', 'hidden')
            ->select([
                'id',
                'creator_id',
                'title',
                'status',
                'recommendation_type',
                'media_type',
                'created_at',
            ])
            ->latest()
            ->get()
            ->groupBy('creator_id');

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
