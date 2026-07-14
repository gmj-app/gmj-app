@props([
    'recommendation',
    'creator',
    'usage' => null,
    'topRequested' => false,
    'anchor' => true,
    'showVotingControls' => true,
    'ownsCreator' => false,
])

@php
    $recommendationAction = session('recommendation_action');
    $hasRecommendationAction = is_array($recommendationAction)
        && (int) ($recommendationAction['recommendation_id'] ?? 0) === $recommendation->id;
    $alternativeErrorsOpen = $errors->any() && (int) old('alternative_recommendation_id') === $recommendation->id;
    $alternatives = $ownsCreator && $recommendation->relationLoaded('alternatives')
        ? $recommendation->alternatives
        : collect();
    $totalVotes = $recommendation->totalVotes();
    $currentUserVotes = auth()->check()
        ? $recommendation->activeVoteQuantityFor(auth()->user())
        : 0;
    $voteLimit = (int) ($usage['votes_limit'] ?? auth()->user()?->membershipLimits()['votes_per_reactor'] ?? 0);
    $votesRemaining = (int) ($usage['votes_remaining'] ?? 0);
    $canAddVote = ! auth()->check() || $votesRemaining > 0;
    $canWithdraw = $recommendation->canBeWithdrawnBy(auth()->user());
@endphp

<article
    data-recommendation-expanded-card
    @if ($anchor) id="recommendation-{{ $recommendation->id }}" @endif
    x-data="{ alternativeOpen: @js($alternativeErrorsOpen), withdrawOpen: false }"
    class="group min-w-0 scroll-mt-24 overflow-hidden rounded-3xl border bg-white shadow-sm transition duration-200 dark:bg-slate-900 md:hover:-translate-y-0.5 md:hover:shadow-xl {{ $hasRecommendationAction ? 'border-indigo-300 ring-1 ring-indigo-400/40 dark:border-indigo-700 dark:ring-indigo-500/40' : 'border-slate-200 dark:border-slate-800 md:hover:border-indigo-200 dark:md:hover:border-indigo-800' }}"
>
    @if ($recommendation->hasMediaPreview())
    @if ($recommendation->isYouTubePlaylist())
        <a
            href="{{ $recommendation->canonicalMediaUrl() }}"
            target="_blank"
            rel="noopener noreferrer nofollow ugc"
            class="relative block aspect-video overflow-hidden bg-gradient-to-br from-slate-950 via-violet-950 to-indigo-900"
            aria-label="Open playlist: {{ $recommendation->displaySourceTitle() }}"
        >
            @if ($recommendation->displayThumbnailUrl())
                <img src="{{ $recommendation->displayThumbnailUrl() }}" alt="Thumbnail for {{ $recommendation->displaySourceTitle() }}" loading="lazy" decoding="async" width="1280" height="720" onerror="this.hidden = true" class="h-full w-full object-cover transition duration-300 group-hover:scale-105 group-hover:opacity-90">
                <span class="absolute inset-0 bg-slate-950/20"></span>
            @endif
            <span class="absolute inset-0 flex items-center justify-center">
                <span class="inline-flex items-center gap-2 rounded-xl bg-slate-950/80 px-4 py-3 text-sm font-bold text-white shadow-xl backdrop-blur-sm">
                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M9 7h11M9 12h11M9 17h11M4 7h.01M4 12h.01M4 17h.01" /></svg>
                    YouTube Playlist
                    @if ($recommendation->displayItemCount() !== null)
                        · {{ $recommendation->displayItemCount() }} {{ Str::plural('video', $recommendation->displayItemCount()) }}
                    @endif
                </span>
            </span>
        </a>
    @elseif ($recommendation->youtubeThumbnailUrl())
        <a
            href="{{ $recommendation->youtube_url }}"
            target="_blank"
            rel="noopener noreferrer nofollow ugc"
            class="relative block aspect-video overflow-hidden bg-slate-950"
            aria-label="Watch {{ $recommendation->displayTitle() }} on YouTube"
        >
            <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-slate-800 to-slate-950">
                <div class="text-center">
                    <span class="mx-auto flex h-16 w-20 items-center justify-center rounded-2xl bg-slate-700 text-white shadow-lg">
                        <svg viewBox="0 0 24 24" aria-hidden="true" class="h-8 w-8 fill-current"><path d="M8 5v14l11-7z"/></svg>
                    </span>
                    <p class="mt-3 text-sm font-medium text-slate-300">Video preview unavailable</p>
                </div>
            </div>
            <img
                src="{{ $recommendation->youtubeThumbnailUrl() }}"
                alt="Thumbnail for {{ $recommendation->displayTitle() }}"
                loading="lazy"
                onerror="this.hidden = true"
                class="relative h-full w-full object-cover transition duration-300 group-hover:scale-105 group-hover:opacity-90"
            >
            <span class="pointer-events-none absolute inset-0 flex items-center justify-center">
                <span class="flex h-14 w-20 items-center justify-center rounded-2xl bg-red-600/95 text-white shadow-xl transition group-hover:scale-105 group-hover:bg-red-500">
                    <svg viewBox="0 0 24 24" aria-hidden="true" class="h-7 w-7 fill-current"><path d="M8 5v14l11-7z"/></svg>
                </span>
            </span>
        </a>
    @elseif ($recommendation->youtube_url)
        <a
            href="{{ $recommendation->youtube_url }}"
            target="_blank"
            rel="noopener noreferrer nofollow ugc"
            class="flex aspect-video items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 transition hover:from-slate-200 hover:to-slate-300 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-inset dark:from-slate-800 dark:to-slate-950 dark:hover:from-slate-800/80 dark:hover:to-slate-900"
            aria-label="Open original link for {{ $recommendation->displayTitle() }}"
        >
            <span class="text-center">
                <span class="mx-auto flex h-16 w-20 items-center justify-center rounded-2xl bg-slate-700 text-white shadow-lg dark:bg-slate-600">
                    <svg viewBox="0 0 24 24" aria-hidden="true" class="h-8 w-8 fill-current"><path d="M8 5v14l11-7z"/></svg>
                </span>
                <span class="mt-3 block text-sm font-medium text-slate-500 dark:text-slate-400">Video preview unavailable</span>
            </span>
        </a>
    @else
        <div class="flex aspect-video items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-950">
            <div class="text-center">
                <span class="mx-auto flex h-16 w-20 items-center justify-center rounded-2xl bg-slate-700 text-white shadow-lg dark:bg-slate-600">
                    <svg viewBox="0 0 24 24" aria-hidden="true" class="h-8 w-8 fill-current"><path d="M8 5v14l11-7z"/></svg>
                </span>
                <p class="mt-3 text-sm font-medium text-slate-500 dark:text-slate-400">
                    {{ $recommendation->recommendation_type === 'topic' ? 'Community topic' : 'Video preview unavailable' }}
                </p>
            </div>
        </div>
    @endif
    @endif

    <div class="p-5 sm:p-6">
        @if ($recommendation->isTopicOnly())
            <div class="border-l-2 border-cyan-500/60 pl-3 sm:pl-4">
                <div class="flex min-w-0 items-start gap-2.5">
                    <svg class="mt-0.5 size-6 shrink-0 text-cyan-700 dark:text-cyan-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 18.25 4 20v-4.5A8 8 0 1 1 7.5 18.25Z" />
                    </svg>
                    <h3 class="min-w-0 break-words text-2xl font-semibold leading-tight text-cyan-800 dark:text-cyan-200 sm:text-[1.65rem]">{{ $recommendation->displayTitle() }}</h3>
                </div>
            </div>
        @endif

        <div class="flex flex-wrap items-center gap-2 {{ $recommendation->isTopicOnly() ? 'mt-3' : '' }}">
            @if ($topRequested)
                <span class="rounded-full bg-fuchsia-100 px-3 py-1.5 text-sm font-semibold text-fuchsia-800 dark:bg-fuchsia-950 dark:text-fuchsia-300">Top requested</span>
            @endif
            @if ($recommendation->is_pinned)
                <span class="rounded-full bg-amber-100 px-3 py-1.5 text-sm font-semibold text-amber-800 dark:bg-amber-950 dark:text-amber-300">Pinned</span>
            @endif
            @if ($recommendation->isYouTubePlaylist())
                <span class="rounded-full bg-violet-100 px-3 py-1.5 text-sm font-semibold text-violet-700 dark:bg-violet-950 dark:text-violet-300">Playlist</span>
            @endif
            @if ($recommendation->isCreatorAdded())
                <span class="rounded-full bg-violet-100 px-3 py-1.5 text-sm font-semibold text-violet-700 dark:bg-violet-950 dark:text-violet-300">Added by creator</span>
            @endif
            <x-requests.status-badge :request="$recommendation" />
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">
                {{ $recommendation->recommendation_type === 'topic' ? 'Topic' : 'YouTube' }}
            </span>
            @if ($recommendation->category)
                <span class="max-w-full break-words rounded-full bg-slate-100 px-3 py-1.5 text-sm font-semibold capitalize text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $recommendation->category }}</span>
            @endif
        </div>

        @if ($recommendation->creatorTags->isNotEmpty())
            <div class="mt-3 flex flex-wrap items-center gap-2" aria-label="Creator tags">
                @foreach ($recommendation->creatorTags->take(3) as $tag)
                    <span class="rounded-full border border-slate-200 bg-slate-50 px-2.5 py-1 text-xs font-semibold text-slate-600 dark:border-slate-700 dark:bg-slate-800/80 dark:text-slate-300">
                        {{ $tag->name }}
                    </span>
                @endforeach
                @if ($recommendation->creatorTags->count() > 3)
                    <span class="text-xs font-medium text-slate-500 dark:text-slate-400">+{{ $recommendation->creatorTags->count() - 3 }} more</span>
                @endif
            </div>
        @endif

        @unless ($recommendation->isTopicOnly())
            <h3 class="mt-5 break-words text-xl font-semibold leading-7 text-slate-950 dark:text-white sm:text-2xl">{{ $recommendation->displayTitle() }}</h3>
        @endunless

        <x-recommendation-user-indicators
            :recommendation="$recommendation"
            class="mt-2"
        />

        @if ($recommendation->channel_title)
            <p class="mt-2 text-base font-medium text-slate-600 dark:text-slate-300">from {{ $recommendation->channel_title }}</p>
        @elseif ($recommendation->artist)
            <p class="mt-2 text-base font-medium text-slate-600 dark:text-slate-300">by {{ $recommendation->artist }}</p>
        @endif

        <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-sm text-slate-500 dark:text-slate-400">
            @if ($recommendation->isCreatorAdded())
                <span>Added by creator</span>
            @elseif ($recommendation->submittedBy)
                <span>Suggested by {{ $recommendation->submittedBy->publicName() }}</span>
            @endif
            <span>Submitted {{ $recommendation->created_at->format('M j, Y') }}</span>
        </div>

        @auth
            <div class="mt-3 flex flex-wrap items-center gap-x-4 gap-y-2">
                <x-requests.edit-own-request-action :recommendation="$recommendation" />

                <button
                    type="button"
                    x-on:click="alternativeOpen = true"
                    class="text-sm font-semibold text-indigo-600 transition hover:text-indigo-500 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
                >
                    Suggest alternative
                </button>

                @if ($canWithdraw)
                    <button
                        type="button"
                        x-on:click="withdrawOpen = true"
                        aria-label="Withdraw this request"
                        class="text-sm font-semibold text-red-600 transition hover:text-red-500 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-red-500 dark:text-red-300 dark:hover:text-red-200"
                    >
                        Withdraw request
                    </button>
                @endif
            </div>
        @endauth

        <x-recommendation-community-support
            :recommendation="$recommendation"
            class="mt-4"
        />

        @if ($ownsCreator && $alternatives->isNotEmpty())
            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-4 dark:border-amber-500/30 dark:bg-amber-950/20">
                <div class="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-wide text-amber-700 dark:text-amber-300">Creator only</p>
                        <h4 class="mt-1 text-sm font-bold text-slate-950 dark:text-white">
                            {{ $alternatives->count() }} {{ Str::plural('alternative', $alternatives->count()) }} suggested
                        </h4>
                    </div>
                    <span class="rounded-full bg-white/80 px-3 py-1 text-xs font-bold text-amber-800 ring-1 ring-amber-200 dark:bg-slate-950/70 dark:text-amber-200 dark:ring-amber-500/30">Private</span>
                </div>

                <div class="mt-4 space-y-3">
                    @foreach ($alternatives as $alternative)
                        <div class="rounded-xl border border-amber-200/80 bg-white p-3 dark:border-amber-500/20 dark:bg-slate-950/70">
                            <div class="flex flex-wrap items-start justify-between gap-3">
                                <div class="min-w-0">
                                    <a href="{{ $alternative->alternative_url }}" target="_blank" rel="noopener noreferrer nofollow ugc" class="break-all text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-300">
                                        {{ $alternative->alternative_url }}
                                    </a>
                                    <p class="mt-2 whitespace-pre-line text-sm leading-6 text-slate-700 dark:text-slate-200">{{ $alternative->reason }}</p>
                                    <p class="mt-2 text-xs font-medium text-slate-500 dark:text-slate-400">
                                        Suggested by {{ $alternative->user?->publicName() ?? 'Unknown guide' }} on {{ $alternative->created_at->format('M j, Y') }}
                                    </p>
                                </div>

                                <span class="rounded-full px-2.5 py-1 text-xs font-bold capitalize {{ $alternative->status === 'accepted' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : ($alternative->status === 'dismissed' ? 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300' : 'bg-amber-100 text-amber-700 dark:bg-amber-950 dark:text-amber-300') }}">
                                    {{ $alternative->status }}
                                </span>
                            </div>

                            @if ($alternative->status === 'pending')
                                <div class="mt-3 flex flex-wrap justify-end gap-2">
                                    <form method="POST" action="{{ route('recommendations.alternatives.dismiss', [$creator, $recommendation, $alternative]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl border border-slate-200 bg-white px-3 py-2 text-sm font-semibold text-slate-600 transition hover:border-slate-300 hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300 dark:hover:bg-slate-800">
                                            Dismiss
                                        </button>
                                    </form>
                                    <form method="POST" action="{{ route('recommendations.alternatives.accept', [$creator, $recommendation, $alternative]) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="inline-flex min-h-10 items-center justify-center rounded-xl bg-indigo-600 px-3 py-2 text-sm font-bold text-white transition hover:bg-indigo-500">
                                            Use this alternative
                                        </button>
                                    </form>
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($recommendation->recommendation_type === 'topic' && $recommendation->description)
            <x-plain-expandable-text :text="$recommendation->description" label="Topic description" />
        @endif

        @if ($recommendation->reason)
            <x-plain-expandable-text :text="$recommendation->reason" label="Why this was suggested" />
        @endif

        @if ($recommendation->request_context)
            <x-plain-expandable-text :text="$recommendation->request_context" label="Guide context" />
        @endif

        @if ($recommendation->status === 'scheduled' && $recommendation->scheduled_for)
            <p class="mt-4 rounded-xl bg-indigo-50 px-4 py-3 text-sm font-medium text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                Scheduled for {{ $recommendation->scheduled_for->format('M j, Y \a\t g:i A') }}
            </p>
        @endif

        @if ($recommendation->status === 'already_seen')
            <p class="mt-4 rounded-xl bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-700 dark:bg-sky-950/60 dark:text-sky-300">
                The creator has already seen this.
            </p>
        @endif

        @if (($recommendation->status === 'published' && $recommendation->published_reaction_url) || ! $recommendation->youtube_url)
            <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-3 border-t border-slate-100 pt-5 text-base dark:border-slate-800">
                @if ($recommendation->status === 'published' && $recommendation->published_reaction_url)
                    <a href="{{ $recommendation->published_reaction_url }}" target="_blank" rel="noopener noreferrer" class="font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                        Watch published content
                    </a>
                @endif

                @if (! $recommendation->youtube_url)
                    <span class="font-medium text-slate-500">Topic request</span>
                @endif
            </div>
        @endif

        <div class="mt-5 flex items-center justify-end">
            @if ($showVotingControls && $recommendation->isVotable())
                <div class="inline-flex w-full flex-col gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 sm:w-auto sm:flex-row sm:items-center sm:gap-5">
                    <div class="flex min-h-11 items-center justify-center leading-none sm:justify-start">
                        <p aria-hidden="true" class="text-3xl font-extrabold leading-none text-slate-950 dark:text-white">{{ $totalVotes }}</p>
                        <span class="sr-only">{{ $totalVotes }} total {{ Str::plural('vote', $totalVotes) }}</span>
                    </div>

                    @auth
                        <div data-vote-controls class="grid w-full max-w-xs grid-cols-[2.75rem_minmax(4.5rem,auto)_2.75rem] items-center justify-center gap-2 sm:flex sm:w-auto sm:max-w-none sm:justify-end">
                            <form
                                method="POST"
                                action="{{ route('recommendations.vote', [$creator, $recommendation]) }}"
                                class="shrink-0"
                            >
                                @csrf
                                <input type="hidden" name="vote_action" value="remove">
                                <button
                                    type="submit"
                                    @disabled($currentUserVotes === 0)
                                    aria-label="Remove vote from this request"
                                    class="inline-flex size-11 items-center justify-center rounded-xl border transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-45 dark:focus-visible:ring-offset-slate-950 {{ $currentUserVotes > 0 ? 'border-slate-200 bg-white text-slate-600 hover:border-indigo-300 hover:bg-indigo-50 hover:text-indigo-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-indigo-700 dark:hover:bg-indigo-950/50 dark:hover:text-indigo-300' : 'border-slate-200 bg-white text-slate-400 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-600' }}"
                                >
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true">
                                        <path stroke-linecap="round" d="M6 12h12" />
                                    </svg>
                                </button>
                            </form>

                            <div data-current-user-votes="{{ $currentUserVotes }}" class="min-w-0 rounded-xl bg-white px-3 py-2 text-center shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 sm:min-w-14">
                                <p class="whitespace-nowrap text-sm font-extrabold leading-none text-slate-950 dark:text-white">{{ $currentUserVotes }}/{{ $voteLimit }}</p>
                                <p class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">yours</p>
                            </div>

                            <form
                                id="recommendation-vote-{{ $recommendation->id }}"
                                method="POST"
                                action="{{ route('recommendations.vote', [$creator, $recommendation]) }}"
                                class="shrink-0"
                            >
                                @csrf
                                <input type="hidden" name="vote_action" value="add">
                                <button
                                    type="submit"
                                    @disabled(! $canAddVote)
                                    aria-label="Add vote to this request"
                                    class="inline-flex size-11 items-center justify-center rounded-xl border transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-45 dark:focus-visible:ring-offset-slate-950 {{ $canAddVote ? 'border-indigo-500 bg-indigo-600 text-white shadow-lg shadow-indigo-500/20 hover:bg-indigo-500 dark:border-indigo-400 dark:bg-indigo-500' : 'border-slate-200 bg-white text-slate-400 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-600' }}"
                                >
                                    <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true">
                                        <path stroke-linecap="round" d="M12 6v12M6 12h12" />
                                    </svg>
                                </button>
                            </form>
                        </div>
                    @else
                        <div data-vote-controls class="grid w-full max-w-xs grid-cols-[2.75rem_minmax(4.5rem,auto)_2.75rem] items-center justify-center gap-2 sm:flex sm:w-auto sm:max-w-none sm:justify-end">
                            <span class="inline-flex size-11 items-center justify-center rounded-xl border border-slate-200 bg-white text-slate-400 opacity-45 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-600" aria-hidden="true">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25">
                                    <path stroke-linecap="round" d="M6 12h12" />
                                </svg>
                            </span>
                            <div class="min-w-0 rounded-xl bg-white px-3 py-2 text-center shadow-sm ring-1 ring-slate-200 dark:bg-slate-900 dark:ring-slate-800 sm:min-w-14">
                                <p class="whitespace-nowrap text-sm font-extrabold leading-none text-slate-950 dark:text-white">0/{{ $voteLimit ?: 3 }}</p>
                                <p class="mt-1 text-[10px] font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">yours</p>
                            </div>
                            <a
                                href="{{ route('login.required', ['return' => route('creator.queue', $creator, absolute: false).'#recommendation-'.$recommendation->id]) }}"
                                aria-label="Add vote to this request"
                                class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl border border-indigo-500 bg-indigo-600 text-white shadow-lg shadow-indigo-500/20 transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-indigo-400 dark:bg-indigo-500 dark:focus-visible:ring-offset-slate-950"
                            >
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true">
                                    <path stroke-linecap="round" d="M12 6v12M6 12h12" />
                                </svg>
                            </a>
                        </div>
                    @endauth
                </div>
            @elseif ($showVotingControls && $recommendation->isVotingClosed())
                <div class="grid w-full grid-cols-1 gap-3 rounded-2xl border border-slate-200 bg-slate-50 px-3 py-3 shadow-sm dark:border-slate-800 dark:bg-slate-950/70 sm:w-auto sm:min-w-72 sm:grid-cols-[auto_minmax(8rem,1fr)] sm:items-center sm:gap-5">
                    <div class="flex min-h-11 items-center justify-center leading-none sm:justify-start">
                        <p aria-hidden="true" class="text-3xl font-extrabold leading-none text-slate-950 dark:text-white">{{ $totalVotes }}</p>
                        <span class="sr-only">{{ $totalVotes }} total {{ Str::plural('vote', $totalVotes) }}</span>
                    </div>

                    <div class="flex min-w-0 flex-col items-center justify-center px-1 py-1 text-center">
                        <span class="text-sm font-semibold text-slate-600 dark:text-slate-300">Voting closed</span>
                        <x-requests.status-badge :request="$recommendation" class="mt-2" />
                    </div>
                </div>
            @else
                <div class="rounded-2xl border border-slate-200 bg-slate-50 px-4 py-3 text-right leading-tight shadow-sm dark:border-slate-800 dark:bg-slate-950/70">
                    <p class="text-lg font-extrabold leading-none text-slate-950 dark:text-white">{{ $totalVotes }}</p>
                    <p class="mt-1 text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ Str::plural('vote', $totalVotes) }}</p>
                </div>
            @endif
        </div>

        @if ($hasRecommendationAction)
            <div
                role="status"
                data-recommendation-action-feedback
                class="mt-3 flex items-center gap-2 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-700/50 dark:bg-emerald-950/40 dark:text-emerald-200"
            >
                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                </svg>
                <span>{{ $recommendationAction['message'] }}</span>
            </div>
        @endif
    </div>

    @auth
        @if ($canWithdraw)
            <template x-teleport="body">
            <div
                x-show="withdrawOpen"
                x-cloak
                class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
                role="dialog"
                aria-modal="true"
                aria-labelledby="withdraw-title-{{ $recommendation->id }}"
            >
                <div x-show="withdrawOpen" x-transition.opacity class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" x-on:click="withdrawOpen = false"></div>

                <div
                    x-show="withdrawOpen"
                    x-transition
                    class="relative mx-auto mt-10 w-full max-w-md rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900"
                >
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <h3 id="withdraw-title-{{ $recommendation->id }}" class="text-lg font-extrabold text-slate-950 dark:text-white">Withdraw this request?</h3>
                            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                This removes it from the active list and returns any active votes placed on it.
                            </p>
                        </div>
                        <button type="button" x-on:click="withdrawOpen = false" class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:hover:bg-slate-800 dark:hover:text-slate-200" aria-label="Cancel withdrawal">
                            <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                            </svg>
                        </button>
                    </div>

                    <form method="POST" action="{{ route('recommendations.withdraw', [$creator, $recommendation]) }}" class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        @csrf
                        <button type="button" x-on:click="withdrawOpen = false" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-red-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-red-500">
                            Withdraw request
                        </button>
                    </form>
                </div>
            </div>
            </template>
        @endif

        <template x-teleport="body">
        <div
            x-show="alternativeOpen"
            x-cloak
            class="fixed inset-0 z-50 overflow-y-auto px-4 py-6 sm:px-0"
            role="dialog"
            aria-modal="true"
            aria-labelledby="alternative-title-{{ $recommendation->id }}"
        >
            <div x-show="alternativeOpen" x-transition.opacity class="fixed inset-0 bg-slate-950/70 backdrop-blur-sm" x-on:click="alternativeOpen = false"></div>

            <div
                x-show="alternativeOpen"
                x-transition
                class="relative mx-auto mt-10 w-full max-w-lg rounded-2xl border border-slate-200 bg-white p-6 shadow-2xl dark:border-slate-800 dark:bg-slate-900"
            >
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <h3 id="alternative-title-{{ $recommendation->id }}" class="text-lg font-extrabold text-slate-950 dark:text-white">Suggest an alternative</h3>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Know a better version of this request? Share the video link and tell the creator why this version may be a better fit.
                        </p>
                    </div>
                    <button type="button" x-on:click="alternativeOpen = false" class="rounded-xl p-2 text-slate-400 transition hover:bg-slate-100 hover:text-slate-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:hover:bg-slate-800 dark:hover:text-slate-200" aria-label="Close alternative request form">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                        </svg>
                    </button>
                </div>

                <form method="POST" action="{{ route('recommendations.alternatives.store', [$creator, $recommendation]) }}" class="mt-6 space-y-5">
                    @csrf
                    <input type="hidden" name="alternative_recommendation_id" value="{{ $recommendation->id }}">

                    <div>
                        <label for="alternative-url-{{ $recommendation->id }}" class="text-sm font-bold text-slate-700 dark:text-slate-200">Alternative video URL</label>
                        <input
                            id="alternative-url-{{ $recommendation->id }}"
                            name="alternative_url"
                            type="url"
                            required
                            value="{{ $alternativeErrorsOpen ? old('alternative_url') : '' }}"
                            class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-slate-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                            placeholder="https://www.youtube.com/watch?v=..."
                        >
                        @if ($alternativeErrorsOpen)
                            @error('alternative_url')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-3">
                            <label for="alternative-reason-{{ $recommendation->id }}" class="text-sm font-bold text-slate-700 dark:text-slate-200">Why this version is better</label>
                            <span class="text-xs font-medium text-slate-500 dark:text-slate-400">500 max</span>
                        </div>
                        <textarea
                            id="alternative-reason-{{ $recommendation->id }}"
                            name="reason"
                            rows="4"
                            maxlength="500"
                            required
                            class="mt-2 block w-full rounded-xl border-slate-300 bg-white text-slate-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100"
                        >{{ $alternativeErrorsOpen ? old('reason') : '' }}</textarea>
                        @if ($alternativeErrorsOpen)
                            @error('reason')
                                <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
                            @enderror
                        @endif
                    </div>

                    <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
                        <button type="button" x-on:click="alternativeOpen = false" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:bg-slate-800">
                            Cancel
                        </button>
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white transition hover:bg-indigo-500">
                            Submit alternative
                        </button>
                    </div>
                </form>
            </div>
        </div>
        </template>
    @endauth
</article>
