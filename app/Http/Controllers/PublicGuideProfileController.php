<?php

namespace App\Http\Controllers;

use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use App\ViewModels\PublicGuideAccoladeViewModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicGuideProfileController extends Controller
{
    public function __invoke(Request $request, string $handle, PublicGuideAccoladeViewModel $accolades): View
    {
        $guide = User::query()
            ->where('public_handle', strtolower($handle))
            ->where('public_profile_enabled', true)
            ->whereNotNull('public_display_name')
            ->with('guideAccolades')
            ->firstOrFail();

        $publicSuggestions = $guide->recommendationsSubmitted()
            ->where('submission_source', Recommendation::SUBMISSION_SOURCE_FAN)
            ->publiclyVisible();

        $completedSupport = UserPick::query()
            ->validHistoricalSupport()
            ->where('user_id', $guide->id)
            ->whereHas('recommendation', fn (Builder $query) => $query
                ->publiclyVisible()
                ->whereNotIn('status', Recommendation::votableStatuses()));

        $stats = [
            'suggestions' => (clone $publicSuggestions)->count(),
            'published' => (clone $publicSuggestions)->where('status', 'published')->count(),
            'votes_cast' => (int) (clone $completedSupport)->sum('vote_count'),
            'creators_supported' => (clone $completedSupport)->distinct()->count('creator_id'),
        ];

        $activeSupportCount = UserPick::query()
            ->where('user_id', $guide->id)
            ->whereHas('recommendation', fn (Builder $query) => $query
                ->publiclyVisible()
                ->votable())
            ->count();

        $publishedSuggestions = (clone $publicSuggestions)
            ->where('status', 'published')
            ->with('creator')
            ->withEffectiveVoteTotal()
            ->latest('published_at')
            ->limit(6)
            ->get();

        $suggestions = (clone $publicSuggestions)
            ->with('creator')
            ->withEffectiveVoteTotal()
            ->latest()
            ->paginate(10, ['*'], 'suggestions_page')
            ->withQueryString();

        $supportedRecommendations = (clone $completedSupport)
            ->with(['recommendation' => fn ($query) => $query
                ->with('creator')
                ->withEffectiveVoteTotal()])
            ->latest()
            ->paginate(10, ['*'], 'support_page')
            ->withQueryString();
        $publicAccolades = $accolades->forGuide($guide);

        return view('guides.show', compact(
            'activeSupportCount',
            'publicAccolades',
            'guide',
            'publishedSuggestions',
            'stats',
            'suggestions',
            'supportedRecommendations',
        ));
    }
}
