# In-app notifications

Laravel database notifications give every `User` one inbox, regardless of whether the user acts as a Guide, Creator, or both.

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

## Product event integrations

Phase 2 uses this pattern without changing the inbox:

```text
Domain event:       RequestPublished
Listeners:          NotifyRequestSubmitterOfPublication
                    NotifyRequestSupportersOfPublication
Notifications:      GuideRequestPublishedNotification
                    SupportedRequestPublishedNotification
Delivery:           NotificationDispatchService
```

`RequestCreated` independently queues the creator-owner notification. Both request events are dispatched only after their database mutation commits. Queued listeners reload models by stable ID, and notification failures are logged without rolling back request creation or publication.

Historical supporters are resolved from `user_picks`, including rows whose capacity was released for normal lifecycle reasons. Support removed with `request_removed`, soft-deleted users, and the original submitter are excluded.

## Announcements

Super Admin announcements support `all` and `creators` audiences. `AnnouncementAudienceResolver` uses one indexed query; active creator ownership is resolved through `creator_owners` and active creators without per-user queries.

Publishing follows this pipeline:

```text
AnnouncementPublished event
  -> DistributeAnnouncementNotifications listener
  -> announcement_deliveries rows in chunks of 250
  -> DispatchAnnouncementChunk jobs
  -> SiteAnnouncementNotification or CreatorAnnouncementNotification
```

Each delivery row and notification has recipient-level uniqueness. A retry that finds an existing notification is recorded as delivered rather than duplicated. Recipient, delivered, and failed counts are stored on the announcement. Recipient-facing fields lock after publication is queued.

Scheduled announcements are claimed idempotently by:

```shell
php artisan announcements:publish-due
```

Laravel's scheduler invokes this command every minute. Cancelled or expired announcements are not delivered.

## Future phases

The same domain-event/listener pattern can add achievements, milestones, account updates, billing events, and other audiences. Audience resolution belongs in resolvers and fan-out services, not controllers or notification views.

Email, preferences, archive/dismiss, push, failed-delivery retry controls, and real-time badge refresh are intentionally not included. A future retention policy can keep general notifications for 12 months, retain account/billing records longer, and keep achievements indefinitely; no automatic deletion is currently scheduled.

## Queue operation

Local tests use the `sync` queue. Phase 2 request listeners and announcement fan-out are queued in production, while the normalized notification classes continue to use only Laravel's database channel.

For the configured database queue, production needs the queue tables migrated and a supervised worker such as:

```shell
php artisan queue:work --queue=default --sleep=3 --tries=3 --timeout=90
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

The Phase 2 migration adds `announcements` and `announcement_deliveries`. Laravel Cloud must run one queue worker and invoke `php artisan schedule:run` every minute (or use its managed scheduler) so due announcements are claimed.
