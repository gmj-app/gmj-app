@props([
    'recommendation',
    'showVoteIndicator' => true,
])

@php
    $currentUser = auth()->user();
    $votedByCurrentUser = $recommendation->votedBy($currentUser);
    $requestedByCurrentUser = $recommendation->requestedByCurrentUser($currentUser);
@endphp

@if ($currentUser && (($showVoteIndicator && $votedByCurrentUser) || $requestedByCurrentUser))
    <span {{ $attributes->class('flex flex-wrap items-center gap-1.5') }}>
        @if ($showVoteIndicator && $votedByCurrentUser)
            <x-requests.active-vote-badge :recommendation="$recommendation" />
        @endif

        <x-requests.requested-by-you-badge :recommendation="$recommendation" />
    </span>
@endif
