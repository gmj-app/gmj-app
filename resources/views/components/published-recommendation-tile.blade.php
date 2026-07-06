@props([
    'recommendation',
    'creator',
])

@php
    $publishedDate = $recommendation->published_at ?? $recommendation->updated_at ?? $recommendation->created_at;
    $display = $recommendation->publishedDisplayData();
@endphp

<article
    id="recommendation-{{ $recommendation->id }}"
    role="button"
    tabindex="0"
    x-on:keydown.enter.prevent="$el.click()"
    x-on:keydown.space.prevent="$el.click()"
    {{ $attributes->merge([
        'class' => 'group flex h-full min-w-0 scroll-mt-28 cursor-pointer flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-lg focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-emerald-700 dark:focus-visible:ring-offset-slate-950',
    ]) }}
>
    <span class="relative block aspect-video overflow-hidden bg-slate-100 dark:bg-slate-950">
        @if ($display['thumbnail_url'])
            <x-youtube-thumbnail
                :thumbnail-url="$display['thumbnail_url']"
                :title="$display['title']"
                :url="$display['url']"
                :aria-label="'Open published video: '.$display['title']"
                x-on:click.stop
                class="h-full w-full"
                image-class="group-hover:scale-105"
            />
            <span class="absolute inset-0 bg-slate-950/0 transition group-hover:bg-slate-950/10"></span>
        @else
            <span class="flex h-full w-full items-center justify-center bg-slate-100 dark:bg-slate-950">
                <span class="text-center">
                    <span class="mx-auto inline-flex size-12 items-center justify-center rounded-2xl bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5V6.75A2.75 2.75 0 0 1 6.75 4h10.5A2.75 2.75 0 0 1 20 6.75V19.5l-4.5-2.25L12 19.5l-3.5-2.25L4 19.5Z" />
                        </svg>
                    </span>
                    <span class="mt-2 block text-xs font-bold text-slate-500 dark:text-slate-400">
                        {{ $recommendation->recommendation_type === 'topic' ? 'Community topic' : 'Preview unavailable' }}
                    </span>
                </span>
            </span>
        @endif

        <span class="absolute left-3 top-3 rounded-full bg-emerald-500 px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wide text-white shadow-sm">
            {{ $display['has_published_url'] ? 'Published work' : 'Published' }}
        </span>
    </span>

    <span class="flex min-w-0 flex-1 flex-col p-4">
        <a href="{{ route('creators.published', $creator) }}#recommendation-{{ $recommendation->id }}" class="sr-only" tabindex="-1">View details for {{ $display['title'] }}</a>
        <span class="line-clamp-2 min-h-10 break-words text-sm font-extrabold leading-5 text-slate-950 transition group-hover:text-emerald-700 dark:text-white dark:group-hover:text-emerald-300">
            {{ $display['title'] }}
        </span>

        <span class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
            <span>Published {{ $publishedDate->format('M j, Y') }}</span>
            @if ($display['channel'])
                <span aria-hidden="true">&middot;</span>
                <span class="min-w-0 max-w-full truncate">{{ $display['channel'] }}</span>
            @endif
        </span>

        <span class="mt-auto pt-4 text-xs font-bold text-slate-500 dark:text-slate-400">
            {{ $recommendation->totalVotes() }} {{ Str::plural('vote', $recommendation->totalVotes()) }}
        </span>
    </span>
</article>
