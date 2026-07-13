<?php

namespace App\Http\Controllers;

use App\Services\Accolades\GuideAccoladeSummaryService;
use App\Services\GuideActivityService;
use App\Services\GuideFavoriteCreatorService;
use App\Services\PlanEntitlementService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function __invoke(Request $request, GuideActivityService $activity, PlanEntitlementService $entitlements, GuideFavoriteCreatorService $favorites, GuideAccoladeSummaryService $accolades): View
    {
        $user = $request->user();
        $ownedCreators = $user->ownedCreators()
            ->wherePivot('role', 'owner')
            ->orderBy('display_name')
            ->get();
        $activitySummary = $activity->summaryFor($user);
        $limits = $entitlements->getLimitsForUser($user);
        $favoriteCreators = $favorites->activeFor($user);
        $accoladeSummary = $accolades->forDashboard($user);

        $resources = [
            'creator_favorites_used' => $favoriteCreators->count(),
            'creator_favorites_limit' => $limits['creator_favorites_limit'],
            'votes_per_creator' => $limits['upvotes_per_creator_limit'],
            'requests_per_creator' => $limits['suggestions_per_creator_limit'],
        ];

        return view('dashboard', compact(
            'activitySummary',
            'accoladeSummary',
            'favoriteCreators',
            'ownedCreators',
            'resources',
        ));
    }
}
