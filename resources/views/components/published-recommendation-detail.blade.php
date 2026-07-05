@props([
    'recommendation',
])

@php
    $publishedDate = $recommendation->published_at ?? $recommendation->updated_at ?? $recommendation->created_at;
    $display = $recommendation->publishedDisplayData();
    $originalSource = $recommendation->channel_title ?: $recommendation->artist;
    $body = $recommendation->recommendation_type === 'topic'
        ? ($recommendation->description ?: $recommendation->reason)
        : ($recommendation->reason ?: $recommendation->description);
    $hasDifferentOriginalUrl = $recommendation->youtube_url
        && $recommendation->displayPublishedUrl()
        && $recommendation->youtube_url !== $recommendation->displayPublishedUrl();
@endphp

<article {{ $attributes->merge(['class' => 'overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900']) }}>
    @if ($display['thumbnail_url'])
        <a
            href="{{ $display['url'] ?: $recommendation->youtube_url }}"
            target="_blank"
            rel="noopener noreferrer"
            class="relative block aspect-video overflow-hidden bg-slate-950"
            aria-label="Open {{ $display['title'] }}"
        >
            <img
                src="{{ $display['thumbnail_url'] }}"
                alt="Thumbnail for {{ $display['title'] }}"
                loading="lazy"
                onerror="this.hidden = true"
                class="h-full w-full object-cover transition duration-300 hover:scale-105 hover:opacity-90"
            >
        </a>
    @else
        <div class="flex aspect-video items-center justify-center bg-slate-100 dark:bg-slate-950">
            <div class="text-center">
                <span class="mx-auto inline-flex size-14 items-center justify-center rounded-2xl bg-slate-200 text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                    <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5V6.75A2.75 2.75 0 0 1 6.75 4h10.5A2.75 2.75 0 0 1 20 6.75V19.5l-4.5-2.25L12 19.5l-3.5-2.25L4 19.5Z" />
                    </svg>
                </span>
                <p class="mt-3 text-sm font-bold text-slate-500 dark:text-slate-400">
                    {{ $recommendation->recommendation_type === 'topic' ? 'Community topic' : 'Video preview unavailable' }}
                </p>
            </div>
        </div>
    @endif

    <div class="p-5 sm:p-6">
        <div class="flex flex-wrap items-center gap-2">
            <span class="rounded-full bg-emerald-100 px-3 py-1.5 text-sm font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Published work</span>
            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                {{ $recommendation->recommendation_type === 'topic' ? 'Topic' : 'YouTube' }}
            </span>
            @if ($recommendation->category)
                <span class="max-w-full break-words rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold capitalize text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $recommendation->category }}</span>
            @endif
        </div>

        @if ($recommendation->creatorTags->isNotEmpty())
            <div class="mt-3 flex flex-wrap items-center gap-2" aria-label="Creator tags">
                @foreach ($recommendation->creatorTags as $tag)
                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-300">
                        {{ $tag->name }}
                    </span>
                @endforeach
            </div>
        @endif

        <h2 class="mt-5 break-words text-2xl font-extrabold leading-8 text-slate-950 dark:text-white sm:text-3xl sm:leading-9">{{ $display['title'] }}</h2>

        @if ($display['channel'])
            <p class="mt-2 text-base font-semibold text-slate-600 dark:text-slate-300">from {{ $display['channel'] }}</p>
        @endif

        <div class="mt-4 flex flex-wrap gap-x-3 gap-y-1 text-sm font-semibold text-slate-500 dark:text-slate-400">
            @if ($recommendation->isCreatorAdded())
                <span>Added by creator</span>
            @elseif ($recommendation->submittedBy)
                <span>Submitted by {{ $recommendation->submittedBy->name }}</span>
            @endif
            <span>Submitted {{ $recommendation->created_at->format('M j, Y') }}</span>
            <span>Published {{ $publishedDate->format('M j, Y') }}</span>
            <span>{{ $recommendation->user_picks_count }} {{ Str::plural('vote', $recommendation->user_picks_count) }} when published</span>
        </div>

        @if ($body)
            <div class="mt-5 whitespace-pre-line break-words text-base leading-7 text-slate-600 dark:text-slate-300">
                <x-linkified-text :text="$body" />
            </div>
        @endif

        <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-3 border-t border-slate-100 pt-5 text-base dark:border-slate-800">
            @if ($display['url'])
                <a href="{{ $display['url'] }}" target="_blank" rel="noopener noreferrer" class="font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                    Watch published content
                </a>
            @endif

            @if (! $recommendation->youtube_url)
                <span class="font-bold text-slate-500">Topic suggestion</span>
            @endif
        </div>

        <div class="mt-6 rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/70">
            <h3 class="text-sm font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Original suggestion</h3>
            <p class="mt-2 break-words text-base font-bold text-slate-900 dark:text-white">{{ $recommendation->title }}</p>
            @if ($originalSource)
                <p class="mt-1 text-sm font-semibold text-slate-600 dark:text-slate-300">{{ $recommendation->channel_title ? 'from' : 'by' }} {{ $originalSource }}</p>
            @endif
            @if ($recommendation->youtube_url && $hasDifferentOriginalUrl)
                <a href="{{ $recommendation->youtube_url }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex text-sm font-bold text-red-600 hover:text-red-500">
                    {{ $recommendation->youtube_video_id ? 'Watch original' : 'Open original link' }}
                </a>
            @endif
        </div>
    </div>
</article>
