@props(['recommendation'])

@if ($recommendation->requestedByCurrentUser(auth()->user()))
    <span
        title="You requested"
        {{ $attributes->class('inline-flex items-center gap-1 rounded-full border border-amber-300/70 bg-amber-50 px-2 py-0.5 text-xs font-semibold text-amber-700 dark:border-amber-400/30 dark:bg-amber-500/10 dark:text-amber-200') }}
    >
        <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m12 3.75 2.475 5.016 5.535.804-4.005 3.904.946 5.512L12 16.383l-4.951 2.603.946-5.512L3.99 9.57l5.535-.804L12 3.75Z" />
        </svg>
        <span>You requested</span>
    </span>
@endif
