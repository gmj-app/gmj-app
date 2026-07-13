@props(['creator', 'header'])

@if ($header['context']['show_owner_toolbar'])
    <section aria-labelledby="creator-tools-title" class="rounded-2xl border border-slate-200 bg-white px-4 py-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:px-5">
        @if ($header['context']['is_super_admin_assisting'])
            <div class="mb-3 rounded-xl border border-amber-300 bg-amber-50 px-3 py-2 text-sm text-amber-950 dark:border-amber-800 dark:bg-amber-950/40 dark:text-amber-100"><strong>Super Admin Assistance Mode</strong> — You are viewing tools for {{ $creator->display_name }}.</div>
        @endif
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <h2 id="creator-tools-title" class="font-extrabold text-slate-950 dark:text-white">Creator tools</h2>
            <div class="flex flex-wrap gap-2 text-sm font-bold">
                @if ($header['context']['is_super_admin_assisting'])
                    <a href="{{ route('super-admin.creators.requests.index', $creator) }}" class="rounded-xl border border-slate-300 px-3 py-2 hover:border-indigo-400 dark:border-slate-700">Manage requests</a>
                    <a href="{{ route('super-admin.creators.assist', $creator) }}" class="rounded-xl border border-slate-300 px-3 py-2 hover:border-indigo-400 dark:border-slate-700">Edit creator page</a>
                @else
                    <a href="{{ route('creators.recommendations.index', $creator) }}" class="rounded-xl border border-slate-300 px-3 py-2 hover:border-indigo-400 dark:border-slate-700">Manage requests</a>
                    <a href="{{ route('creators.settings.edit', $creator) }}" class="rounded-xl border border-slate-300 px-3 py-2 hover:border-indigo-400 dark:border-slate-700">Edit creator page</a>
                    <a href="{{ route('creators.dashboard', $creator) }}" aria-label="Open settings for {{ $creator->display_name }}" class="rounded-xl border border-slate-300 px-3 py-2 hover:border-indigo-400 dark:border-slate-700">Settings</a>
                @endif
            </div>
        </div>
    </section>
@endif
