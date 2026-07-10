@props(['recommendation'])

@php
    $isTopic = $recommendation->recommendation_type === 'topic';
    $thumbnailUrl = $isTopic ? null : $recommendation->displayThumbnailUrl();
@endphp

@if ($isTopic)
    <span
        aria-hidden="true"
        class="relative h-9 w-16 shrink-0 overflow-hidden rounded-md border border-indigo-400/25 bg-gradient-to-br from-slate-900 via-indigo-950 to-indigo-800 shadow-sm sm:h-[50px] sm:w-[88px]"
    >
        <span class="absolute -right-3 -top-4 size-10 rounded-full bg-indigo-400/15 blur-md"></span>
        <span class="absolute inset-0 flex items-center justify-center pb-1.5 text-indigo-200/90 sm:pb-2">
            <svg class="size-4 sm:size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 18.25 4 20v-4.5A8 8 0 1 1 7.5 18.25Z" />
                <path stroke-linecap="round" d="M8 9h8M8 12h5" />
            </svg>
        </span>
        <span class="absolute inset-x-0 bottom-0.5 text-center text-[7px] font-semibold uppercase leading-none tracking-[0.18em] text-indigo-100/80 sm:bottom-1 sm:text-[8px]">Topic</span>
    </span>
@elseif ($thumbnailUrl)
    <span class="h-9 w-16 shrink-0 overflow-hidden rounded-md border border-slate-200 bg-slate-800 sm:h-[50px] sm:w-[88px] dark:border-slate-700">
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
