<?php

namespace App\Http\Controllers;

use App\Mail\BetaFeedbackSubmitted;
use App\Models\BetaFeedback;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class BetaFeedbackController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        abort_unless(config('gmj.beta_feedback_enabled'), 404);

        $user = $request->user();

        $validated = $request->validate([
            'name' => [$user ? 'nullable' : 'required', 'string', 'max:255'],
            'email' => [$user ? 'nullable' : 'required', 'email', 'max:255'],
            'type' => ['required', 'string', Rule::in([
                'Bug',
                'Confusing UX',
                'Missing feature',
                'Content/data issue',
                'Other',
            ])],
            'message' => ['required', 'string', 'max:5000'],
            'extra_context' => ['nullable', 'string', 'max:5000'],
            'current_url' => ['nullable', 'string', 'max:2000'],
            'user_agent' => ['nullable', 'string', 'max:2000'],
            'platform' => ['nullable', 'string', 'max:255'],
            'timezone' => ['nullable', 'string', 'max:255'],
            'app_environment' => ['nullable', 'string', 'max:255'],
            'viewport_width' => ['nullable', 'integer', 'min:0'],
            'viewport_height' => ['nullable', 'integer', 'min:0'],
            'screen_width' => ['nullable', 'integer', 'min:0'],
            'screen_height' => ['nullable', 'integer', 'min:0'],
            'meta' => ['nullable', 'json', 'max:5000'],
        ]);

        $validated['user_id'] = $user?->id;
        $validated['name'] = filled($validated['name'] ?? null)
            ? $validated['name']
            : $user?->name;
        $validated['email'] = filled($validated['email'] ?? null)
            ? $validated['email']
            : $user?->email;
        $validated['meta'] = isset($validated['meta'])
            ? json_decode($validated['meta'], true)
            : null;

        $feedback = BetaFeedback::query()->create($validated);

        Log::info('Beta feedback submitted', [
            'feedback_id' => $feedback->id,
            'user_id' => $feedback->user_id,
            'type' => $feedback->type,
            'current_url' => $feedback->current_url,
            'platform' => $feedback->platform,
            'timezone' => $feedback->timezone,
        ]);

        try {
            Mail::to(config('gmj.beta_feedback_email'))
                ->send(new BetaFeedbackSubmitted($feedback));
        } catch (Throwable $exception) {
            Log::error('Failed to send beta feedback email', [
                'feedback_id' => $feedback->id,
                'error' => $exception->getMessage(),
            ]);
        }

        return response()->json([
            'message' => 'Thanks, your feedback was sent.',
        ]);
    }
}
