@props(['title'])
<x-public-layout :title="$title.' | Creator Intelligence'">
    <div class="mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8 border-b border-slate-200 pb-6 dark:border-slate-800">
            <p class="text-sm font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">SuperAdmin</p>
            <h1 class="mt-1 text-3xl font-extrabold">Creator Intelligence</h1>
            <nav aria-label="Creator Intelligence" class="mt-5 flex flex-wrap gap-2 text-sm font-bold">
                @foreach ([
                    'overview' => ['Overview', route('superadmin.creator-intelligence.overview')],
                    'profiles' => ['Creator Profiles', route('superadmin.creator-intelligence.profiles.index')],
                    'channels' => ['Creator Channels', route('superadmin.creator-intelligence.channels.index')],
                ] as $key => [$label, $url])
                    <a href="{{ $url }}" class="rounded-xl px-4 py-2 {{ request()->routeIs('superadmin.creator-intelligence.'.$key.'*') ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-slate-900' }}">{{ $label }}</a>
                @endforeach
            </nav>
        </div>
        @if (session('success'))<div class="mb-6 rounded-xl bg-emerald-100 p-4 font-semibold text-emerald-800">{{ session('success') }}</div>@endif
        {{ $slot }}
    </div>
</x-public-layout>
