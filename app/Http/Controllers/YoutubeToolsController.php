<?php

namespace App\Http\Controllers;

use App\Jobs\ApplyYoutubeDescriptionUpdates;
use App\Models\VideoToolAuditLog;
use App\Models\YoutubeChannelToken;
use App\Services\Youtube\DescriptionChange;
use App\Services\Youtube\DescriptionPlanner;
use App\Services\Youtube\DescriptionPreview;
use App\Services\Youtube\DescriptionUpdateOptions;
use App\Services\Youtube\YoutubeApiClient;
use App\Services\Youtube\YoutubeDescriptionUpdater;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Laravel\Socialite\Facades\Socialite;
use Throwable;

class YoutubeToolsController extends Controller
{
    public const YOUTUBE_SCOPE = 'https://www.googleapis.com/auth/youtube.force-ssl';

    public function index(Request $request): View
    {
        return view('tools.youtube', [
            'enabled' => $this->youtubeEnabled(),
            'token' => $this->tokenFor($request),
            'preview' => $this->previewFromSession($request),
            'lastBatchId' => $request->session()->get('youtube_tools.last_batch_id'),
        ]);
    }

    public function connect(): RedirectResponse
    {
        if (! $this->youtubeEnabled()) {
            abort(403, 'YouTube video tools are disabled.');
        }

        $this->configureYoutubeRedirect();

        return Socialite::driver('google')
            ->scopes([self::YOUTUBE_SCOPE])
            ->with([
                'access_type' => 'offline',
                'prompt' => 'consent',
                'include_granted_scopes' => 'true',
            ])
            ->redirect();
    }

    public function callback(Request $request, YoutubeApiClient $client): RedirectResponse
    {
        if (! $this->youtubeEnabled()) {
            abort(403, 'YouTube video tools are disabled.');
        }

        $this->configureYoutubeRedirect();

        try {
            $googleUser = Socialite::driver('google')->user();
        } catch (Throwable) {
            return redirect()->route('tools.youtube.index')
                ->with('status', 'YouTube authorization could not be completed. Please try again.');
        }

        $existingToken = $this->tokenFor($request);

        $token = YoutubeChannelToken::query()->updateOrCreate(
            ['user_id' => $request->user()->id],
            [
                'google_account_id' => (string) $googleUser->getId(),
                'access_token' => (string) $googleUser->token,
                'refresh_token' => $googleUser->refreshToken ?: $existingToken?->refresh_token,
                'expires_at' => Carbon::now()->addSeconds((int) ($googleUser->expiresIn ?? 3600) - 60),
            ],
        );

        try {
            $uploadedVideos = $client->uploadedVideos($token);

            $token->forceFill([
                'channel_id' => $uploadedVideos['channel_id'],
                'channel_title' => $uploadedVideos['channel_title'],
            ])->save();
        } catch (Throwable) {
            // Channel metadata can be recovered on preview; keep the token.
        }

        return redirect()->route('tools.youtube.index')
            ->with('status', 'YouTube channel connected.');
    }

    public function preview(
        Request $request,
        YoutubeApiClient $client,
        DescriptionPlanner $planner,
    ): RedirectResponse {
        if (! $this->youtubeEnabled()) {
            abort(403, 'YouTube video tools are disabled.');
        }

        $validated = $this->validatedToolInput($request);
        $token = $this->tokenFor($request);

        if (! $token) {
            return redirect()->route('tools.youtube.index')
                ->with('status', 'Connect a YouTube channel before previewing updates.');
        }

        $options = $this->optionsFromValidated($validated);
        $batchId = (string) Str::uuid();

        try {
            $uploadedVideos = $client->uploadedVideos($token);
            $preview = $planner->preview($uploadedVideos['videos'], $options);

            $token->forceFill([
                'channel_id' => $uploadedVideos['channel_id'],
                'channel_title' => $uploadedVideos['channel_title'],
            ])->save();
        } catch (Throwable $throwable) {
            return redirect()->route('tools.youtube.index')
                ->withInput()
                ->with('status', 'Preview failed: '.$throwable->getMessage());
        }

        foreach ($preview->changes as $change) {
            VideoToolAuditLog::query()->create([
                'user_id' => $request->user()->id,
                'operation_batch_id' => $batchId,
                'video_id' => $change->videoId,
                'video_title' => $change->videoTitle,
                'action' => $change->action,
                'status' => $change->changed() ? 'previewed' : 'skipped',
                'message' => $change->message,
                'metadata' => [
                    'old_description_hash' => hash('sha256', $change->oldDescription),
                    'new_description_hash' => hash('sha256', $change->newDescription),
                ],
            ]);
        }

        $request->session()->put('youtube_tools.preview', $preview->toArray());
        $request->session()->put('youtube_tools.batch_id', $batchId);
        $request->session()->forget('youtube_tools.last_batch_id');

        return redirect()->route('tools.youtube.index')
            ->with('status', 'Preview generated. Review the warnings before applying updates.');
    }

    public function apply(Request $request, YoutubeDescriptionUpdater $updater): RedirectResponse
    {
        if (! $this->youtubeEnabled()) {
            abort(403, 'YouTube video tools are disabled.');
        }

        $request->validate([
            'confirm_bulk_update' => ['accepted'],
        ]);

        $token = $this->tokenFor($request);
        $preview = $this->previewFromSession($request);
        $batchId = (string) $request->session()->get('youtube_tools.batch_id');

        if (! $token || ! $preview || $preview->changedVideos()->isEmpty() || $batchId === '') {
            return redirect()->route('tools.youtube.index')
                ->with('status', 'Generate a preview with changes before applying updates.');
        }

        $changes = $preview->changedVideos()
            ->map(fn (DescriptionChange $change) => $change->toArray())
            ->values()
            ->all();

        if (count($changes) > 25) {
            ApplyYoutubeDescriptionUpdates::dispatch($request->user()->id, $changes, $batchId);
            $message = 'Bulk update queued. Keep the queue worker running to apply all changes.';
        } else {
            foreach ($changes as $change) {
                $updater->applyChange($request->user(), $token, DescriptionChange::fromArray($change), $batchId);
            }

            $message = 'YouTube description updates applied.';
        }

        $request->session()->forget(['youtube_tools.preview', 'youtube_tools.batch_id']);
        $request->session()->put('youtube_tools.last_batch_id', $batchId);

        return redirect()->route('tools.youtube.index')->with('status', $message);
    }

    private function youtubeEnabled(): bool
    {
        return (bool) config('services.youtube.enabled');
    }

    private function tokenFor(Request $request): ?YoutubeChannelToken
    {
        return YoutubeChannelToken::query()
            ->where('user_id', $request->user()->id)
            ->first();
    }

    private function configureYoutubeRedirect(): void
    {
        config(['services.google.redirect' => config('services.youtube.redirect')]);
    }

    /**
     * @return array<string, mixed>
     */
    private function validatedToolInput(Request $request): array
    {
        return $request->validate([
            'append_text' => ['nullable', 'string', 'max:5000'],
            'find_text' => ['nullable', 'string', 'max:5000'],
            'replace_text' => ['nullable', 'string', 'max:5000'],
            'append_only_if_missing' => ['nullable', 'boolean'],
            'add_separator' => ['nullable', 'boolean'],
        ]);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function optionsFromValidated(array $validated): DescriptionUpdateOptions
    {
        return new DescriptionUpdateOptions(
            appendText: (string) ($validated['append_text'] ?? ''),
            findText: filled($validated['find_text'] ?? null) ? (string) $validated['find_text'] : null,
            replaceText: filled($validated['replace_text'] ?? null) ? (string) $validated['replace_text'] : null,
            appendOnlyIfMissing: (bool) ($validated['append_only_if_missing'] ?? false),
            addSeparator: (bool) ($validated['add_separator'] ?? false),
        );
    }

    private function previewFromSession(Request $request): ?DescriptionPreview
    {
        $changes = $request->session()->get('youtube_tools.preview');

        if (! is_array($changes)) {
            return null;
        }

        return new DescriptionPreview(collect($changes)
            ->map(fn (array $change) => DescriptionChange::fromArray($change)));
    }
}
