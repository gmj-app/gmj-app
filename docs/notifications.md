# In-app notifications

Phase 1 uses Laravel database notifications and gives every `User` one inbox, regardless of whether the user acts as a Guide, Creator, or both.

## Creating a notification

Application code should create a notification class extending `BaseDatabaseNotification`, then send it through `NotificationDispatchService`. Do not insert notification rows directly or create notifications in Blade.

Use a deterministic key that is unique for the recipient and domain event, for example:

```text
request.published:request-123:user-45
```

The dispatch service checks this key before delivery. A database unique constraint is the final guard against concurrent retries.

The normalized JSON payload contains:

```text
schema_version, notification_key, category, audience, title, message,
action_url, action_label, icon, severity, actor_type, actor_id,
creator_id, request_id, metadata
```

Taxonomies and display labels live in `config/notifications.php`. Action destinations must be internal relative paths and are resolved by `NotificationUrlResolver`.

## Future domain events

Later phases should follow this pattern without changing the inbox:

```text
Domain event:       RequestPublished
Listener:           SendRequestPublishedNotifications
Notification class: RequestPublishedNotification
Delivery:           NotificationDispatchService
```

The same pattern applies to new creator requests, achievements, milestones, announcements, account updates, and billing events. Audience resolution and announcement fan-out belong in listeners/services, not controllers or notification views.

Email, preferences, archive/dismiss, push, and real-time badge refresh are intentionally not part of Phase 1. A future retention policy can keep general notifications for 12 months, retain account/billing records longer, and keep achievements indefinitely; no automatic deletion is currently scheduled.

## Queue operation

Phase 1 database notifications are synchronous so a missing worker cannot block local or initial production delivery. The base class uses Laravel's `Queueable` trait. A future concrete notification can implement `Illuminate\Contracts\Queue\ShouldQueue` when workers are deployed.

For the configured database queue, production needs the queue tables migrated and a supervised worker such as:

```shell
php artisan queue:work --queue=default --tries=3
```

Restart long-running workers after deployments with `php artisan queue:restart`.

## Deployment

```shell
php artisan migrate --force
php artisan optimize:clear
npm ci
npm run build
```

The notification migration creates the Laravel-compatible table plus unread/recent composite indexes and recipient-scoped deduplication uniqueness. It does not purge existing data.
