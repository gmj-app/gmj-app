@props(['creator', 'header'])

@php($actions = $header['actions'])
<div data-creator-public-actions class="grid grid-cols-1 gap-2 sm:flex sm:flex-wrap">
    <x-creator.header-action-button
        href="{{ $actions['request_url'] }}"
        aria-label="Add a request for {{ $creator->display_name }}"
        :variant="$actions['can_add_request'] ? 'primary' : 'primary-disabled'"
        :aria-disabled="$actions['can_add_request'] ? null : 'true'"
        :tabindex="$actions['can_add_request'] ? null : '-1'"
    >
        <span class="min-w-0">
            <span class="block break-words">{{ $actions['request_label'] }}</span>
            @if ($actions['request_detail'])<span class="block text-xs font-semibold text-white/75">{{ $actions['request_detail'] }}</span>@endif
        </span>
    </x-creator.header-action-button>

    @if (! $header['context']['is_creator_owner'])
        @auth
            <form
                id="creator-favorite-toggle"
                method="POST"
                action="{{ route('creator.favorite', $creator) }}"
                class="w-full sm:w-auto"
                @if ($actions['favorite_state'] && $header['guide_activity']['votes_used'] > 0)
                    x-on:submit="if ($el.dataset.participationConfirmed === '1') return; $event.preventDefault(); $dispatch('request-participation-confirmation', { formId: $el.id, mode: 'confirm', title: 'Remove favorite?', body: @js('Unfavoriting removes your active votes from this creator. Requests with no other votes may be removed.'), resourceLine: @js('Active votes on this creator: '.$header['guide_activity']['votes_used']), confirmLabel: 'Remove favorite and active votes', destructive: true });"
                @endif
            >
                @csrf
                <x-creator.header-action-button
                    as="button"
                    type="submit"
                    aria-pressed="{{ $actions['favorite_state'] ? 'true' : 'false' }}"
                    :disabled="$actions['can_favorite'] ? null : true"
                    data-favorite-state="{{ $actions['favorite_state'] ? 'selected' : ($actions['can_favorite'] ? 'available' : 'unavailable') }}"
                    :variant="$actions['favorite_state'] ? 'selected' : ($actions['can_favorite'] ? 'secondary' : 'unavailable')"
                >
                    <svg class="size-5 shrink-0 {{ $actions['favorite_state'] ? 'fill-current text-indigo-300' : 'fill-none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3.75 2.475 5.016 5.535.804-4.005 3.904.946 5.512L12 16.383l-4.951 2.603.946-5.512L3.99 9.57l5.535-.804L12 3.75Z" /></svg>
                    <span class="min-w-0 break-words">{{ $actions['favorite_label'] }}</span>
                </x-creator.header-action-button>
            </form>
        @else
            <x-creator.header-action-button href="{{ route('login.required', ['return' => route('creator.queue', $creator, absolute: false)]) }}" aria-label="Sign in to favorite {{ $creator->display_name }}">
                <svg class="size-5 shrink-0 fill-none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3.75 2.475 5.016 5.535.804-4.005 3.904.946 5.512L12 16.383l-4.951 2.603.946-5.512L3.99 9.57l5.535-.804L12 3.75Z" /></svg>
                <span class="min-w-0 break-words">Favorite</span>
            </x-creator.header-action-button>
        @endauth
    @endif

    @if ($actions['channel_url'])
        <x-creator.header-action-button href="{{ $actions['channel_url'] }}" target="_blank" rel="noopener noreferrer" aria-label="Visit {{ $creator->display_name }}'s YouTube channel (opens in a new tab)" variant="channel">
            <span class="min-w-0 break-words">Visit Channel</span>
            <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 5h5v5M19 5l-8 8"/><path d="M19 13v5a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"/></svg>
        </x-creator.header-action-button>
    @endif
</div>
