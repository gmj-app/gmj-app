<?php

namespace App\Http\Controllers;

use App\Presenters\NotificationPresenter;
use App\Services\DailyJourney\AccessService;
use App\Services\NotificationUrlResolver;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

class NotificationController extends Controller
{
    public function index(Request $request, AccessService $gameAccess): View
    {
        $filter = (string) $request->query('filter', 'all');
        $category = (string) $request->query('category', '');
        abort_unless(in_array($filter, ['all', 'unread'], true), 404);
        abort_unless($category === '' || array_key_exists($category, config('notifications.categories', [])), 404);

        $notifications = $request->user()->notifications()
            ->when(! $gameAccess->allows($request->user()), fn ($query) => $query->where(function ($query): void {
                $query->whereNull('data->notification_key')->orWhere('data->notification_key', 'not like', 'game.%');
            }))
            ->when($filter === 'unread', fn ($query) => $query->whereNull('read_at'))
            ->when($category !== '', fn ($query) => $query->where('data->category', $category))
            ->latest()->paginate(25)->withQueryString();
        $notifications->setCollection($notifications->getCollection()->map(fn (DatabaseNotification $item) => new NotificationPresenter($item)));

        return view('notifications.index', compact('notifications', 'filter', 'category'));
    }

    public function open(Request $request, string $notification, NotificationUrlResolver $urls, AccessService $gameAccess): RedirectResponse
    {
        $item = $this->owned($request, $notification);
        $this->ensureVisible($request, $item, $gameAccess);
        $item->markAsRead();

        return redirect()->to($urls->resolve(data_get($item->data, 'action_url')));
    }

    public function markRead(Request $request, string $notification, AccessService $gameAccess): RedirectResponse
    {
        $item = $this->owned($request, $notification);
        $this->ensureVisible($request, $item, $gameAccess);
        $item->markAsRead();

        return back()->with('success', 'Notification marked read.');
    }

    public function markUnread(Request $request, string $notification, AccessService $gameAccess): RedirectResponse
    {
        $item = $this->owned($request, $notification);
        $this->ensureVisible($request, $item, $gameAccess);
        $item->markAsUnread();

        return back()->with('success', 'Notification marked unread.');
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked read.');
    }

    private function owned(Request $request, string $id): DatabaseNotification
    {
        return $request->user()->notifications()->findOrFail($id);
    }

    private function ensureVisible(Request $request, DatabaseNotification $notification, AccessService $gameAccess): void
    {
        $key = data_get($notification->data, 'notification_key');
        abort_if(is_string($key) && str_starts_with($key, 'game.') && ! $gameAccess->allows($request->user()), 404);
    }
}
