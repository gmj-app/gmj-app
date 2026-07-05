# Internal YouTube Video Tools

The internal creator tools area is available at `/tools/admin`.

## Access

Access is controlled by `users.can_access_video_tools`. Normal users receive `403 Forbidden`.

Grant access with Tinker:

```bash
php artisan tinker
```

```php
App\Models\User::where('email', 'jfragment@gmail.com')->update(['can_access_video_tools' => true]);
```

## Environment

Set these values in `.env`:

```dotenv
GOOGLE_CLIENT_ID=
GOOGLE_CLIENT_SECRET=
GOOGLE_REDIRECT_URI="${APP_URL}/auth/google/callback"
YOUTUBE_REDIRECT_URI="${APP_URL}/tools/admin/youtube/callback"
YOUTUBE_API_ENABLED=true
```

In Google Cloud, enable the YouTube Data API v3 and add the YouTube redirect URI to the OAuth client's authorized redirect URIs.

## OAuth Scope

The tool requests `https://www.googleapis.com/auth/youtube.force-ssl`, which allows updating YouTube video metadata through the YouTube Data API v3.

Guide My Journey login and YouTube channel authorization are separate flows. A user signs in normally first, then connects a YouTube channel from `/tools/admin/youtube`.

## Safety Workflow

1. Connect the YouTube channel.
2. Enter append text or exact find/replace text.
3. Generate a preview.
4. Review changed and skipped videos.
5. Confirm the warning and apply updates.

The apply endpoint is rate limited. If more than 25 videos would change, the updates are queued, so run a queue worker:

```bash
php artisan queue:work
```

Before each update, the app stores the full original description in `youtube_description_backups`. Every preview, skip, update, and failure is recorded in `video_tool_audit_logs`.

Rollback UI is not implemented yet, but the backup table includes the original and new description plus `operation_batch_id` so a rollback tool can be added without another schema change.
