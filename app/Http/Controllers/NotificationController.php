<?php

namespace App\Http\Controllers;

use App\Presenters\NotificationPresenter;
use App\Services\NotificationReadService;
use App\Services\NotificationUrlResolver;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request): View
    {
        $filter = (string) $request->query('filter', 'all');
        $category = (string) $request->query('category', '');
        abort_unless(in_array($filter, ['all', 'unread'], true), 404);
        abort_unless($category === '' || array_key_exists($category, config('notifications.categories', [])), 404);

        $notifications = $request->user()->notifications()
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($category !== '', fn ($query) => $query->where('data->category', $category))
            ->latest()->paginate(25)->withQueryString();
        $notifications->setCollection($notifications->getCollection()->map(fn (DatabaseNotification $item) => new NotificationPresenter($item)));

        return view('notifications.index', compact('notifications', 'filter', 'category'));
    }

    public function open(Request $request, string $notification, NotificationUrlResolver $urls): RedirectResponse
    {
        $item = $this->owned($request, $notification);
        $item->markAsRead();

        return redirect()->to($urls->resolve(data_get($item->data, 'action_url')));
    }

    public function markRead(Request $request, string $notification): RedirectResponse
    {
        $this->owned($request, $notification)->markAsRead();

        return back()->with('success', 'Notification marked read.');
    }

    public function markUnread(Request $request, string $notification): RedirectResponse
    {
        $this->owned($request, $notification)->markAsUnread();

        return back()->with('success', 'Notification marked unread.');
    }

    public function dropdown(Request $request): JsonResponse
    {
        return $this->dropdownState($request);
    }

    public function acknowledgeOpen(Request $request, NotificationReadService $reads): JsonResponse
    {
        // The first inbox query defines this open event's server-side boundary.
        $snapshot = $reads->unreadSnapshot($request->user());
        $reads->acknowledge($request->user(), $snapshot);

        return $this->dropdownState($request, $snapshot->all());
    }

    private function dropdownState(Request $request, array $openedIds = []): JsonResponse
    {
        $user = $request->user();
        $notificationItems = $user->notifications()->latest()->limit(10)->get()
            ->map(fn ($item) => new NotificationPresenter($item));

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'unread_ids' => $notificationItems->reject->isRead()->map->id()->values(),
            'opened_unread_ids' => $notificationItems->map->id()->intersect($openedIds)->values(),
            'html' => view('components.notifications.dropdown-items', compact('notificationItems'))->render(),
        ])->header('Cache-Control', 'private, no-store');
    }

    public function markAllRead(Request $request, NotificationReadService $reads): RedirectResponse
    {
        $reads->acknowledge($request->user(), $reads->unreadSnapshot($request->user()));

        return back()->with('success', 'All notifications marked read.');
    }

    private function owned(Request $request, string $id): DatabaseNotification
    {
        return $request->user()->notifications()->findOrFail($id);
    }
}
