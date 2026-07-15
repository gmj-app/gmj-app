<?php

namespace App\Http\Controllers;

use App\Models\Recommendation;
use App\Models\User;
use App\Models\UserPick;
use App\Services\PublicGuideMetricsService;
use App\ViewModels\PublicGuideAccoladeViewModel;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\View\View;

class PublicGuideProfileController extends Controller
{
    public function __invoke(Request $request, string $handle, PublicGuideAccoladeViewModel $accolades, PublicGuideMetricsService $metrics): View
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

        $publicMetrics = $metrics->forGuide($guide);
        $stats = [
            'suggestions' => $publicMetrics['requests_count'],
            'published' => $publicMetrics['published_requests_count'],
            'votes_cast' => $publicMetrics['votes_cast_count'],
            'creators_supported' => $publicMetrics['creators_supported_count'],
        ];
        $activeSupportCount = $publicMetrics['active_requests_supported_count'];

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
