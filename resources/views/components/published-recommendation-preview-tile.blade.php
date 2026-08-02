@props([
    'recommendation',
    'creator',
])

@php
    $display = $recommendation->publishedDisplayData();
    $publishedDate = $display['date'];
    $votes = $recommendation->totalVotes();
@endphp

<a
    href="{{ route('creators.published', $creator) }}#recommendation-{{ $recommendation->id }}"
    aria-label="View published request: {{ $display['title'] }}"
    class="group flex h-full min-w-0 flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-emerald-700 dark:focus-visible:ring-offset-slate-950"
>
    <span class="relative block aspect-video overflow-hidden bg-gradient-to-br from-slate-800 to-slate-950">
        <span class="absolute inset-0 flex items-center justify-center text-slate-400" aria-hidden="true">
            <svg class="size-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5V6.75A2.75 2.75 0 0 1 6.75 4h10.5A2.75 2.75 0 0 1 20 6.75V19.5l-4.5-2.25L12 19.5l-3.5-2.25L4 19.5Z" />
            </svg>
        </span>
        @if ($display['thumbnail_url'])
            <img
                src="{{ $display['thumbnail_url'] }}"
                alt=""
                width="640"
                height="360"
                loading="lazy"
                onerror="this.hidden = true"
                class="relative h-full w-full object-cover transition duration-300 group-hover:scale-105 group-hover:opacity-90"
            >
        @endif
    </span>

    <span class="flex min-w-0 flex-1 flex-col p-4">
        <span title="{{ $display['title'] }}" class="line-clamp-2 min-h-10 break-words text-sm font-extrabold leading-5 text-slate-950 transition group-hover:text-emerald-700 dark:text-white dark:group-hover:text-emerald-300">
            {{ $display['title'] }}
        </span>

        <span class="mt-3 text-xs font-semibold text-slate-500 dark:text-slate-400">
            @if ($publishedDate)
                Published <time datetime="{{ $publishedDate->toDateString() }}">{{ $publishedDate->format('M j, Y') }}</time>
            @else
                Published recently
            @endif
        </span>

        <span class="mt-auto pt-4 text-xs font-bold text-slate-500 dark:text-slate-400">
            {{ $votes }} {{ Str::plural('vote', $votes) }}
        </span>
    </span>
</a>
