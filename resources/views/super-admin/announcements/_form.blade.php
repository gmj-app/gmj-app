@php
    $timing = old('publish_timing', $announcement->status === \App\Models\Announcement::STATUS_SCHEDULED ? 'schedule' : 'draft');
    $audience = old('audience', $announcement->audience ?: \App\Models\Announcement::AUDIENCE_ALL);
@endphp
<div x-data="{ audience: @js($audience), timing: @js($timing), estimates: @js($estimates) }" class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
    <div class="space-y-5 rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">
        <div><label for="internal_name" class="text-sm font-bold">Internal name</label><input id="internal_name" name="internal_name" value="{{ old('internal_name', $announcement->internal_name) }}" maxlength="255" required class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950">@error('internal_name')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div><label for="title" class="text-sm font-bold">Title</label><input id="title" name="title" value="{{ old('title', $announcement->title) }}" maxlength="150" required class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950">@error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div><label for="message" class="text-sm font-bold">Message</label><textarea id="message" name="message" rows="5" maxlength="1000" required class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950">{{ old('message', $announcement->message) }}</textarea><p class="mt-1 text-xs text-slate-500">Plain text only.</p>@error('message')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label for="audience" class="text-sm font-bold">Audience</label><select id="audience" name="audience" x-model="audience" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"><option value="all">All users</option><option value="creators">Creators only</option></select></div>
            <div><label for="publish_timing" class="text-sm font-bold">Publish timing</label><select id="publish_timing" name="publish_timing" x-model="timing" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"><option value="draft">Save draft</option><option value="now">Publish now</option><option value="schedule">Schedule</option></select></div>
        </div>
        <div x-show="timing === 'schedule'" class="grid gap-4 sm:grid-cols-2">
            <div><label for="starts_at" class="text-sm font-bold">Starts at</label><input id="starts_at" name="starts_at" type="datetime-local" x-bind:disabled="timing !== 'schedule'" value="{{ old('starts_at', $announcement->starts_at?->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950">@error('starts_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="expires_at" class="text-sm font-bold">Expires at <span class="font-normal text-slate-500">(optional)</span></label><input id="expires_at" name="expires_at" type="datetime-local" x-bind:disabled="timing !== 'schedule'" value="{{ old('expires_at', $announcement->expires_at?->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950">@error('expires_at')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
        </div>
        <div x-show="timing !== 'schedule'"><label for="expires_at_now" class="text-sm font-bold">Expires at <span class="font-normal text-slate-500">(optional)</span></label><input id="expires_at_now" name="expires_at" type="datetime-local" x-bind:disabled="timing === 'schedule'" value="{{ old('expires_at', $announcement->expires_at?->format('Y-m-d\TH:i')) }}" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"></div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label for="action_url" class="text-sm font-bold">Internal action path <span class="font-normal text-slate-500">(optional)</span></label><input id="action_url" name="action_url" value="{{ old('action_url', $announcement->action_url) }}" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"><p class="mt-1 text-xs text-slate-500">For example: /dashboard</p>@error('action_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
            <div><label for="action_label" class="text-sm font-bold">Action label <span class="font-normal text-slate-500">(optional)</span></label><input id="action_label" name="action_label" value="{{ old('action_label', $announcement->action_label) }}" maxlength="80" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"></div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div><label for="icon" class="text-sm font-bold">Icon</label><select id="icon" name="icon" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950">@foreach(config('notifications.icons') as $icon)<option value="{{ $icon }}" @selected(old('icon', $announcement->icon) === $icon)>{{ str($icon)->replace('-', ' ')->title() }}</option>@endforeach</select></div>
            <div><label for="severity" class="text-sm font-bold">Severity</label><select id="severity" name="severity" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950">@foreach(config('notifications.severities') as $severity)<option value="{{ $severity }}" @selected(old('severity', $announcement->severity) === $severity)>{{ str($severity)->title() }}</option>@endforeach</select></div>
        </div>
        <div class="flex flex-wrap gap-3"><button class="rounded-xl bg-indigo-600 px-5 py-3 font-extrabold text-white">{{ $submitLabel }}</button><a href="{{ route('super-admin.announcements.index') }}" class="rounded-xl bg-slate-100 px-5 py-3 font-bold dark:bg-slate-800">Cancel</a></div>
    </div>

    <aside class="space-y-4">
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <p class="text-xs font-extrabold uppercase tracking-wide text-slate-500">Estimated audience</p>
            <p class="mt-2 text-2xl font-extrabold" x-text="estimates[audience].toLocaleString()"></p>
            <p class="text-sm text-slate-500" x-text="audience === 'creators' ? 'distinct active creator owners' : 'active user accounts'"></p>
            <p class="mt-3 text-xs text-slate-500">Recipient eligibility is recalculated when delivery begins.</p>
        </div>
        <div class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <p class="mb-4 text-xs font-extrabold uppercase tracking-wide text-slate-500">Notification preview</p>
            <article class="flex gap-3"><x-notifications.icon :name="old('icon', $announcement->icon)" :severity="old('severity', $announcement->severity)" /><div class="min-w-0"><p class="font-extrabold">{{ old('title', $announcement->title) ?: 'Announcement title' }}</p><p class="mt-1 whitespace-pre-line text-sm text-slate-600 dark:text-slate-300">{{ old('message', $announcement->message) ?: 'Your announcement message will appear here.' }}</p><p class="mt-2 text-xs font-bold text-indigo-600">{{ old('action_label', $announcement->action_label) }}</p></div></article>
        </div>
    </aside>
</div>
