<?php

namespace App\Http\Controllers;

use App\Mail\BetaFeedbackSubmitted;
use App\Models\BetaFeedback;
use App\Models\User;
use App\Services\SuperAdminAuditService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;
use Throwable;

class BetaFeedbackController extends Controller
{
    public function __construct(private readonly SuperAdminAuditService $audit) {}

    public function index(Request $request): JsonResponse
    {
        $this->authorizeInbox($request);

        $feedback = BetaFeedback::query()
            ->notSpam()
            ->with(['user', 'readBy'])
            ->latest('created_at')
            ->latest('id')
            ->limit(25)
            ->get();

        return response()->json([
            'unread_count' => BetaFeedback::query()->unread()->count(),
            'feedback' => $feedback->map(fn (BetaFeedback $item): array => [
                'id' => $item->id,
                'name' => $item->name,
                'email' => $item->email,
                'type' => $item->type,
                'message' => $item->message,
                'extra_context' => $item->extra_context,
                'current_url' => $item->current_url,
                'created_at' => $item->created_at?->toIso8601String(),
                'read_at' => $item->read_at?->toIso8601String(),
                'read_by' => $item->readBy?->name,
            ]),
        ]);
    }

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
            : $user?->publicName();
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

    public function markRead(Request $request, BetaFeedback $feedback): JsonResponse
    {
        $user = $this->authorizeInbox($request);

        $feedback->forceFill([
            'read_at' => now(),
            'read_by_user_id' => $user->id,
        ])->save();

        return response()->json([
            'success' => true,
            'read_at' => $feedback->read_at?->toIso8601String(),
            'read_by' => $user->name,
            'unread_count' => BetaFeedback::query()->unread()->count(),
        ]);
    }

    public function markUnread(Request $request, BetaFeedback $feedback): JsonResponse
    {
        $this->authorizeInbox($request);

        $feedback->forceFill([
            'read_at' => null,
            'read_by_user_id' => null,
        ])->save();

        return response()->json([
            'success' => true,
            'unread_count' => BetaFeedback::query()->unread()->count(),
        ]);
    }

    public function spam(Request $request, BetaFeedback $feedback): JsonResponse
    {
        $user = $this->authorizeInbox($request);
        $validated = $request->validate([
            'spam_reason' => ['nullable', Rule::in(['unsolicited_sales', 'automated_spam', 'abusive', 'irrelevant', 'other'])],
        ]);
        $before = ['spam_at' => $feedback->spam_at?->toIso8601String(), 'spam_by_user_id' => $feedback->spam_by_user_id, 'spam_reason' => $feedback->spam_reason];

        if (! $feedback->isSpam()) {
            $feedback->forceFill(['spam_at' => now(), 'spam_by_user_id' => $user->id, 'spam_reason' => $validated['spam_reason'] ?? null])->save();
            $this->audit->record($user, $feedback, 'beta_feedback.marked_spam', 'Testing feedback marked as spam.', $before, ['spam_at' => $feedback->spam_at?->toIso8601String(), 'spam_by_user_id' => $user->id, 'spam_reason' => $feedback->spam_reason], ['sender_email' => $feedback->email], $request);
        }

        return response()->json(['success' => true, 'message' => 'Feedback marked as spam.', 'unread_count' => BetaFeedback::query()->unread()->count()]);
    }

    public function restore(Request $request, BetaFeedback $feedback): JsonResponse
    {
        $user = $this->authorizeInbox($request);
        $before = ['spam_at' => $feedback->spam_at?->toIso8601String(), 'spam_by_user_id' => $feedback->spam_by_user_id, 'spam_reason' => $feedback->spam_reason];

        if ($feedback->isSpam()) {
            $feedback->forceFill(['spam_at' => null, 'spam_by_user_id' => null, 'spam_reason' => null])->save();
            $this->audit->record($user, $feedback, 'beta_feedback.restored_from_spam', 'Testing feedback restored from spam.', $before, ['spam_at' => null, 'spam_by_user_id' => null, 'spam_reason' => null], ['sender_email' => $feedback->email], $request);
        }

        return response()->json(['success' => true, 'message' => 'Feedback restored to inbox.', 'unread_count' => BetaFeedback::query()->unread()->count()]);
    }

    private function authorizeInbox(Request $request): User
    {
        $user = $request->user();

        abort_unless($user?->canViewBetaFeedbackInbox(), 404);

        return $user;
    }
}
