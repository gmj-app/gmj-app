@props([
    'thumbnailUrl',
    'title',
    'url' => null,
    'showPlayOverlay' => true,
    'ariaLabel' => null,
    'imageClass' => '',
])

@php
    $label = $ariaLabel ?: "Open video: {$title}";
    $classes = $attributes->get('class');
    $baseClasses = 'relative block aspect-video overflow-hidden bg-slate-950';
    $imageClasses = trim('relative h-full w-full object-cover transition duration-300 group-hover:scale-105 group-hover:opacity-90 '.$imageClass);
@endphp

@if ($url)
    <a
        href="{{ $url }}"
        target="_blank"
        rel="noopener noreferrer nofollow ugc"
        aria-label="{{ $label }}"
        {{ $attributes->except('class')->merge(['class' => trim($baseClasses.' '.$classes)]) }}
    >
        <img
            src="{{ $thumbnailUrl }}"
            alt="Thumbnail for {{ $title }}"
            loading="lazy"
            onerror="this.hidden = true"
            class="{{ $imageClasses }}"
        >

        @if ($showPlayOverlay)
            <span class="pointer-events-none absolute inset-0 flex items-center justify-center" aria-hidden="true">
                <span class="flex h-14 w-20 items-center justify-center rounded-2xl bg-red-600/95 text-white shadow-xl transition group-hover:scale-105 group-hover:bg-red-500">
                    <svg viewBox="0 0 24 24" aria-hidden="true" class="h-7 w-7 fill-current"><path d="M8 5v14l11-7z"/></svg>
                </span>
            </span>
        @endif
    </a>
@else
    <div {{ $attributes->except('class')->merge(['class' => trim($baseClasses.' '.$classes)]) }}>
        <img
            src="{{ $thumbnailUrl }}"
            alt="Thumbnail for {{ $title }}"
            loading="lazy"
            onerror="this.hidden = true"
            class="{{ $imageClasses }}"
        >
    </div>
@endif
