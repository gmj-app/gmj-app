<x-public-layout :title="$creator->display_name.' | '.config('app.name', 'Guide My Journey')">
    <section class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        <div class="mx-auto min-w-0 max-w-5xl">
            <div
                x-data="{ creatorMenuOpen: false, biographyOpen: false, submissionGuidanceOpen: false }"
                x-on:keydown.escape.window="biographyOpen || submissionGuidanceOpen ? (biographyOpen = false, submissionGuidanceOpen = false) : creatorMenuOpen = false"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                @php
                    $addRecommendationLabel = 'Add Recommendation';

                    if (auth()->check() && $isFavorited && $creator->submissions_open) {
                        $addRecommendationLabel .= " ({$usage['suggestions_remaining']}/{$usage['suggestions_limit']})";
                    }
                @endphp

                <x-creator-hero-background :creator="$creator" class="min-h-48 sm:min-h-40 lg:min-h-44">
                    <div class="absolute right-3 top-3 z-20 sm:right-4 sm:top-4">
                        <div class="relative" x-on:click.outside="creatorMenuOpen = false">
                            <button
                                type="button"
                                x-on:click="creatorMenuOpen = ! creatorMenuOpen"
                                aria-label="Open creator actions"
                                aria-haspopup="menu"
                                aria-expanded="false"
                                x-bind:aria-expanded="creatorMenuOpen.toString()"
                                class="inline-flex size-10 items-center justify-center rounded-full bg-black/45 text-white shadow-lg ring-1 ring-white/20 transition hover:bg-black/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80"
                            >
                                <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true">
                                    <circle cx="5" cy="12" r="1.8" />
                                    <circle cx="12" cy="12" r="1.8" />
                                    <circle cx="19" cy="12" r="1.8" />
                                </svg>
                            </button>

                            <div
                                x-show="creatorMenuOpen"
                                x-cloak
                                x-transition:enter="transition ease-out duration-150"
                                x-transition:enter-start="opacity-0 scale-95"
                                x-transition:enter-end="opacity-100 scale-100"
                                x-transition:leave="transition ease-in duration-100"
                                x-transition:leave-start="opacity-100 scale-100"
                                x-transition:leave-end="opacity-0 scale-95"
                                role="menu"
                                class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-white/10 bg-slate-950/95 py-1.5 text-sm font-semibold text-white shadow-2xl backdrop-blur"
                            >
                                <button
                                    type="button"
                                    role="menuitem"
                                    x-on:click="biographyOpen = true; creatorMenuOpen = false"
                                    class="flex w-full items-center gap-3 px-3.5 py-2.5 text-left hover:bg-white/10 focus:bg-white/10 focus:outline-none"
                                >
                                    <svg class="size-5 shrink-0 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <circle cx="12" cy="12" r="9" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M12 10.75v5.5" />
                                        <path stroke-linecap="round" d="M12 7.75h.01" />
                                    </svg>
                                    Biography
                                </button>

                                <button
                                    type="button"
                                    role="menuitem"
                                    x-on:click="submissionGuidanceOpen = true; creatorMenuOpen = false"
                                    class="flex w-full items-center gap-3 px-3.5 py-2.5 text-left hover:bg-white/10 focus:bg-white/10 focus:outline-none"
                                >
                                    <svg class="size-5 shrink-0 text-slate-300" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M5 5.75A1.75 1.75 0 0 1 6.75 4h10.5A1.75 1.75 0 0 1 19 5.75v12.5A1.75 1.75 0 0 1 17.25 20H6.75A1.75 1.75 0 0 1 5 18.25V5.75Z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" d="M8.5 8.5h7M8.5 12h7M8.5 15.5h4" />
                                    </svg>
                                    Submission guidance
                                </button>
                            </div>
                        </div>
                    </div>

                    <div class="relative z-10 flex min-h-48 flex-col justify-center gap-4 px-4 pb-4 pt-14 sm:min-h-40 sm:px-5 sm:py-5 sm:pr-16 lg:min-h-44 lg:flex-row lg:items-center lg:justify-between lg:gap-6 lg:px-6">
                        <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
                            <x-creator-avatar
                                :creator="$creator"
                                size="xl"
                                class="size-16 shrink-0 border-2 border-white/50 shadow-xl ring-4 ring-slate-950/25 sm:size-20 sm:text-2xl lg:text-3xl"
                            />

                            <div class="min-w-0 flex-1">
                                <h1 class="max-w-3xl break-words text-2xl font-extrabold leading-tight tracking-tight text-white drop-shadow-sm sm:text-3xl">{{ $creator->display_name }}</h1>

                                <div class="mt-3 flex flex-wrap gap-2">
                                    @auth
                                        <a
                                            href="{{ route('recommendations.create', $creator) }}"
                                            aria-label="Add a recommendation for {{ $creator->display_name }}"
                                            class="inline-flex min-h-10 items-center justify-center rounded-full bg-indigo-600 px-4 py-2 text-center text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-500 {{ $usage['can_suggest'] && $creator->submissions_open ? '' : 'pointer-events-none bg-slate-400 shadow-none' }}"
                                        >
                                            @if (! $creator->submissions_open)
                                                Recommendations closed
                                            @else
                                                {{ $addRecommendationLabel }}
                                            @endif
                                        </a>
                                    @else
                                        <a
                                            href="{{ route('recommendations.create', $creator) }}"
                                            aria-label="Add a recommendation for {{ $creator->display_name }}"
                                            class="inline-flex min-h-10 items-center justify-center rounded-full bg-indigo-600 px-4 py-2 text-center text-sm font-semibold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-500"
                                        >
                                            Add Recommendation
                                        </a>
                                    @endauth

                                    @if ($creator->youtube_channel_url ?? $creator->channel_url)
                                        <a
                                            href="{{ $creator->youtube_channel_url ?? $creator->channel_url }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            aria-label="Visit {{ $creator->display_name }}'s YouTube channel"
                                            class="inline-flex min-h-10 items-center justify-center rounded-full border border-white/30 bg-white/95 px-4 py-2 text-center text-sm font-medium text-slate-800 shadow-sm hover:bg-white hover:text-red-600 dark:border-white/15 dark:bg-slate-950/80 dark:text-slate-100 dark:hover:text-red-300"
                                        >
                                            Visit Channel
                                        </a>
                                    @endif

                                    @if ($ownsCreator)
                                        <a
                                            href="{{ route('creators.dashboard', $creator) }}"
                                            aria-label="Open settings for {{ $creator->display_name }}"
                                            class="inline-flex min-h-10 items-center justify-center gap-2 rounded-full border border-white/30 bg-white/95 px-4 py-2 text-center text-sm font-medium text-slate-800 shadow-sm transition hover:bg-white hover:text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80 dark:border-white/15 dark:bg-slate-950/80 dark:text-slate-100 dark:hover:text-indigo-300"
                                        >
                                            <x-icons.cog-6-tooth class="size-5 shrink-0" />
                                            Settings
                                        </a>
                                    @endif

                                    @if (! $ownsCreator)
                                        @auth
                                            <form
                                                id="creator-favorite-toggle"
                                                method="POST"
                                                action="{{ route('creator.favorite', $creator) }}"
                                                class="sm:w-auto"
                                                @if ($isFavorited && $usage['votes_used'] > 0)
                                                    x-on:submit="
                                                        if ($el.dataset.participationConfirmed === '1') return;
                                                        $event.preventDefault();
                                                        $dispatch('request-participation-confirmation', {
                                                            formId: $el.id,
                                                            mode: 'confirm',
                                                            title: 'Remove favorite?',
                                                            body: @js("Unfavoriting removes your active votes from this creator. Suggestions with no other votes may be removed."),
                                                            resourceLine: @js("Active votes on this creator: {$usage['votes_used']}"),
                                                            confirmLabel: 'Remove favorite and active votes',
                                                            destructive: true,
                                                        });
                                                    "
                                                @endif
                                            >
                                                @csrf
                                                <button
                                                    type="submit"
                                                    class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-full border px-4 py-2 text-sm font-medium shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80 sm:w-auto {{ $isFavorited ? 'border-amber-300 bg-amber-100 text-amber-800 hover:bg-amber-200 dark:border-amber-500/50 dark:bg-amber-500/15 dark:text-amber-300 dark:hover:bg-amber-500/25' : 'border-white/30 bg-white/95 text-slate-800 hover:bg-white hover:text-amber-700 dark:border-white/15 dark:bg-slate-950/80 dark:text-slate-100 dark:hover:text-amber-300' }}"
                                                >
                                                    <svg class="size-5 {{ $isFavorited ? 'fill-current' : 'fill-none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="m12 3.75 2.475 5.016 5.535.804-4.005 3.904.946 5.512L12 16.383l-4.951 2.603.946-5.512L3.99 9.57l5.535-.804L12 3.75Z" />
                                                    </svg>
                                                    {{ $isFavorited ? 'Favorited' : 'Favorite' }}
                                                </button>
                                            </form>
                                        @else
                                            <a href="{{ route('login.required', ['return' => route('creator.queue', $creator, absolute: false)]) }}" class="inline-flex min-h-10 w-full items-center justify-center gap-2 rounded-full border border-white/30 bg-white/95 px-4 py-2 text-sm font-medium text-slate-800 shadow-sm transition hover:bg-white hover:text-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/80 dark:border-white/15 dark:bg-slate-950/80 dark:text-slate-100 dark:hover:text-amber-300 sm:w-auto">
                                                <svg class="size-5 fill-none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m12 3.75 2.475 5.016 5.535.804-4.005 3.904.946 5.512L12 16.383l-4.951 2.603.946-5.512L3.99 9.57l5.535-.804L12 3.75Z" />
                                                </svg>
                                                Favorite
                                            </a>
                                        @endauth
                                    @endif
                                </div>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 text-xs font-medium text-white/90 lg:max-w-xs lg:shrink-0 lg:justify-end">
                            <span class="rounded-full border border-white/20 bg-white/15 px-3 py-1.5 backdrop-blur-sm">{{ $publicRecommendationsCount }} {{ $publicRecommendationsCount === 1 ? 'recommendation' : 'recommendations' }}</span>
                            <span class="rounded-full border border-white/20 bg-white/15 px-3 py-1.5 backdrop-blur-sm">{{ $favoritesCount }} {{ $favoritesCount === 1 ? 'follower' : 'followers' }}</span>
                            <span class="rounded-full border border-white/20 bg-white/15 px-3 py-1.5 backdrop-blur-sm">{{ $publicVotesCount }} {{ Str::plural('vote', $publicVotesCount) }}</span>
                        </div>
                    </div>
                </x-creator-hero-background>

                <div
                    x-show="biographyOpen"
                    x-cloak
                    x-transition.opacity
                    class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/70 px-3 py-8 sm:px-6"
                >
                    <button
                        type="button"
                        class="fixed inset-0 cursor-default"
                        aria-label="Close biography"
                        x-on:click="biographyOpen = false"
                    ></button>

                    <div
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="creator-biography-title"
                        class="relative z-10 w-full max-w-2xl overflow-hidden rounded-2xl bg-[#212121] text-white shadow-2xl ring-1 ring-white/10"
                        x-on:click.stop
                    >
                        <div class="sticky top-0 z-10 flex items-center justify-between gap-4 bg-[#212121] px-6 py-5">
                            <h2 id="creator-biography-title" class="text-xl font-semibold tracking-tight sm:text-2xl">{{ $creator->display_name }}</h2>
                            <button
                                type="button"
                                x-on:click="biographyOpen = false"
                                aria-label="Close biography"
                                class="inline-flex size-10 shrink-0 items-center justify-center rounded-full text-slate-200 transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                            >
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                                </svg>
                            </button>
                        </div>

                        <div class="max-h-[calc(100vh-9rem)] overflow-y-auto px-6 pb-6">
                            <section>
                                <h3 class="text-lg font-semibold">Description</h3>
                                <div class="mt-3 space-y-4 whitespace-pre-line break-words text-sm font-medium leading-6 text-slate-100 [overflow-wrap:anywhere] sm:text-base sm:leading-7"><x-linkified-text :text="filled($creator->bio) ? $creator->bio : 'No biography has been added for this creator yet.'" /></div>
                            </section>

                            <section class="mt-7">
                                <h3 class="text-lg font-semibold">More info</h3>
                                <div class="mt-4 space-y-4 text-sm font-medium text-slate-100 sm:text-base">
                                    @if ($creator->youtube_channel_url ?? $creator->channel_url)
                                        <a
                                            href="{{ $creator->youtube_channel_url ?? $creator->channel_url }}"
                                            target="_blank"
                                            rel="noopener noreferrer"
                                            class="flex items-center gap-4 rounded-xl py-1 transition hover:text-white"
                                        >
                                            <svg class="size-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <rect x="3" y="6" width="18" height="12" rx="3" />
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m10 9 5 3-5 3V9Z" />
                                            </svg>
                                            <span class="break-all">{{ $creator->youtube_channel_url ?? $creator->channel_url }}</span>
                                        </a>
                                    @endif

                                    <div class="flex items-center gap-4">
                                        <svg class="size-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5V5.75A1.75 1.75 0 0 1 5.75 4h12.5A1.75 1.75 0 0 1 20 5.75V19.5l-4-2-4 2-4-2-4 2Z" />
                                        </svg>
                                        <span>{{ $publicRecommendationsCount }} {{ $publicRecommendationsCount === 1 ? 'recommendation' : 'recommendations' }}</span>
                                    </div>

                                    <div class="flex items-center gap-4">
                                        <svg class="size-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M16 19v-1.5A3.5 3.5 0 0 0 12.5 14h-5A3.5 3.5 0 0 0 4 17.5V19" />
                                            <circle cx="10" cy="7.5" r="3.5" />
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M18 8v5M20.5 10.5h-5" />
                                        </svg>
                                        <span>{{ $favoritesCount }} {{ $favoritesCount === 1 ? 'follower' : 'followers' }}</span>
                                    </div>

                                    <div class="flex items-center gap-4">
                                        <svg class="size-6 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m7 11 5-7 5 7h-3v8h-4v-8H7Z" />
                                        </svg>
                                        <span>{{ $publicVotesCount }} {{ Str::plural('vote', $publicVotesCount) }}</span>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>

                <div
                    x-show="submissionGuidanceOpen"
                    x-cloak
                    x-transition.opacity
                    class="fixed inset-0 z-50 flex items-start justify-center overflow-y-auto bg-black/70 px-3 py-8 sm:px-6"
                >
                    <button
                        type="button"
                        class="fixed inset-0 cursor-default"
                        aria-label="Close submission guidance"
                        x-on:click="submissionGuidanceOpen = false"
                    ></button>

                    <div
                        role="dialog"
                        aria-modal="true"
                        aria-labelledby="creator-submission-guidance-title"
                        class="relative z-10 w-full max-w-2xl overflow-hidden rounded-2xl bg-[#212121] text-white shadow-2xl ring-1 ring-white/10"
                        x-on:click.stop
                    >
                        <div class="sticky top-0 z-10 flex items-center justify-between gap-4 bg-[#212121] px-6 py-5">
                            <div class="flex min-w-0 items-center gap-3">
                                <x-creator-avatar
                                    :creator="$creator"
                                    size="md"
                                    class="size-11 rounded-xl ring-1 ring-white/10"
                                />

                                <div class="min-w-0">
                                    <h2 id="creator-submission-guidance-title" class="text-xl font-semibold tracking-tight sm:text-2xl">Submission guidance</h2>
                                    <p class="mt-0.5 truncate text-sm font-medium text-slate-400">A note from {{ $creator->display_name }}</p>
                                </div>
                            </div>

                            <button
                                type="button"
                                x-on:click="submissionGuidanceOpen = false"
                                aria-label="Close submission guidance"
                                class="inline-flex size-10 shrink-0 items-center justify-center rounded-full text-slate-200 transition hover:bg-white/10 focus:outline-none focus-visible:ring-2 focus-visible:ring-white/70"
                            >
                                <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 6l12 12M18 6 6 18" />
                                </svg>
                            </button>
                        </div>

                        <div class="max-h-[calc(100vh-9rem)] overflow-y-auto px-6 pb-6">
                            @if (filled($creator->submission_instructions))
                                <blockquote class="relative rounded-2xl border border-white/10 bg-white/[0.04] px-5 py-6 sm:px-6">
                                    <span class="pointer-events-none absolute left-4 top-2 bg-gradient-to-br from-indigo-300/60 to-violet-300/20 bg-clip-text text-7xl font-black leading-none text-transparent" aria-hidden="true">“</span>
                                    <div class="relative whitespace-pre-line pl-5 text-sm font-medium leading-6 text-slate-100 sm:text-base sm:leading-7">{{ $creator->submission_instructions }}</div>
                                    <span class="pointer-events-none absolute bottom-1 right-5 bg-gradient-to-br from-violet-300/35 to-indigo-300/10 bg-clip-text text-6xl font-black leading-none text-transparent" aria-hidden="true">”</span>
                                </blockquote>
                            @else
                                <p class="rounded-2xl border border-white/10 bg-white/[0.04] px-5 py-4 text-sm font-medium leading-6 text-slate-300 sm:text-base">
                                    This creator has not added submission guidance yet.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </section>

    <section class="px-4 pb-10 sm:px-6 sm:pb-14 lg:px-8">
        <div class="mx-auto min-w-0 max-w-5xl">
            @if (session('success'))
                <div
                    data-global-success-alert
                    class="mb-5 rounded-2xl border border-emerald-200 bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 dark:border-emerald-900 dark:bg-emerald-950 dark:text-emerald-200"
                >
                    {{ session('success') }}
                </div>
            @endif

            @error('limit')
                <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200">
                    <p>{{ $message }}</p>
                    @if (str_contains((string) $message, 'used all your votes for this creator'))
                        <p class="mt-1 text-xs font-medium leading-5 text-red-600 dark:text-red-300">
                            You’ll get votes back when recommendations you supported are published or closed.
                        </p>
                    @endif
                </div>
            @enderror

            @error('favorite')
                <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200">{{ $message }}</div>
            @enderror

            <div class="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,1fr)_17rem] lg:items-start">
                <aside class="min-w-0 lg:col-start-2 lg:row-start-1">
                    <div class="space-y-5 lg:sticky lg:top-24">
                        <div class="w-full min-w-0 overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                            @auth
                            <div x-data="{ open: false }" class="min-w-0">
                                <button
                                    type="button"
                                    x-on:click="open = ! open"
                                    aria-expanded="false"
                                    x-bind:aria-expanded="open.toString()"
                                    aria-controls="creator-resource-details"
                                    class="flex w-full min-w-0 items-start gap-3 px-4 py-4 text-left transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-inset dark:hover:bg-slate-800/60"
                                >
                                    <span class="min-w-0 flex-1">
                                        <span class="block truncate font-semibold text-slate-950 dark:text-white">{{ auth()->user()->publicName() }}</span>
                                        <span class="mt-0.5 block truncate text-xs font-normal text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</span>
                                    </span>

                                    <span class="shrink-0 rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">{{ $usage['tier'] }}</span>

                                    <svg
                                        class="mt-1 size-5 shrink-0 text-slate-400 transition-transform duration-200"
                                        x-bind:class="{ 'rotate-180 text-indigo-500 dark:text-indigo-300': open }"
                                        viewBox="0 0 24 24"
                                        fill="none"
                                        stroke="currentColor"
                                        stroke-width="2"
                                        aria-hidden="true"
                                    >
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                    </svg>
                                </button>

                                <dl class="grid grid-cols-3 gap-2 border-t border-slate-100 px-4 py-3 dark:border-slate-800">
                                    @foreach ([
                                        ['Favorites left', $usage['reactors_remaining'], $usage['reactors_limit']],
                                        ['Suggestions left', $usage['suggestions_remaining'], $usage['suggestions_limit']],
                                        ['Votes left', $usage['votes_remaining'], $usage['votes_limit']],
                                    ] as [$label, $remaining, $limit])
                                        <div class="min-w-0 rounded-xl bg-slate-50 px-2 py-2 text-center dark:bg-slate-950/60">
                                            <dt class="truncate text-[10px] font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                                            <dd class="mt-1 text-sm font-semibold leading-none text-slate-950 dark:text-white">{{ $remaining }}/{{ $limit }}</dd>
                                        </div>
                                    @endforeach
                                </dl>

                                <div
                                    id="creator-resource-details"
                                    x-show="open"
                                    x-cloak
                                    x-transition:enter="transition ease-out duration-200"
                                    x-transition:enter-start="opacity-0 -translate-y-1"
                                    x-transition:enter-end="opacity-100 translate-y-0"
                                    x-transition:leave="transition ease-in duration-150"
                                    x-transition:leave-start="opacity-100 translate-y-0"
                                    x-transition:leave-end="opacity-0 -translate-y-1"
                                    class="border-t border-slate-100 px-4 pb-4 pt-3 dark:border-slate-800"
                                >
                                    <h2 class="text-xs font-semibold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Your limits</h2>
                                    <dl class="mt-3 grid grid-cols-1 gap-2 sm:grid-cols-3 lg:grid-cols-1">
                                        @foreach ([
                                            ['Creator favorites remaining', $usage['reactors_remaining'], $usage['reactors_used'], $usage['reactors_limit']],
                                            ['Suggestions remaining', $usage['suggestions_remaining'], $usage['suggestions_used'], $usage['suggestions_limit']],
                                            ['Votes remaining', $usage['votes_remaining'], $usage['votes_used'], $usage['votes_limit']],
                                        ] as [$label, $remaining, $used, $limit])
                                            <div class="rounded-2xl bg-slate-50 px-3 py-2.5 dark:bg-slate-950/60">
                                                <dt class="text-xs font-semibold leading-4 text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                                                <dd class="mt-1 text-lg font-semibold leading-none text-slate-950 dark:text-white">{{ $remaining }}</dd>
                                                <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">{{ $used }} of {{ $limit }} used</p>
                                            </div>
                                        @endforeach
                                    </dl>

                                    <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-3 text-sm font-medium dark:border-slate-800">
                                        <a href="{{ route('profile.edit') }}" class="inline-flex min-h-10 items-center rounded-xl px-3 text-indigo-600 hover:bg-indigo-50 hover:text-indigo-500 dark:text-indigo-400 dark:hover:bg-indigo-950/60">
                                            Profile
                                        </a>

                                        <form method="POST" action="{{ route('logout') }}">
                                            @csrf
                                            <button type="submit" class="inline-flex min-h-10 items-center rounded-xl px-3 text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white">
                                                Log out
                                            </button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        @else
                            <div class="p-5">
                                <p class="font-semibold text-slate-950 dark:text-white">Join the community</p>
                                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Create a free account to suggest ideas and vote on this journey.</p>
                                <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-1">
                                    <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center justify-center rounded-full bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">Register</a>
                                    <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-full border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-700 hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-700 dark:text-slate-200">Log in</a>
                                </div>
                            </div>
                        @endauth
                        </div>

                        <section class="w-full min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" aria-labelledby="recently-published-title">
                            <h2 id="recently-published-title" class="text-sm font-semibold text-slate-950 dark:text-white">Recently Published</h2>

                            @if ($recentPublishedRecommendations->isEmpty())
                                <p class="mt-4 text-sm leading-6 text-slate-500 dark:text-slate-400">No published recommendations yet.</p>
                            @else
                                <div class="mt-3 divide-y divide-slate-200/80 dark:divide-slate-700/50">
                                    @foreach ($recentPublishedRecommendations as $publishedRecommendation)
                                        @php
                                            $publishedDisplay = $publishedRecommendation->publishedDisplayData();
                                            $publishedDate = $publishedDisplay['date'];
                                            $publishedVotes = $publishedRecommendation->totalVotes();
                                        @endphp
                                        <a
                                            href="{{ route('creators.published', $creator) }}#recommendation-{{ $publishedRecommendation->id }}"
                                            aria-label="View published recommendation: {{ $publishedDisplay['title'] }}"
                                            class="group flex min-w-0 items-start gap-3 rounded-xl px-1 py-4 transition hover:bg-emerald-50/70 focus:outline-none focus-visible:bg-emerald-50/70 focus-visible:ring-2 focus-visible:ring-emerald-500 dark:hover:bg-emerald-950/20 dark:focus-visible:bg-emerald-950/20"
                                        >
                                            <span class="relative flex aspect-video w-[84px] shrink-0 overflow-hidden rounded-lg bg-slate-950 ring-1 ring-slate-200 dark:ring-slate-800">
                                                @if ($publishedDisplay['thumbnail_url'])
                                                    <img
                                                        src="{{ $publishedDisplay['thumbnail_url'] }}"
                                                        alt=""
                                                        loading="lazy"
                                                        onerror="this.hidden = true"
                                                        class="h-full w-full object-cover transition duration-200 group-hover:scale-105 group-hover:opacity-90"
                                                    >
                                                @else
                                                    <span class="flex h-full w-full items-center justify-center bg-gradient-to-br from-slate-800 to-slate-950 text-slate-400">
                                                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                            <path stroke-linecap="round" stroke-linejoin="round" d="M4 19.5V6.75A2.75 2.75 0 0 1 6.75 4h10.5A2.75 2.75 0 0 1 20 6.75V19.5l-4.5-2.25L12 19.5l-3.5-2.25L4 19.5Z" />
                                                        </svg>
                                                    </span>
                                                @endif
                                            </span>
                                            <span class="min-w-0 flex-1">
                                                <span class="line-clamp-2 break-words text-sm font-semibold leading-5 text-slate-800 transition group-hover:text-emerald-700 group-focus-visible:text-emerald-700 dark:text-slate-100 dark:group-hover:text-emerald-300 dark:group-focus-visible:text-emerald-300">{{ $publishedDisplay['title'] }}</span>
                                                <span class="mt-1 block truncate text-[11px] font-medium text-slate-500 dark:text-slate-400">
                                                    {{ $publishedDate?->format('M j, Y') ?? 'Recently' }} &middot; {{ $publishedVotes }} {{ Str::plural('vote', $publishedVotes) }}
                                                </span>
                                            </span>
                                        </a>
                                    @endforeach
                                </div>

                                <div class="mt-4 flex justify-end">
                                    <a href="{{ route('creators.published', $creator) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 transition hover:text-emerald-600 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-emerald-500 dark:text-emerald-300 dark:hover:text-emerald-200">
                                        View all published
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                                        </svg>
                                    </a>
                                </div>
                            @endif
                        </section>
                    </div>
                </aside>

                <div class="min-w-0 space-y-5 lg:col-start-1 lg:row-start-1">
                    @php
                        $activeFilterCount = collect([
                            $filters['q'],
                            $filters['status'],
                            $filters['category'],
                            $filters['tag'],
                        ])->filter(fn ($value) => $value !== '')->count();
                    @endphp

                    <div
                        x-data="{ open: false }"
                        class="rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
                    >
                        <h2 class="sr-only">Filters</h2>
                        <div class="flex items-center justify-end px-4 py-2.5 sm:px-5">
                            <button
                                type="button"
                                x-on:click="open = ! open"
                                aria-expanded="false"
                                x-bind:aria-expanded="open.toString()"
                                x-bind:aria-label="open ? 'Hide filters' : 'Filter suggestions'"
                                aria-controls="creator-queue-filters"
                                class="inline-flex min-h-10 shrink-0 items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-indigo-600 transition hover:bg-indigo-50 hover:text-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:text-indigo-300 dark:hover:bg-indigo-950/60 dark:hover:text-indigo-200 dark:focus-visible:ring-offset-slate-900"
                            >
                                <span x-text="open ? 'Hide filters' : 'Filter suggestions'">Filter suggestions</span>
                                @if ($activeFilterCount > 0)
                                    <span
                                        data-active-filter-count="{{ $activeFilterCount }}"
                                        aria-label="{{ $activeFilterCount }} active {{ Str::plural('filter', $activeFilterCount) }}"
                                        class="inline-flex min-w-6 items-center justify-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-semibold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                                    >
                                        {{ $activeFilterCount }}
                                    </span>
                                @endif
                                <svg
                                    class="size-4 transition-transform duration-200"
                                    x-bind:class="{ 'rotate-180': open }"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                </svg>
                            </button>
                        </div>

                        <div
                            id="creator-queue-filters"
                            x-show="open"
                            x-cloak
                            x-transition:enter="transition ease-out duration-200"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-150"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 -translate-y-1"
                            class="border-t border-slate-200 px-4 pb-5 pt-4 dark:border-slate-800 sm:px-5"
                        >
                            <form method="GET" action="{{ route('creator.queue', $creator) }}" class="grid grid-cols-1 gap-3 md:grid-cols-2 xl:grid-cols-[repeat(14,minmax(0,1fr))] xl:items-end">
                            <div class="md:col-span-2 xl:col-span-4">
                                <label for="queue-search" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Search recommendations</label>
                                <input
                                    id="queue-search"
                                    name="q"
                                    type="search"
                                    value="{{ $filters['q'] }}"
                                    placeholder="Title, artist, channel, or URL"
                                    class="mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-950 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                                >
                            </div>

                            <div class="xl:col-span-2">
                                <label for="queue-status" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Status</label>
                                <select id="queue-status" name="status" class="mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                    <option value="">All statuses</option>
                                    @foreach ($statusOptions as $status => $label)
                                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if ($categoryOptions->isNotEmpty())
                                <div class="xl:col-span-2">
                                    <label for="queue-category" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Category</label>
                                    <select id="queue-category" name="category" class="mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                        <option value="">All categories</option>
                                        @foreach ($categoryOptions as $category)
                                            <option value="{{ $category }}" @selected($filters['category'] === $category)>{{ ucfirst($category) }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            @if ($tagOptions->isNotEmpty())
                                <div class="xl:col-span-2">
                                    <label for="queue-tag" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Tag</label>
                                    <select id="queue-tag" name="tag" class="mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                        <option value="">All tags</option>
                                        @foreach ($tagOptions as $tag)
                                            <option value="{{ $tag->slug }}" @selected($filters['tag'] === $tag->slug)>{{ $tag->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="xl:col-span-2">
                                <label for="queue-sort" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Sort</label>
                                <select id="queue-sort" name="sort" class="mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                    <option value="votes" @selected($filters['sort'] === 'votes')>Most votes</option>
                                    <option value="newest" @selected($filters['sort'] === 'newest')>Newest</option>
                                    <option value="status" @selected($filters['sort'] === 'status')>Status</option>
                                    <option value="scheduled" @selected($filters['sort'] === 'scheduled')>Scheduled date</option>
                                </select>
                            </div>

                            <div class="flex w-full flex-col gap-2 xl:col-span-2 xl:flex-row">
                                <button type="submit" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-semibold text-white hover:bg-indigo-500">
                                    Apply
                                </button>

                                @if ($filters['q'] !== '' || $filters['status'] !== '' || $filters['category'] !== '' || $filters['tag'] !== '' || $filters['sort'] !== 'votes')
                                    <a href="{{ route('creator.queue', $creator) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:text-indigo-600 dark:border-slate-700 dark:text-slate-300">
                                        Clear
                                    </a>
                                @endif
                            </div>
                            </form>
                        </div>
                    </div>

                    @php
                        $recommendationAction = session('recommendation_action');
                        $actionRecommendationId = is_array($recommendationAction)
                            ? (int) ($recommendationAction['recommendation_id'] ?? 0)
                            : null;
                    @endphp

                    @forelse ($recommendations as $recommendation)
                        @php
                            $rank = $loop->iteration;
                            $rankMod100 = $rank % 100;
                            $rankSuffix = in_array($rankMod100, [11, 12, 13], true)
                                ? 'th'
                                : match ($rank % 10) {
                                    1 => 'st',
                                    2 => 'nd',
                                    3 => 'rd',
                                    default => 'th',
                                };
                            $rankLabel = "{$rank}{$rankSuffix}";
                            $rankClasses = match ($rank) {
                                1 => 'border-amber-300 bg-amber-100 text-amber-800 dark:border-amber-500/50 dark:bg-amber-500/15 dark:text-amber-200',
                                2 => 'border-slate-300 bg-slate-100 text-slate-700 dark:border-slate-500/60 dark:bg-slate-500/15 dark:text-slate-200',
                                3 => 'border-orange-300 bg-orange-100 text-orange-800 dark:border-orange-500/50 dark:bg-orange-500/15 dark:text-orange-200',
                                default => 'border-indigo-200 bg-indigo-50 text-indigo-700 dark:border-indigo-500/40 dark:bg-indigo-500/10 dark:text-indigo-200',
                            };
                            $isActionTarget = $actionRecommendationId === $recommendation->id;
                        @endphp

                        <div
                            id="recommendation-{{ $recommendation->id }}"
                            x-data="{ open: @js($isActionTarget) }"
                            x-init="
                                const expandIfHashTarget = () => {
                                    if (window.location.hash === '#recommendation-{{ $recommendation->id }}') {
                                        open = true;
                                        $nextTick(() => $el.scrollIntoView({ block: 'start' }));
                                    }
                                };

                                expandIfHashTarget();
                            "
                            x-on:hashchange.window="
                                if (window.location.hash === '#recommendation-{{ $recommendation->id }}') {
                                    open = true;
                                    $nextTick(() => $el.scrollIntoView({ block: 'start' }));
                                }
                            "
                            class="scroll-mt-28 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:border-emerald-300 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900 dark:hover:border-emerald-700"
                            x-bind:class="open ? 'border-emerald-400 ring-2 ring-emerald-300/70 dark:border-emerald-500 dark:ring-emerald-500/40' : ''"
                        >
                            <button
                                type="button"
                                x-on:click="open = ! open"
                                aria-expanded="false"
                                x-bind:aria-expanded="open.toString()"
                                aria-controls="recommendation-details-{{ $recommendation->id }}"
                                class="group flex min-h-14 w-full min-w-0 cursor-pointer items-center gap-2.5 px-3 py-2 text-left transition hover:bg-emerald-50/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-inset dark:hover:bg-emerald-950/20 sm:min-h-[66px] sm:gap-4 sm:px-5"
                            >
                                <span class="inline-flex h-10 min-w-12 shrink-0 items-center justify-center rounded-xl border px-2.5 text-sm font-semibold sm:h-11 sm:min-w-14 {{ $rankClasses }}">
                                    {{ $rankLabel }}
                                </span>

                                <x-recommendation-compact-media :recommendation="$recommendation" />

                                <span class="min-w-0 flex-1">
                                    <span class="block break-words text-sm font-semibold leading-snug text-slate-800 dark:text-slate-100 sm:text-base">
                                        {{ $recommendation->title }}
                                    </span>
                                    <x-recommendation-user-indicators
                                        :recommendation="$recommendation"
                                        class="mt-1"
                                    />
                                </span>

                                <span class="shrink-0 text-right">
                                    <span class="block text-base font-semibold leading-none text-slate-950 dark:text-white sm:text-lg">{{ $recommendation->totalVotes() }}</span>
                                    <span class="mt-1 block text-[11px] font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ Str::plural('vote', $recommendation->totalVotes()) }}</span>
                                </span>

                                <svg
                                    class="size-5 shrink-0 text-slate-400 transition-transform duration-200"
                                    x-bind:class="{ 'rotate-180 text-emerald-600 dark:text-emerald-300': open }"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    stroke-width="2"
                                    aria-hidden="true"
                                >
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m6 9 6 6 6-6" />
                                </svg>
                            </button>

                            <div
                                id="recommendation-details-{{ $recommendation->id }}"
                                x-show="open"
                                x-cloak
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-1"
                                class="border-t border-slate-200 bg-slate-50 p-3 dark:border-slate-800 dark:bg-slate-950/40 sm:p-4"
                            >
                                <x-recommendation-card
                                    :recommendation="$recommendation"
                                    :creator="$creator"
                                    :usage="$usage"
                                    :top-requested="$recommendation->id === $topRequestedId"
                                    :owns-creator="$ownsCreator"
                                    :anchor="false"
                                />
                            </div>
                        </div>
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                            @if ($publicRecommendationsCount === 0)
                                <h2 class="text-lg font-semibold text-slate-950 dark:text-white">No recommendations yet. Be the first to suggest something for this journey.</h2>

                                @if ($creator->submissions_open)
                                    <a href="{{ route('recommendations.create', $creator) }}" class="mt-5 inline-flex rounded-full bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-500">
                                        Submit recommendation
                                    </a>
                                @endif
                            @else
                                <h2 class="text-lg font-semibold text-slate-950 dark:text-white">No recommendations found.</h2>
                                <a href="{{ route('creator.queue', $creator) }}" class="mt-4 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                    Clear filters
                                </a>
                            @endif
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </section>

    @if (auth()->check() && $isFavorited && $usage['votes_used'] > 0)
        <x-participation-confirmation-modal />
    @endif
</x-public-layout>
