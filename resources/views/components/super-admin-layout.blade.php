@props(['title'])
<x-public-layout :title="$title.' | Super Admin'">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8 flex flex-col gap-5 border-b border-slate-200 pb-6 dark:border-slate-800 sm:flex-row sm:items-end sm:justify-between">
            <div><p class="text-sm font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">Internal tools</p><h1 class="mt-1 text-3xl font-extrabold">Super Admin</h1></div>
            <nav aria-label="Super Admin" class="flex flex-wrap gap-2 text-sm font-bold">
                <a href="{{ route('super-admin.dashboard') }}" class="rounded-xl px-4 py-2 {{ request()->routeIs('super-admin.dashboard') ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-slate-900' }}">Overview</a>
                <a href="{{ route('super-admin.creators.index') }}" class="rounded-xl px-4 py-2 {{ request()->routeIs('super-admin.creators.*') ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-slate-900' }}">Creators</a>
                <a href="{{ route('super-admin.ads.index') }}" class="rounded-xl px-4 py-2 {{ request()->routeIs('super-admin.ads.*') ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-slate-900' }}">Advertisements</a>
                <a href="{{ route('super-admin.notifications.test') }}" class="rounded-xl px-4 py-2 {{ request()->routeIs('super-admin.notifications.*') ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-slate-900' }}">Notifications</a>
            </nav>
        </div>
        @if (session('success'))<div class="mb-6 rounded-xl bg-emerald-100 p-4 font-semibold text-emerald-800">{{ session('success') }}</div>@endif
        {{ $slot }}
    </div>
</x-public-layout>
