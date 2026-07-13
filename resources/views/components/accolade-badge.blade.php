@props(['definition', 'size' => 'md', 'showLabel' => true])
@php
    $styles = [
        'emerald' => 'border-emerald-300 bg-emerald-100 text-emerald-800 dark:border-emerald-700 dark:bg-emerald-950 dark:text-emerald-200',
        'sky' => 'border-sky-300 bg-sky-100 text-sky-800 dark:border-sky-700 dark:bg-sky-950 dark:text-sky-200',
        'violet' => 'border-violet-300 bg-violet-100 text-violet-800 dark:border-violet-700 dark:bg-violet-950 dark:text-violet-200',
        'amber' => 'border-amber-300 bg-amber-100 text-amber-900 dark:border-amber-700 dark:bg-amber-950 dark:text-amber-200',
        'rose' => 'border-rose-300 bg-rose-100 text-rose-800 dark:border-rose-700 dark:bg-rose-950 dark:text-rose-200',
        'indigo' => 'border-indigo-300 bg-indigo-100 text-indigo-800 dark:border-indigo-700 dark:bg-indigo-950 dark:text-indigo-200',
        'slate' => 'border-slate-300 bg-slate-100 text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100',
    ];
    $icon = $definition['icon_key'];
@endphp
<span {{ $attributes->class(['inline-flex items-center gap-1.5 rounded-full border font-bold', $styles[$definition['badge_style_key']] ?? $styles['slate'], 'px-2.5 py-1 text-xs' => $size === 'sm', 'px-3 py-1.5 text-sm' => $size !== 'sm']) }} title="{{ $definition['description'] }}">
    <svg class="size-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
        @switch($icon)
            @case('calendar') <rect x="4" y="5" width="16" height="15" rx="2"/><path d="M8 3v4M16 3v4M4 10h16"/> @break
            @case('community') @case('network') <circle cx="8" cy="9" r="3"/><circle cx="17" cy="8" r="2"/><path d="M3 20c0-4 2-6 5-6s5 2 5 6M14 13c3 0 5 2 5 5"/> @break
            @case('compass') @case('gps') <circle cx="12" cy="12" r="9"/><path d="m15 9-2 4-4 2 2-4 4-2Z"/> @break
            @case('ear') <path d="M17 16c-1 3-3 5-6 4-2-.6-2-2-2-4v-4a5 5 0 1 1 10 0c0 2-1 3-3 4-1 .5-1 1-1 2"/> @break
            @case('globe') <circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/> @break
            @case('handshake') <path d="m4 13 4-4 4 3 3-2 5 5-4 4-4-2-2 1-6-5Z"/> @break
            @case('map') @case('route') @case('trail-marker') <path d="M5 20V5l5-2 4 2 5-2v15l-5 2-4-2-5 2Z"/><path d="M10 3v15M14 5v15"/> @break
            @case('ripple') <circle cx="12" cy="12" r="2"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="10"/> @break
            @case('summit') <path d="m3 20 7-13 3 5 2-3 6 11H3Z"/><path d="m8 11 2 2 2-2"/> @break
            @default <path d="M12 3 9 9l-6 1 4 5-1 6 6-3 6 3-1-6 4-5-6-1-3-6Z"/>
        @endswitch
    </svg>
    @if($showLabel)<span>{{ $definition['name'] }}</span>@endif
</span>
