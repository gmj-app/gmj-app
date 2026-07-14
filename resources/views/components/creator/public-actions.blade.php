@props(['creator', 'header'])

@php($actions = $header['actions'])
<div data-creator-public-actions class="grid grid-cols-1 gap-2 sm:flex sm:flex-wrap">
    <a
        href="{{ $actions['request_url'] }}"
        aria-label="Add a request for {{ $creator->display_name }}"
        @if (! $actions['can_add_request']) aria-disabled="true" tabindex="-1" @endif
        class="inline-flex min-h-12 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-center font-extrabold text-white shadow-lg shadow-indigo-950/25 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-white focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900 {{ $actions['can_add_request'] ? '' : 'pointer-events-none bg-slate-500 shadow-none' }}"
    >
        <span>
            <span class="block">{{ $actions['request_label'] }}</span>
            @if ($actions['request_detail'])<span class="block text-xs font-semibold text-white/75">{{ $actions['request_detail'] }}</span>@endif
        </span>
    </a>

    @if (! $header['context']['is_creator_owner'])
        @auth
            <form
                id="creator-favorite-toggle"
                method="POST"
                action="{{ route('creator.favorite', $creator) }}"
                @if ($actions['favorite_state'] && $header['guide_activity']['votes_used'] > 0)
                    x-on:submit="if ($el.dataset.participationConfirmed === '1') return; $event.preventDefault(); $dispatch('request-participation-confirmation', { formId: $el.id, mode: 'confirm', title: 'Remove favorite?', body: @js('Unfavoriting removes your active votes from this creator. Requests with no other votes may be removed.'), resourceLine: @js('Active votes on this creator: '.$header['guide_activity']['votes_used']), confirmLabel: 'Remove favorite and active votes', destructive: true });"
                @endif
            >
                @csrf
                <button
                    type="submit"
                    aria-pressed="{{ $actions['favorite_state'] ? 'true' : 'false' }}"
                    @disabled(! $actions['can_favorite'])
                    data-favorite-state="{{ $actions['favorite_state'] ? 'selected' : ($actions['can_favorite'] ? 'available' : 'unavailable') }}"
                    @class([
                        'inline-flex min-h-12 w-full items-center justify-center gap-2 rounded-xl border px-5 py-2.5 font-bold text-white backdrop-blur transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900 sm:w-auto',
                        'border-indigo-400/70 bg-indigo-500/20 shadow-[0_0_0_1px_rgba(129,140,248,0.08),0_8px_24px_rgba(79,70,229,0.16)] hover:border-indigo-300 hover:bg-indigo-500/30' => $actions['favorite_state'],
                        'border-white/25 bg-slate-950/65 shadow-sm hover:border-indigo-300/70 hover:bg-indigo-500/15' => ! $actions['favorite_state'] && $actions['can_favorite'],
                        'cursor-not-allowed border-white/10 bg-slate-900/60 text-slate-400 opacity-80' => ! $actions['can_favorite'],
                    ])
                >
                    <svg class="size-5 shrink-0 {{ $actions['favorite_state'] ? 'fill-current text-indigo-300' : 'fill-none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3.75 2.475 5.016 5.535.804-4.005 3.904.946 5.512L12 16.383l-4.951 2.603.946-5.512L3.99 9.57l5.535-.804L12 3.75Z" /></svg>
                    {{ $actions['favorite_label'] }}
                </button>
            </form>
        @else
            <a href="{{ route('login.required', ['return' => route('creator.queue', $creator, absolute: false)]) }}" aria-label="Sign in to favorite {{ $creator->display_name }}" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-white/25 bg-slate-950/65 px-5 py-2.5 font-bold text-white shadow-sm backdrop-blur transition duration-200 hover:border-indigo-300/70 hover:bg-indigo-500/15 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900">
                <svg class="size-5 shrink-0 fill-none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m12 3.75 2.475 5.016 5.535.804-4.005 3.904.946 5.512L12 16.383l-4.951 2.603.946-5.512L3.99 9.57l5.535-.804L12 3.75Z" /></svg>
                Favorite
            </a>
        @endauth
    @endif

    @if ($actions['channel_url'])
        <a href="{{ $actions['channel_url'] }}" target="_blank" rel="noopener noreferrer" aria-label="Visit {{ $creator->display_name }}'s YouTube channel (opens in a new tab)" class="inline-flex min-h-12 items-center justify-center gap-2 rounded-xl border border-white/25 bg-white/15 px-5 py-2.5 font-bold text-white backdrop-blur transition hover:bg-white/25 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
            Visit Channel
            <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M14 5h5v5M19 5l-8 8"/><path d="M19 13v5a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V6a1 1 0 0 1 1-1h5"/></svg>
        </a>
    @endif
</div>
