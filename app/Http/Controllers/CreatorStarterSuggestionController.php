<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStarterSuggestionsRequest;
use App\Models\Creator;
use App\Models\Recommendation;
use App\Services\YouTubeUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class CreatorStarterSuggestionController extends Controller
{
    public function __construct(
        private readonly YouTubeUrlService $youtubeUrls,
    ) {}

    public function create(Creator $creator): View
    {
        Gate::authorize('manage', $creator);

        return view('creators.starter-suggestions', [
            'categories' => Recommendation::CATEGORY_OPTIONS,
            'creator' => $creator,
        ]);
    }

    public function store(
        StoreStarterSuggestionsRequest $request,
        Creator $creator,
    ): RedirectResponse {
        Gate::authorize('manage', $creator);

        $suggestions = $request->filledSuggestions();

        DB::transaction(function () use ($creator, $request, $suggestions): void {
            foreach ($suggestions as $suggestion) {
                $videoId = $this->youtubeUrls->extractVideoId($suggestion['url']);

                $creator->recommendations()->create([
                    'submitted_by' => $request->user()->id,
                    'submission_source' => Recommendation::SUBMISSION_SOURCE_CREATOR,
                    'recommendation_type' => $videoId ? 'youtube' : 'topic',
                    'youtube_url' => $suggestion['url'],
                    'youtube_video_id' => $videoId,
                    'title' => $suggestion['title'],
                    'category' => $suggestion['category'],
                    'reason' => $suggestion['note'],
                    'status' => 'approved',
                ]);
            }
        });

        return redirect()
            ->route('creators.dashboard', $creator)
            ->with(
                'success',
                $suggestions === []
                    ? 'No starter requests added. You can add them later.'
                    : 'Starter requests added. Your creator page is ready.',
            );
    }

    public function skip(Request $request, Creator $creator): RedirectResponse
    {
        Gate::authorize('manage', $creator);

        return redirect()
            ->route('creators.dashboard', $creator)
            ->with('success', 'You can add requests later from your dashboard.');
    }
}
