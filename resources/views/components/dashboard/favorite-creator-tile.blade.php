@props(['creator'])

<a
    href="{{ route('creator.queue', $creator) }}"
    aria-label="Open {{ $creator->display_name }} creator page"
    {{ $attributes->class('group flex min-h-20 min-w-0 items-center gap-3 rounded-xl border border-slate-700 bg-slate-900 p-4 text-white transition hover:border-emerald-400 hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950') }}
>
    @if ($creator->avatar_url)
        <img src="{{ $creator->avatar_url }}" alt="" class="size-12 shrink-0 rounded-full object-cover ring-1 ring-white/10">
    @else
        <span class="flex size-12 shrink-0 items-center justify-center rounded-full bg-indigo-500/20 text-sm font-black text-indigo-200 ring-1 ring-indigo-400/30" aria-hidden="true">{{ $creator->initials }}</span>
    @endif
    <span class="min-w-0 flex-1">
        <span class="block truncate font-bold">{{ $creator->display_name }}</span>
        <span class="mt-0.5 block truncate text-sm text-slate-400">{{ '@'.$creator->slug }}</span>
    </span>
    <svg class="size-5 shrink-0 text-slate-500 transition group-hover:text-emerald-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
</a>
