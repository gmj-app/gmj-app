<?php

namespace App\Presenters;

use App\Services\NotificationUrlResolver;
use Illuminate\Notifications\DatabaseNotification;

class NotificationPresenter
{
    public function __construct(public readonly DatabaseNotification $notification) {}

    public function id(): string
    {
        return (string) $this->notification->id;
    }

    public function title(): string
    {
        return $this->text('title', 'Notification');
    }

    public function message(): string
    {
        return $this->text('message', 'You have a new update.');
    }

    public function category(): string
    {
        $value = $this->text('category', 'system');

        return array_key_exists($value, config('notifications.categories', [])) ? $value : 'system';
    }

    public function categoryLabel(): string
    {
        return config('notifications.categories.'.$this->category(), 'System');
    }

    public function icon(): string
    {
        $value = $this->text('icon', 'bell');

        return in_array($value, config('notifications.icons', []), true) ? $value : 'bell';
    }

    public function severity(): string
    {
        $value = $this->text('severity', 'info');

        return in_array($value, config('notifications.severities', []), true) ? $value : 'info';
    }

    public function actionUrl(): string
    {
        return app(NotificationUrlResolver::class)->resolve(data_get($this->notification->data, 'action_url'));
    }

    public function actionLabel(): ?string
    {
        $value = $this->text('action_label', '');

        return $value !== '' ? $value : null;
    }

    public function isRead(): bool
    {
        return $this->notification->read_at !== null;
    }

    public function createdAt()
    {
        return $this->notification->created_at;
    }

    public function relativeTime(): string
    {
        return $this->createdAt()?->diffForHumans() ?? 'Recently';
    }

    public function dateGroup(): string
    {
        $createdAt = $this->createdAt();
        if (! $createdAt) {
            return 'Earlier';
        }
        if ($createdAt->isToday()) {
            return 'Today';
        }
        if ($createdAt->isYesterday()) {
            return 'Yesterday';
        }

        return $createdAt->format('F j, Y');
    }

    private function text(string $key, string $fallback): string
    {
        $value = data_get($this->notification->data, $key);

        return is_string($value) && trim($value) !== '' ? trim(strip_tags($value)) : $fallback;
    }
}
