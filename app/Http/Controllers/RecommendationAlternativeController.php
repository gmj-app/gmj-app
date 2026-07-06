<?php

namespace App\Http\Controllers;

use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\RecommendationAlternative;
use App\Models\User;
use App\Services\YouTubeUrlService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RecommendationAlternativeController extends Controller
{
    public function __construct(
        private readonly YouTubeUrlService $youtubeUrls,
    ) {}

    public function store(Request $request, Creator $creator, Recommendation $recommendation): RedirectResponse
    {
        $this->ensurePublicRecommendation($creator, $recommendation);

        $validated = $request->validate([
            'alternative_url' => ['required', 'url', 'max:2048'],
            'reason' => ['required', 'string', 'max:500'],
        ]);

        $videoId = $this->youtubeUrls->extractVideoId($validated['alternative_url']);

        if (! $videoId) {
            throw ValidationException::withMessages([
                'alternative_url' => 'Enter a valid YouTube video URL.',
            ]);
        }

        $reason = $this->plainText($validated['reason']);

        if ($reason === '') {
            throw ValidationException::withMessages([
                'reason' => 'Tell the creator why this version may be a better fit.',
            ]);
        }

        $duplicateExists = $recommendation->alternatives()
            ->where('user_id', $request->user()->id)
            ->where('alternative_url', $validated['alternative_url'])
            ->exists();

        if ($duplicateExists) {
            throw ValidationException::withMessages([
                'alternative_url' => 'You already suggested this alternative for this request.',
            ]);
        }

        $recommendation->alternatives()->create([
            'user_id' => $request->user()->id,
            'alternative_url' => $validated['alternative_url'],
            'alternative_video_id' => $videoId,
            'reason' => $reason,
            'status' => RecommendationAlternative::STATUS_PENDING,
        ]);

        return redirect()
            ->to(route('creator.queue', $creator)."#recommendation-{$recommendation->id}")
            ->with('recommendation_action', [
                'recommendation_id' => $recommendation->id,
                'message' => 'Alternative sent privately to the creator.',
                'type' => 'alternative_submitted',
            ]);
    }

    public function accept(
        Request $request,
        Creator $creator,
        Recommendation $recommendation,
        RecommendationAlternative $alternative,
    ): RedirectResponse {
        $this->ensureOwnedAlternative($request, $creator, $recommendation, $alternative);

        DB::transaction(function () use ($request, $recommendation, $alternative): void {
            /** @var User $user */
            $user = $request->user();

            $recommendation->update([
                'youtube_url' => $alternative->alternative_url,
                'youtube_video_id' => $alternative->alternative_video_id,
            ]);

            $alternative->update([
                'status' => RecommendationAlternative::STATUS_ACCEPTED,
                'accepted_at' => now(),
                'dismissed_at' => null,
                'reviewed_at' => now(),
                'reviewed_by' => $user->id,
            ]);
        });

        return redirect()
            ->to(route('creator.queue', $creator)."#recommendation-{$recommendation->id}")
            ->with('success', 'Alternative accepted and applied to the request.');
    }

    public function dismiss(
        Request $request,
        Creator $creator,
        Recommendation $recommendation,
        RecommendationAlternative $alternative,
    ): RedirectResponse {
        $this->ensureOwnedAlternative($request, $creator, $recommendation, $alternative);

        $alternative->update([
            'status' => RecommendationAlternative::STATUS_DISMISSED,
            'accepted_at' => null,
            'dismissed_at' => now(),
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
        ]);

        return redirect()
            ->to(route('creator.queue', $creator)."#recommendation-{$recommendation->id}")
            ->with('success', 'Alternative dismissed.');
    }

    private function ensurePublicRecommendation(Creator $creator, Recommendation $recommendation): void
    {
        abort_if($creator->status !== 'active', 404);
        abort_unless($recommendation->creator_id === $creator->id, 404);
        abort_unless(in_array($recommendation->status, Recommendation::PUBLIC_STATUSES, true), 404);
    }

    private function ensureOwnedAlternative(
        Request $request,
        Creator $creator,
        Recommendation $recommendation,
        RecommendationAlternative $alternative,
    ): void {
        $this->ensurePublicRecommendation($creator, $recommendation);
        abort_unless($alternative->recommendation_id === $recommendation->id, 404);

        $ownsCreator = $creator->creatorOwners()
            ->where('user_id', $request->user()->id)
            ->where('role', 'owner')
            ->exists();

        abort_unless($ownsCreator, 403);
    }

    private function plainText(string $value): string
    {
        return trim((string) preg_replace('/\s+/', ' ', strip_tags($value)));
    }
}
