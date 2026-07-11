@props([
    'eyebrow' => null,
    'title' => null,
    'subtitle' => null,
    'level' => 2,
])

<div {{ $attributes->class('flex min-w-0 flex-col gap-4 sm:flex-row sm:items-end sm:justify-between') }}>
    <div class="min-w-0">
        @if ($eyebrow)
            <p class="text-xs font-semibold uppercase tracking-[0.16em] text-indigo-600 dark:text-indigo-300">{{ $eyebrow }}</p>
        @endif
        <h{{ $level }} class="mt-1 text-2xl font-semibold tracking-tight text-slate-950 dark:text-slate-100">{{ $title }}</h{{ $level }}>
        @if ($subtitle)
            <p class="mt-1 max-w-3xl text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $subtitle }}</p>
        @endif
    </div>

    @isset($actions)
        <div class="shrink-0">{{ $actions }}</div>
    @endisset
</div>
