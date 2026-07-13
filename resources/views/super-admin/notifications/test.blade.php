<x-super-admin-layout title="Test notifications">
    <div class="grid gap-8 lg:grid-cols-[minmax(0,1fr)_22rem]">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900 sm:p-6">
            <h2 class="text-xl font-extrabold">Send a test notification</h2>
            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">This uses the application database delivery path and does not send email.</p>

            <form method="POST" action="{{ route('super-admin.notifications.test.store') }}" class="mt-6 space-y-5">
                @csrf
                <div>
                    <label for="recipient_id" class="text-sm font-bold">Recipient</label>
                    <select id="recipient_id" name="recipient_id" required class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950">
                        <option value="">Choose a user from this page</option>
                        @foreach ($users as $user)
                            <option value="{{ $user->id }}" @selected((string) old('recipient_id') === (string) $user->id)>{{ $user->publicName() }} — {{ $user->email }}</option>
                        @endforeach
                    </select>
                    @error('recipient_id')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror
                </div>

                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="category" class="text-sm font-bold">Category</label><select id="category" name="category" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950">@foreach(config('notifications.categories') as $value => $label)<option value="{{ $value }}" @selected(old('category', 'system') === $value)>{{ $label }}</option>@endforeach</select></div>
                    <div><label for="audience" class="text-sm font-bold">Audience</label><select id="audience" name="audience" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950">@foreach(config('notifications.audiences') as $value)<option value="{{ $value }}" @selected(old('audience', 'all') === $value)>{{ str($value)->replace('_', ' ')->title() }}</option>@endforeach</select></div>
                    <div><label for="icon" class="text-sm font-bold">Icon</label><select id="icon" name="icon" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950">@foreach(config('notifications.icons') as $value)<option value="{{ $value }}" @selected(old('icon', 'bell') === $value)>{{ str($value)->replace('-', ' ')->title() }}</option>@endforeach</select></div>
                    <div><label for="severity" class="text-sm font-bold">Severity</label><select id="severity" name="severity" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950">@foreach(config('notifications.severities') as $value)<option value="{{ $value }}" @selected(old('severity', 'info') === $value)>{{ str($value)->title() }}</option>@endforeach</select></div>
                </div>

                <div><label for="title" class="text-sm font-bold">Title</label><input id="title" name="title" value="{{ old('title', 'Test notification') }}" maxlength="150" required class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950">@error('title')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div><label for="message" class="text-sm font-bold">Message</label><textarea id="message" name="message" rows="4" maxlength="500" required class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950">{{ old('message', 'This is a test of the application notification system.') }}</textarea>@error('message')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                <div class="grid gap-4 sm:grid-cols-2">
                    <div><label for="action_url" class="text-sm font-bold">Internal action path</label><input id="action_url" name="action_url" value="{{ old('action_url', '/notifications') }}" required class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"><p class="mt-1 text-xs text-slate-500">For example: /notifications</p>@error('action_url')<p class="mt-1 text-sm text-red-600">{{ $message }}</p>@enderror</div>
                    <div><label for="action_label" class="text-sm font-bold">Action label <span class="font-normal text-slate-500">(optional)</span></label><input id="action_label" name="action_label" value="{{ old('action_label', 'View notification') }}" maxlength="80" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"></div>
                </div>
                <div><label for="deduplication_key" class="text-sm font-bold">Deduplication key <span class="font-normal text-slate-500">(optional)</span></label><input id="deduplication_key" name="deduplication_key" value="{{ old('deduplication_key') }}" maxlength="191" class="mt-1 block w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"><p class="mt-1 text-xs text-slate-500">Leave blank for a unique test; reuse a key to verify duplicate suppression.</p></div>

                <button class="rounded-xl bg-indigo-600 px-5 py-3 font-extrabold text-white hover:bg-indigo-500">Send test notification</button>
            </form>
        </section>

        <aside>
            <form method="GET" action="{{ route('super-admin.notifications.test') }}" class="flex gap-2">
                <label for="q" class="sr-only">Search users</label>
                <input id="q" name="q" value="{{ $search }}" placeholder="Name, handle, or email" class="min-w-0 flex-1 rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-900">
                <button class="rounded-xl bg-slate-900 px-4 font-bold text-white dark:bg-slate-700">Search</button>
            </form>
            <div class="mt-4 overflow-hidden rounded-2xl border border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                @forelse ($users as $user)
                    <div class="border-b border-slate-100 p-4 last:border-0 dark:border-slate-800">
                        <p class="truncate font-extrabold">{{ $user->publicName() }}</p>
                        <p class="truncate text-sm text-slate-500">{{ $user->email }}</p>
                        @if($user->public_handle)<p class="truncate text-xs text-slate-500">@{{ $user->public_handle }}</p>@endif
                    </div>
                @empty
                    <p class="p-6 text-center text-sm text-slate-500">No users match that search.</p>
                @endforelse
            </div>
            <div class="mt-4">{{ $users->links() }}</div>
        </aside>
    </div>
</x-super-admin-layout>
