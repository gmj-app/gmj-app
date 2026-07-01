@props([
    'recommendation',
    'creator',
    'usage' => null,
    'topRequested' => false,
])

@php
    $recommendationAction = session('recommendation_action');
    $hasRecommendationAction = is_array($recommendationAction)
        && (int) ($recommendationAction['recommendation_id'] ?? 0) === $recommendation->id;
@endphp

<article id="recommendation-{{ $recommendation->id }}" class="group min-w-0 scroll-mt-24 overflow-hidden rounded-3xl border bg-white shadow-sm transition duration-200 dark:bg-slate-900 md:hover:-translate-y-0.5 md:hover:shadow-xl {{ $hasRecommendationAction ? 'border-indigo-300 ring-1 ring-indigo-400/40 dark:border-indigo-700 dark:ring-indigo-500/40' : 'border-slate-200 dark:border-slate-800 md:hover:border-indigo-200 dark:md:hover:border-indigo-800' }}">
    @if ($recommendation->youtubeThumbnailUrl())
        <a
            href="{{ $recommendation->youtube_url }}"
            target="_blank"
            rel="noopener noreferrer"
            class="relative block aspect-video overflow-hidden bg-slate-950"
            aria-label="Watch {{ $recommendation->title }} on YouTube"
        >
            <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-slate-800 to-slate-950">
                <div class="text-center">
                    <span class="mx-auto flex h-16 w-20 items-center justify-center rounded-2xl bg-slate-700 text-white shadow-lg">
                        <svg viewBox="0 0 24 24" aria-hidden="true" class="h-8 w-8 fill-current"><path d="M8 5v14l11-7z"/></svg>
                    </span>
                    <p class="mt-3 text-sm font-bold text-slate-300">Video preview unavailable</p>
                </div>
            </div>
            <img
                src="{{ $recommendation->youtubeThumbnailUrl() }}"
                alt="Thumbnail for {{ $recommendation->title }}"
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
    @else
        <div class="flex aspect-video items-center justify-center bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-950">
            <div class="text-center">
                <span class="mx-auto flex h-16 w-20 items-center justify-center rounded-2xl bg-slate-700 text-white shadow-lg dark:bg-slate-600">
                    <svg viewBox="0 0 24 24" aria-hidden="true" class="h-8 w-8 fill-current"><path d="M8 5v14l11-7z"/></svg>
                </span>
                <p class="mt-3 text-sm font-bold text-slate-500 dark:text-slate-400">
                    {{ $recommendation->recommendation_type === 'topic' ? 'Community topic' : 'Video preview unavailable' }}
                </p>
            </div>
        </div>
    @endif

    <div class="p-5 sm:p-6">
        <div class="flex flex-wrap items-center gap-2">
            @if ($topRequested)
                <span class="rounded-full bg-fuchsia-100 px-3 py-1.5 text-sm font-bold text-fuchsia-800 dark:bg-fuchsia-950 dark:text-fuchsia-300">Top requested</span>
            @endif
            @if ($recommendation->is_pinned)
                <span class="rounded-full bg-amber-100 px-3 py-1.5 text-sm font-bold text-amber-800 dark:bg-amber-950 dark:text-amber-300">Pinned</span>
            @endif
            @if ($recommendation->isCreatorAdded())
                <span class="rounded-full bg-violet-100 px-3 py-1.5 text-sm font-bold text-violet-700 dark:bg-violet-950 dark:text-violet-300">Added by creator</span>
            @endif
            <span class="rounded-full px-3 py-1.5 text-sm font-bold {{ $recommendation->status === 'already_seen' ? 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-300' : 'bg-indigo-100 text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' }}">{{ $recommendation->statusLabel() }}</span>
        </div>

        <div class="mt-3 flex flex-wrap items-center gap-2">
            <span class="rounded-full bg-slate-100 px-3 py-1.5 text-sm font-bold text-slate-600 dark:bg-slate-800 dark:text-slate-300">
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
                    <span class="text-xs font-bold text-slate-500 dark:text-slate-400">+{{ $recommendation->creatorTags->count() - 3 }} more</span>
                @endif
            </div>
        @endif

        <h3 class="mt-5 break-words text-xl font-extrabold leading-7 text-slate-950 dark:text-white sm:text-2xl">{{ $recommendation->title }}</h3>

        @if ($recommendation->channel_title)
            <p class="mt-2 text-base font-semibold text-slate-600 dark:text-slate-300">from {{ $recommendation->channel_title }}</p>
        @elseif ($recommendation->artist)
            <p class="mt-2 text-base font-semibold text-slate-600 dark:text-slate-300">by {{ $recommendation->artist }}</p>
        @endif

        <div class="mt-3 flex flex-wrap gap-x-3 gap-y-1 text-sm text-slate-500 dark:text-slate-400">
            @if ($recommendation->isCreatorAdded())
                <span>Added by creator</span>
            @elseif ($recommendation->submittedBy)
                <span>Submitted by {{ $recommendation->submittedBy->name }}</span>
            @endif
            <span>Submitted {{ $recommendation->created_at->format('M j, Y') }}</span>
        </div>

        @if ($recommendation->recommendation_type === 'topic' && $recommendation->description)
            <p class="mt-4 text-base leading-7 text-slate-600 dark:text-slate-300">
                {{ Str::limit($recommendation->description, 160) }}
            </p>
        @elseif ($recommendation->reason)
            <p class="mt-4 text-base leading-7 text-slate-600 dark:text-slate-300">
                {{ Str::limit($recommendation->reason, 160) }}
            </p>
        @elseif ($recommendation->description)
            <p class="mt-4 text-base leading-7 text-slate-600 dark:text-slate-300">
                {{ Str::limit($recommendation->description, 160) }}
            </p>
        @endif

        @if ($recommendation->status === 'scheduled' && $recommendation->scheduled_for)
            <p class="mt-4 rounded-xl bg-indigo-50 px-4 py-3 text-sm font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">
                Scheduled for {{ $recommendation->scheduled_for->format('M j, Y \a\t g:i A') }}
            </p>
        @endif

        @if ($recommendation->status === 'already_seen')
            <p class="mt-4 rounded-xl bg-sky-50 px-4 py-3 text-sm font-semibold text-sky-700 dark:bg-sky-950/60 dark:text-sky-300">
                The creator has already seen this.
            </p>
        @endif

        <div class="mt-5 flex flex-wrap items-center gap-x-5 gap-y-3 border-t border-slate-100 pt-5 text-base dark:border-slate-800">
            @if ($recommendation->youtube_url)
                <a href="{{ $recommendation->youtube_url }}" target="_blank" rel="noopener noreferrer" class="font-bold text-red-600 hover:text-red-500">
                    {{ $recommendation->youtube_video_id ? 'Watch original' : 'Open original link' }}
                </a>
            @endif

            @if ($recommendation->status === 'published' && $recommendation->published_reaction_url)
                <a href="{{ $recommendation->published_reaction_url }}" target="_blank" rel="noopener noreferrer" class="font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                    Watch published content
                </a>
            @endif

            @if (! $recommendation->youtube_url)
                <span class="font-bold text-slate-500">Topic suggestion</span>
            @endif
        </div>

        <div class="mt-5 flex flex-col items-stretch gap-4 rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-950/70 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-3">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-xl border {{ $recommendation->consumesUpvotes() && ($recommendation->picked_by_user ?? false) ? 'border-indigo-200 bg-indigo-100 text-indigo-700 dark:border-indigo-700 dark:bg-indigo-950 dark:text-indigo-300' : 'border-slate-200 bg-white text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300' }}">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0-6 6m6-6 6 6" />
                    </svg>
                </span>
                <div>
                    <p class="text-3xl font-extrabold leading-none text-slate-950 dark:text-white">{{ $recommendation->user_picks_count }}</p>
                    <p class="mt-1 text-sm font-bold text-slate-500 dark:text-slate-400">{{ Str::plural('upvote', $recommendation->user_picks_count) }}</p>
                </div>
            </div>

            @if ($recommendation->consumesUpvotes())
                @auth
                    <form
                        id="recommendation-vote-{{ $recommendation->id }}"
                        method="POST"
                        action="{{ route('recommendations.vote', [$creator, $recommendation]) }}"
                        class="w-full sm:w-auto"
                    >
                        @csrf
                        <input type="hidden" name="vote_action" value="{{ $recommendation->picked_by_user ? 'remove' : 'add' }}">
                        <button
                            type="submit"
                            aria-label="{{ $recommendation->picked_by_user ? 'Remove your upvote from this recommendation' : 'Upvote this recommendation' }}"
                            class="min-h-12 w-full rounded-full px-5 py-3 text-base font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950 sm:w-auto {{ $recommendation->picked_by_user ? 'bg-indigo-600 text-white hover:bg-indigo-500' : 'border border-indigo-200 bg-white text-indigo-700 hover:bg-indigo-50 dark:border-indigo-800 dark:bg-slate-900 dark:text-indigo-300' }}"
                        >
                            {{ $recommendation->picked_by_user ? 'Remove upvote' : 'Upvote' }}
                        </button>
                    </form>
                @else
                    <a
                        href="{{ route('login.required', ['return' => route('creator.queue', $creator, absolute: false).'#recommendation-'.$recommendation->id]) }}"
                        class="inline-flex min-h-12 w-full items-center justify-center rounded-full border border-indigo-200 bg-white px-5 py-3 text-base font-bold text-indigo-700 dark:border-indigo-800 dark:bg-slate-900 dark:text-indigo-300 sm:w-auto"
                    >
                        Log in to upvote
                    </a>
                @endauth
            @else
                <span class="inline-flex min-h-12 w-full items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-3 text-center text-sm font-bold text-slate-500 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-400 sm:w-auto">
                    No longer accepting upvotes
                </span>
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
</article>
