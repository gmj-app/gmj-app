@props([
    'creatorCount',
    'guideCount',
    'header' => false,
])

@php($visibilityClasses = $header ? 'hidden xl:inline-flex' : 'inline-flex')

<dl
    data-platform-stats
    aria-label="Guide My Journey community statistics"
    {{ $attributes->class([$visibilityClasses, 'min-w-48 shrink-0 items-center justify-center rounded-xl border border-slate-200/80 bg-white/60 px-3 py-2 text-slate-600 shadow-sm shadow-slate-950/5 backdrop-blur dark:border-white/10 dark:bg-white/[0.04] dark:text-slate-300']) }}
>
    <div class="flex items-baseline gap-1.5 whitespace-nowrap">
        <dt class="order-2 text-xs font-semibold">{{ Str::plural('Creator', $creatorCount) }}</dt>
        <dd class="order-1 text-sm font-extrabold tabular-nums text-slate-900 dark:text-white">{{ number_format($creatorCount) }}</dd>
    </div>

    <div class="ml-3 flex items-baseline gap-1.5 whitespace-nowrap border-l border-slate-200 pl-3 dark:border-slate-700">
        <dt class="order-2 text-xs font-semibold">{{ Str::plural('Guide', $guideCount) }}</dt>
        <dd class="order-1 text-sm font-extrabold tabular-nums text-slate-900 dark:text-white">{{ number_format($guideCount) }}</dd>
    </div>
</dl>
