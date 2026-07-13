<?php

namespace App\Http\Controllers;

use App\Presenters\NotificationPresenter;
use App\Services\NotificationUrlResolver;
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

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications()->update(['read_at' => now()]);

        return back()->with('success', 'All notifications marked read.');
    }

    private function owned(Request $request, string $id): DatabaseNotification
    {
        return $request->user()->notifications()->findOrFail($id);
    }
}
