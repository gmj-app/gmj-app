@props([
    'eyebrow' => null,
    'title' => null,
    'subtitle' => null,
    'compact' => false,
    'align' => 'left',
])

@php
    $centered = $align === 'center';
@endphp

<div {{ $attributes->class([
    'flex min-w-0 flex-col gap-5 sm:flex-row sm:items-end sm:justify-between',
    'text-center sm:block' => $centered,
]) }}>
    <div @class(['min-w-0', 'mx-auto' => $centered])>
        @if ($eyebrow)
            <p class="text-xs font-semibold uppercase tracking-[0.18em] text-emerald-600 dark:text-emerald-300">{{ $eyebrow }}</p>
        @endif
        <h1 @class([
            'font-semibold tracking-tight text-slate-950 dark:text-slate-50',
            'mt-1 text-2xl sm:text-3xl' => $compact,
            'mt-1 text-3xl md:text-4xl' => ! $compact,
        ])>
            @isset($titleContent){{ $titleContent }}@else{{ $title }}@endisset
        </h1>
        @if ($subtitle)
            <p @class(['mt-2 max-w-3xl text-base leading-relaxed text-slate-600 dark:text-slate-300', 'mx-auto' => $centered])>{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="shrink-0">{{ $actions }}</div>
    @endisset
</div>
