<?php

namespace App\Notifications;

use App\Notifications\Channels\DeduplicatedDatabaseChannel;
use App\Services\NotificationUrlResolver;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Str;

class BaseDatabaseNotification extends Notification
{
    use Queueable;

    private readonly string $normalizedKey;

    public function __construct(
        public readonly string $key,
        public readonly string $title,
        public readonly string $message,
        public readonly string $category = 'system',
        public readonly string $audience = 'all',
        public readonly ?string $actionUrl = null,
        public readonly ?string $actionLabel = null,
        public readonly string $icon = 'bell',
        public readonly string $severity = 'info',
        public readonly array $context = [],
    ) {
        $this->normalizedKey = substr(trim($key) !== '' ? trim($key) : 'system.notification:'.Str::uuid(), 0, 191);
    }

    public function via(object $notifiable): array
    {
        return [DeduplicatedDatabaseChannel::class];
    }

    public function deduplicationKey(): string
    {
        return $this->normalizedKey;
    }

    public function toArray(object $notifiable): array
    {
        $categories = array_keys(config('notifications.categories', []));
        $audiences = config('notifications.audiences', []);
        $severities = config('notifications.severities', []);
        $icons = config('notifications.icons', []);

        return [
            'schema_version' => (int) config('notifications.schema_version', 1),
            'notification_key' => $this->normalizedKey,
            'category' => in_array($this->category, $categories, true) ? $this->category : 'system',
            'audience' => in_array($this->audience, $audiences, true) ? $this->audience : 'all',
            'title' => trim(strip_tags($this->title)) ?: 'Notification',
            'message' => trim(strip_tags($this->message)) ?: 'You have a new update.',
            'action_url' => app(NotificationUrlResolver::class)->resolve($this->actionUrl),
            'action_label' => filled($this->actionLabel) ? trim(strip_tags($this->actionLabel)) : null,
            'icon' => in_array($this->icon, $icons, true) ? $this->icon : 'bell',
            'severity' => in_array($this->severity, $severities, true) ? $this->severity : 'info',
            'actor_type' => $this->context['actor_type'] ?? null,
            'actor_id' => $this->context['actor_id'] ?? null,
            'creator_id' => $this->context['creator_id'] ?? null,
            'request_id' => $this->context['request_id'] ?? null,
            'metadata' => is_array($this->context['metadata'] ?? null) ? $this->context['metadata'] : [],
        ];
    }
}
