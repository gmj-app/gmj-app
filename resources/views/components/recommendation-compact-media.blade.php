@props(['recommendation'])

@php
    $isTopic = $recommendation->recommendation_type === 'topic';
    $thumbnailUrl = $isTopic ? null : $recommendation->compactThumbnailUrl();
@endphp

@if ($isTopic)
    <span
        aria-hidden="true"
        class="relative h-9 w-16 shrink-0 overflow-hidden rounded-md border border-indigo-300/50 bg-gradient-to-br from-slate-900 via-indigo-900 to-indigo-700 shadow-sm ring-1 ring-inset ring-white/10 md:h-[50px] md:w-[88px]"
    >
        <span class="absolute -right-3 -top-4 size-10 rounded-full bg-indigo-400/15 blur-md"></span>
        <span class="absolute inset-0 flex items-center justify-center pb-2 text-indigo-100 md:pb-2.5">
            <svg class="size-[18px] md:size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 18.25 4 20v-4.5A8 8 0 1 1 7.5 18.25Z" />
                <path stroke-linecap="round" d="M8 9h8M8 12h5" />
            </svg>
        </span>
        <span class="absolute inset-x-0 bottom-1 text-center text-[8px] font-bold uppercase leading-none tracking-[0.14em] text-white/95 md:text-[9px]">Topic</span>
    </span>
@elseif ($recommendation->isYouTubePlaylist())
    <span class="relative h-9 w-16 shrink-0 overflow-hidden rounded-md border border-violet-400/30 bg-gradient-to-br from-slate-950 via-violet-950 to-indigo-800 md:h-[50px] md:w-[88px]">
        @if ($thumbnailUrl)
            <img
                src="{{ $thumbnailUrl }}"
                alt=""
                aria-hidden="true"
                loading="lazy"
                decoding="async"
                width="88"
                height="50"
                onerror="this.hidden = true"
                class="h-full w-full object-cover"
            >
            <span class="absolute inset-0 bg-gradient-to-t from-slate-950/70 via-transparent to-transparent"></span>
        @endif
        <span class="absolute bottom-0.5 right-0.5 inline-flex items-center gap-0.5 rounded bg-slate-950/80 px-1 py-0.5 text-[7px] font-bold uppercase leading-none text-white backdrop-blur-sm md:bottom-1 md:right-1 md:text-[8px]">
            <svg class="size-2.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                <path stroke-linecap="round" d="M9 7h11M9 12h11M9 17h11M4 7h.01M4 12h.01M4 17h.01" />
            </svg>
            {{ $recommendation->displayItemCount() ?? 'Playlist' }}
        </span>
    </span>
@elseif ($thumbnailUrl)
    <span class="h-9 w-16 shrink-0 overflow-hidden rounded-md border border-slate-200 bg-slate-800 md:h-[50px] md:w-[88px] dark:border-slate-700">
        <img
            src="{{ $thumbnailUrl }}"
            alt=""
            aria-hidden="true"
            loading="lazy"
            decoding="async"
            width="88"
            height="50"
            onerror="this.parentElement.hidden = true"
            class="h-full w-full object-cover transition group-hover:brightness-105"
        >
    </span>
@endif
