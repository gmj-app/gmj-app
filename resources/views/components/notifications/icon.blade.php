@props(['name' => 'bell', 'severity' => 'info'])
@php
    $name = in_array($name, config('notifications.icons', []), true) ? $name : 'bell';
    $severity = in_array($severity, config('notifications.severities', []), true) ? $severity : 'info';
    $severityClass = match ($severity) {
        'success' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300',
        'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300',
        'danger' => 'bg-red-100 text-red-700 dark:bg-red-950 dark:text-red-300',
        default => 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300',
    };
@endphp
<span {{ $attributes->class('inline-flex size-9 shrink-0 items-center justify-center rounded-full '.$severityClass) }} aria-hidden="true">
    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
        @switch($name)
            @case('check-circle') <circle cx="12" cy="12" r="9"/><path d="m8 12 2.5 2.5L16 9"/> @break
            @case('list-check') <path d="M9 6h11M9 12h11M9 18h11M4 6l1 1 2-2M4 12l1 1 2-2M4 18l1 1 2-2"/> @break
            @case('star') <path d="m12 3 2.7 5.5 6 .9-4.4 4.2 1 6-5.3-2.8-5.3 2.8 1-6-4.4-4.2 6-.9L12 3Z"/> @break
            @case('trophy') <path d="M8 4h8v4a4 4 0 0 1-8 0V4Zm4 8v5m-4 3h8M8 6H5v1a4 4 0 0 0 4 4m7-5h3v1a4 4 0 0 1-4 4"/> @break
            @case('megaphone') <path d="M4 13V9l12-4v12L4 13Zm4 1 1 5h3l-1-4"/> @break
            @case('user') <circle cx="12" cy="8" r="3"/><path d="M5 20a7 7 0 0 1 14 0"/> @break
            @case('credit-card') <rect x="3" y="5" width="18" height="14" rx="2"/><path d="M3 10h18M7 15h3"/> @break
            @case('alert-triangle') <path d="M12 3 2.5 20h19L12 3Zm0 6v5m0 3h.01"/> @break
            @case('settings') <circle cx="12" cy="12" r="3"/><path d="M19 12a7 7 0 0 0-.1-1l2-1.5-2-3.4-2.4 1A8 8 0 0 0 15 6l-.3-2.5h-4L10.4 6A8 8 0 0 0 9 7.1l-2.4-1-2 3.4 2 1.5a7 7 0 0 0 0 2l-2 1.5 2 3.4 2.4-1a8 8 0 0 0 1.4 1.1l.3 2.5h4L15 18a8 8 0 0 0 1.4-1.1l2.4 1 2-3.4-2-1.5a7 7 0 0 0 .2-1Z"/> @break
            @case('shield') <path d="M12 3 5 6v5c0 4.5 2.8 8 7 10 4.2-2 7-5.5 7-10V6l-7-3Z"/> @break
            @default <path d="M18 9a6 6 0 0 0-12 0c0 7-3 7-3 7h18s-3 0-3-7M10 20h4"/>
        @endswitch
    </svg>
</span>
