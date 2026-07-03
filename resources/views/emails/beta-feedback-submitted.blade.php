New Guide My Journey testing feedback

Feedback type: {{ $feedback->type }}
Name: {{ $feedback->name ?: 'Not provided' }}
Email: {{ $feedback->email ?: 'Not provided' }}
User ID: {{ $feedback->user_id ?: 'Guest' }}
Current page URL: {{ $feedback->current_url ?: 'Not provided' }}

Message:
{{ $feedback->message }}

Extra context:
{{ $feedback->extra_context ?: 'Not provided' }}

Browser/user agent:
{{ $feedback->user_agent ?: 'Not provided' }}

Platform: {{ $feedback->platform ?: 'Not provided' }}
Timezone: {{ $feedback->timezone ?: 'Not provided' }}
Viewport size: {{ $feedback->viewport_width && $feedback->viewport_height ? "{$feedback->viewport_width} x {$feedback->viewport_height}" : 'Not provided' }}
Screen size: {{ $feedback->screen_width && $feedback->screen_height ? "{$feedback->screen_width} x {$feedback->screen_height}" : 'Not provided' }}
Submitted at: {{ $feedback->created_at?->toDayDateTimeString() ?: 'Not provided' }}
