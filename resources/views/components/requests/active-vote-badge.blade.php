@props(['recommendation'])

@php($activeVoteQuantity = $recommendation->activeVoteQuantityFor(auth()->user()))

@if ($activeVoteQuantity > 0)
    <span
        title="{{ $activeVoteQuantity === 1 ? 'You have 1 active vote here' : "You have {$activeVoteQuantity} active votes here" }}"
        data-active-vote-badge
        data-active-vote-quantity="{{ $activeVoteQuantity }}"
        {{ $attributes->class('inline-flex items-center gap-1 rounded-full border border-emerald-300/70 bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:border-emerald-400/30 dark:bg-emerald-500/10 dark:text-emerald-200') }}
    >
        <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5 9.2 16.5 19 7.5" />
        </svg>
        <span>You voted <span aria-hidden="true">&middot;</span> {{ $activeVoteQuantity }}</span>
    </span>
@endif
