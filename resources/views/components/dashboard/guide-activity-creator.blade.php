@props([
    'creator',
    'activeVotes',
    'suggestions',
    'defaultOpen' => false,
])

@php
    $activeVoteCount = (int) ($creator->active_vote_count ?? $activeVotes->sum('vote_count'));
    $suggestionCount = (int) ($creator->suggestion_count ?? $suggestions->count());
    $publishedCount = (int) ($creator->published_count ?? $suggestions->where('status', 'published')->count());
    $detailsId = 'guide-activity-creator-'.$creator->id;
@endphp

<article
    x-data="{ open: @js($defaultOpen) }"
    class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition hover:border-emerald-300 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-emerald-700"
    x-bind:class="open ? 'border-emerald-400 ring-1 ring-emerald-300/60 dark:border-emerald-500 dark:ring-emerald-500/30' : ''"
>
    <button
        type="button"
        x-on:click="open = ! open"
        aria-expanded="false"
        x-bind:aria-expanded="open.toString()"
        aria-controls="{{ $detailsId }}"
        class="flex min-h-20 w-full min-w-0 items-center gap-3 px-4 py-3 text-left transition hover:bg-emerald-50/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-emerald-500 dark:hover:bg-emerald-950/20 sm:gap-4 sm:px-5"
    >
        <x-creator-avatar :creator="$creator" />

        <span class="min-w-0 flex-1">
            <span class="block truncate text-base font-bold text-slate-950 dark:text-white">{{ $creator->display_name }}</span>
            @if ($creator->slug)
                <span class="mt-0.5 block truncate text-xs font-medium text-slate-500 dark:text-slate-400">{{ '@'.$creator->slug }}</span>
            @endif
            <span class="mt-1 block text-sm text-slate-600 dark:text-slate-300">
                {{ $activeVoteCount }} active {{ Str::plural('vote', $activeVoteCount) }}
                <span aria-hidden="true">&middot;</span>
                {{ $suggestionCount }} {{ Str::plural('request', $suggestionCount) }}
                <span aria-hidden="true">&middot;</span>
                {{ $publishedCount }} published
            </span>
        </span>

        <svg class="size-5 shrink-0 text-slate-400 transition-transform duration-200" x-bind:class="{ 'rotate-180 text-emerald-600 dark:text-emerald-300': open }" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
            <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
        </svg>
    </button>

    <div
        id="{{ $detailsId }}"
        x-show="open"
        x-cloak
        x-transition
        class="border-t border-slate-200 bg-slate-50/70 px-4 py-4 dark:border-slate-800 dark:bg-slate-950/40 sm:px-5"
    >
        <div class="grid gap-5 lg:grid-cols-2">
            <section aria-labelledby="{{ $detailsId }}-votes">
                <x-subsection-label as="h3" id="{{ $detailsId }}-votes">Your active votes</x-subsection-label>

                @if ($activeVotes->isEmpty())
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">No active votes with this creator.</p>
                @else
                    <ul class="mt-2 divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($activeVotes as $vote)
                            <li class="flex min-w-0 items-center justify-between gap-3 py-2.5">
                                <a href="{{ route('creator.queue', $creator).'#recommendation-'.$vote->recommendation_id }}" class="min-w-0 break-words text-sm font-semibold text-indigo-700 hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200">
                                    {{ $vote->recommendation->displayTitle() }}
                                </a>
                                <span class="shrink-0 text-sm font-bold text-slate-700 dark:text-slate-200">{{ $vote->vote_count }} {{ Str::plural('vote', $vote->vote_count) }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>

            <section aria-labelledby="{{ $detailsId }}-suggestions">
                <x-subsection-label as="h3" id="{{ $detailsId }}-suggestions">Your requests</x-subsection-label>

                @if ($suggestions->isEmpty())
                    <p class="mt-3 text-sm text-slate-500 dark:text-slate-400">No requests submitted to this creator.</p>
                @else
                    <ul class="mt-2 divide-y divide-slate-200 dark:divide-slate-800">
                        @foreach ($suggestions as $suggestion)
                            @php
                                $suggestionUrl = match (true) {
                                    $suggestion->status === 'published' => route('creators.published', $creator).'#recommendation-'.$suggestion->id,
                                    in_array($suggestion->status, \App\Models\Recommendation::ACTIVE_PUBLIC_STATUSES, true) => route('creator.queue', $creator).'#recommendation-'.$suggestion->id,
                                    in_array($suggestion->status, \App\Models\Recommendation::CLOSED_PUBLIC_STATUSES, true) => route('creators.closed', $creator).'#recommendation-'.$suggestion->id,
                                    default => null,
                                };
                            @endphp
                            <li class="flex min-w-0 items-center justify-between gap-3 py-2.5">
                                <span class="min-w-0">
                                    @if ($suggestionUrl)
                                        <a href="{{ $suggestionUrl }}" class="break-words text-sm font-semibold text-indigo-700 hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200">{{ $suggestion->displayTitle() }}</a>
                                    @else
                                        <span class="block break-words text-sm font-semibold text-slate-800 dark:text-slate-100">{{ $suggestion->displayTitle() }}</span>
                                    @endif
                                    <x-requests.requested-by-you-badge :recommendation="$suggestion" class="mt-1" />
                                    <span class="mt-0.5 block text-xs text-slate-500 dark:text-slate-400">
                                        {{ $suggestion->mediaTypeLabel() }}
                                        <span aria-hidden="true">&middot;</span>
                                        {{ $suggestion->created_at->format('M j, Y') }}
                                        <span aria-hidden="true">&middot;</span>
                                        {{ $suggestion->totalVotes() }} community {{ Str::plural('vote', $suggestion->totalVotes()) }}
                                    </span>
                                </span>
                                <span class="shrink-0 text-right"><span class="rounded-full px-2.5 py-1 text-xs font-bold {{ $suggestion->statusBadgeClass() }}">{{ $suggestion->statusLabel() }}</span><x-requests.edit-own-request-action :recommendation="$suggestion" compact class="mt-1 min-h-0 text-xs" /></span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </section>
        </div>

        <div class="mt-4 border-t border-slate-200 pt-4 dark:border-slate-800">
            <a href="{{ route('creator.queue', $creator) }}" class="inline-flex min-h-10 items-center text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">Open creator page</a>
        </div>
    </div>
</article>
