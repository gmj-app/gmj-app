@props([
    'recommendation',
])

@php
    $currentUser = auth()->user();
    $currentUserVotes = $currentUser
        ? $recommendation->currentUserVoteCount($currentUser)
        : 0;
    $votedByCurrentUser = $recommendation->votedBy($currentUser);
    $requestedByCurrentUser = $recommendation->requestedByCurrentUser($currentUser);
@endphp

@if ($currentUser && ($votedByCurrentUser || $requestedByCurrentUser))
    <span {{ $attributes->class('flex flex-wrap items-center gap-1.5') }}>
        @if ($votedByCurrentUser)
            <span
                title="{{ $currentUserVotes === 1 ? 'You voted here with 1 vote' : "You voted here with {$currentUserVotes} votes" }}"
                class="inline-flex items-center gap-1 rounded-full border border-emerald-300/70 bg-emerald-50 px-2 py-0.5 text-xs font-semibold text-emerald-700 dark:border-emerald-400/30 dark:bg-emerald-500/10 dark:text-emerald-200"
            >
                <svg class="size-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 12.5 9.2 16.5 19 7.5" />
                </svg>
                <span>You voted</span>
            </span>
        @endif

        <x-requests.requested-by-you-badge :recommendation="$recommendation" />
    </span>
@endif
