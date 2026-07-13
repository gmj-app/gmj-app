<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Notifications\BaseDatabaseNotification;
use App\Services\NotificationDispatchService;
use App\Services\NotificationUrlResolver;
use App\Services\SuperAdminAuditService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TestNotificationController extends Controller
{
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q'));
        $users = User::query()->select(['id', 'name', 'public_display_name', 'public_handle', 'email'])
            ->when($search !== '', fn ($query) => $query->where(fn ($query) => $query->where('name', 'like', "%{$search}%")->orWhere('public_display_name', 'like', "%{$search}%")->orWhere('public_handle', 'like', "%{$search}%")->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('super-admin.notifications.test', compact('users', 'search'));
    }

    public function store(Request $request, NotificationDispatchService $dispatch, NotificationUrlResolver $urls, SuperAdminAuditService $audit): RedirectResponse
    {
        $validated = $request->validate([
            'recipient_id' => ['required', Rule::exists('users', 'id')->whereNull('deleted_at')],
            'category' => ['required', Rule::in(array_keys(config('notifications.categories', [])))],
            'audience' => ['required', Rule::in(config('notifications.audiences', []))],
            'title' => ['required', 'string', 'max:150'], 'message' => ['required', 'string', 'max:500'],
            'action_url' => ['required', 'string', 'max:2048'], 'action_label' => ['nullable', 'string', 'max:80'],
            'icon' => ['required', Rule::in(config('notifications.icons', []))], 'severity' => ['required', Rule::in(config('notifications.severities', []))],
            'deduplication_key' => ['nullable', 'string', 'max:191'],
        ]);
        if (! $urls->isSafe($validated['action_url'])) {
            throw ValidationException::withMessages(['action_url' => 'Use a safe internal path beginning with /.']);
        }
        $recipient = User::query()->findOrFail($validated['recipient_id']);
        $key = $validated['deduplication_key'] ?: 'system.test:'.str()->uuid().':user-'.$recipient->id;
        $sent = $dispatch->send($recipient, new BaseDatabaseNotification(key: $key, title: $validated['title'], message: $validated['message'], category: $validated['category'], audience: $validated['audience'], actionUrl: $validated['action_url'], actionLabel: $validated['action_label'] ?? null, icon: $validated['icon'], severity: $validated['severity']));
        if ($sent) {
            $audit->record($request->user(), $recipient, 'notification.test_sent', 'Test notification sent.', [], [], ['recipient_user_id' => $recipient->id, 'category' => $validated['category'], 'notification_key' => $key], $request);
        }

        return back()->with('success', $sent ? 'Test notification sent.' : 'Duplicate notification was not sent.');
    }
}
