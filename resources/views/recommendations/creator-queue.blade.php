<x-public-layout :title="$creator->display_name.' Journey | '.config('app.name', 'Guide My Journey')">
    <section class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        <div class="mx-auto min-w-0 max-w-5xl">
            <div
                x-data="{ creatorMenuOpen: false, biographyOpen: false }"
                x-on:keydown.escape.window="biographyOpen ? biographyOpen = false : creatorMenuOpen = false"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <x-creator-hero-background :creator="$creator" class="h-36 sm:h-44 lg:h-52">
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
                            </div>
                        </div>
                    </div>

                    <div class="absolute inset-x-0 bottom-0 p-4">
                        <div class="flex min-w-0 items-end gap-3 sm:gap-4">
                            <x-creator-avatar
                                :creator="$creator"
                                size="xl"
                                class="hidden border-2 border-white/40 shadow-xl ring-4 ring-slate-950/30 sm:inline-flex sm:h-20 sm:w-20 sm:text-2xl lg:h-24 lg:w-24 lg:text-3xl"
                            />

                            <div class="min-w-0 pb-0.5">
                                <h1 class="max-w-3xl break-words text-2xl font-extrabold leading-tight tracking-tight text-white drop-shadow-sm sm:text-3xl lg:text-4xl">{{ $creator->display_name }}'s Journey</h1>
                            </div>
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
                            <h2 id="creator-biography-title" class="text-xl font-extrabold tracking-tight sm:text-2xl">{{ $creator->display_name }}</h2>
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
                                <h3 class="text-lg font-extrabold">Description</h3>
                                <div class="mt-3 space-y-4 whitespace-pre-line text-sm font-medium leading-6 text-slate-100 sm:text-base sm:leading-7">{{ filled($creator->bio) ? $creator->bio : 'No biography has been added for this creator yet.' }}</div>
                            </section>

                            <section class="mt-7">
                                <h3 class="text-lg font-extrabold">More info</h3>
                                <div class="mt-4 space-y-4 text-sm font-semibold text-slate-100 sm:text-base">
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
                                        <span>{{ $publicVotesCount }} {{ $publicVotesCount === 1 ? 'upvote' : 'upvotes' }}</span>
                                    </div>
                                </div>
                            </section>
                        </div>
                    </div>
                </div>

                <div class="p-4 sm:p-5">
                    <div class="flex flex-col gap-3">
                        <div class="-mt-10 shrink-0 sm:hidden">
                            <x-creator-avatar
                                :creator="$creator"
                                size="lg"
                                class="border-2 border-white/80 shadow-md ring-4 ring-white/70 dark:border-slate-800 dark:ring-slate-900"
                            />
                        </div>

                        @php
                            $addRecommendationLabel = 'Add Recommendation';

                            if (auth()->check() && $isFavorited && $creator->submissions_open) {
                                $addRecommendationLabel .= " ({$usage['suggestions_remaining']}/{$usage['suggestions_limit']})";
                            }
                        @endphp

                        <div class="flex flex-col gap-3 lg:flex-row lg:items-start lg:justify-between">
                            <div class="flex flex-col gap-2.5 sm:flex-row sm:flex-wrap">
                                @auth
                                    <a
                                        href="{{ route('recommendations.create', $creator) }}"
                                        aria-label="Add a recommendation for {{ $creator->display_name }}"
                                        class="inline-flex min-h-11 items-center justify-center rounded-full bg-indigo-600 px-5 py-2.5 text-center text-sm font-bold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-500 {{ $usage['can_suggest'] && $creator->submissions_open ? '' : 'pointer-events-none bg-slate-400 shadow-none' }}"
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
                                        class="inline-flex min-h-11 items-center justify-center rounded-full bg-indigo-600 px-5 py-2.5 text-center text-sm font-bold text-white shadow-lg shadow-indigo-600/20 hover:bg-indigo-500"
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
                                        class="inline-flex min-h-11 items-center justify-center rounded-full border border-slate-200 bg-white px-5 py-2.5 text-center text-sm font-bold text-slate-700 hover:border-red-200 hover:text-red-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200"
                                    >
                                        Visit Channel
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
                                                        body: @js("Removing this creator from your favorites will also remove your upvotes on this creator's suggestions."),
                                                        resourceLine: @js("Active upvotes on this creator: {$usage['votes_used']}"),
                                                        confirmLabel: 'Remove favorite and upvotes',
                                                        destructive: true,
                                                    });
                                                "
                                            @endif
                                        >
                                            @csrf
                                            <button
                                                type="submit"
                                                class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full border px-5 py-2.5 text-sm font-bold shadow-sm transition focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900 sm:w-auto {{ $isFavorited ? 'border-amber-300 bg-amber-100 text-amber-800 hover:bg-amber-200 dark:border-amber-500/50 dark:bg-amber-500/15 dark:text-amber-300 dark:hover:bg-amber-500/25' : 'border-slate-200 bg-white text-slate-700 hover:border-amber-300 hover:text-amber-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-amber-400/60 dark:hover:text-amber-300' }}"
                                            >
                                                <svg class="size-5 {{ $isFavorited ? 'fill-current' : 'fill-none' }}" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="m12 3.75 2.475 5.016 5.535.804-4.005 3.904.946 5.512L12 16.383l-4.951 2.603.946-5.512L3.99 9.57l5.535-.804L12 3.75Z" />
                                                </svg>
                                                {{ $isFavorited ? 'Favorited' : 'Favorite' }}
                                            </button>
                                        </form>
                                    @else
                                        <a href="{{ route('login.required', ['return' => route('creator.queue', $creator, absolute: false)]) }}" class="inline-flex min-h-11 w-full items-center justify-center gap-2 rounded-full border border-slate-200 bg-white px-5 py-2.5 text-sm font-bold text-slate-700 shadow-sm transition hover:border-amber-300 hover:text-amber-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-amber-400/60 dark:hover:text-amber-300 dark:focus-visible:ring-offset-slate-900 sm:w-auto">
                                            <svg class="size-5 fill-none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="m12 3.75 2.475 5.016 5.535.804-4.005 3.904.946 5.512L12 16.383l-4.951 2.603.946-5.512L3.99 9.57l5.535-.804L12 3.75Z" />
                                            </svg>
                                            Favorite
                                        </a>
                                    @endauth
                                @endif
                            </div>

                            <div class="flex flex-wrap gap-2 text-xs font-bold text-slate-600 dark:text-slate-300 lg:max-w-sm lg:justify-end">
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 dark:border-slate-800 dark:bg-white/5">{{ $publicRecommendationsCount }} {{ $publicRecommendationsCount === 1 ? 'recommendation' : 'recommendations' }}</span>
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 dark:border-slate-800 dark:bg-white/5">{{ $favoritesCount }} {{ $favoritesCount === 1 ? 'follower' : 'followers' }}</span>
                                <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 dark:border-slate-800 dark:bg-white/5">{{ $publicVotesCount }} {{ $publicVotesCount === 1 ? 'upvote' : 'upvotes' }}</span>
                            </div>
                        </div>

                        @auth
                            @if ($ownsCreator)
                                <div class="text-xs font-semibold sm:text-sm">
                                    <a href="{{ route('creators.dashboard', $creator) }}" class="text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                        Manage creator page
                                    </a>
                                </div>
                            @endif
                        @endauth

                        @if ($creator->submission_instructions)
                            <details class="max-w-3xl rounded-xl border border-slate-200 bg-slate-50 px-4 py-2.5 text-sm leading-6 text-slate-700 dark:border-slate-800 dark:bg-white/5 dark:text-slate-300">
                                <summary class="cursor-pointer font-bold text-slate-800 marker:text-slate-400 dark:text-slate-100">Submission guidance</summary>
                                <p class="mt-2">{{ $creator->submission_instructions }}</p>
                            </details>
                        @endif
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
                <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200">{{ $message }}</div>
            @enderror

            @error('favorite')
                <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200">{{ $message }}</div>
            @enderror

            <div class="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,1fr)_17rem] lg:items-start">
                <aside class="min-w-0 lg:col-start-2 lg:row-start-1">
                    <div class="w-full min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 lg:sticky lg:top-24">
                        @auth
                            <div class="flex items-start justify-between gap-4">
                                <div class="min-w-0">
                                    <p class="truncate font-bold text-slate-950 dark:text-white">{{ auth()->user()->name }}</p>
                                    <p class="mt-0.5 truncate text-xs text-slate-500 dark:text-slate-400">{{ auth()->user()->email }}</p>
                                </div>
                                <span class="shrink-0 rounded-full bg-indigo-100 px-2.5 py-1 text-xs font-bold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300">{{ $usage['tier'] }}</span>
                            </div>

                            <div class="mt-5 border-t border-slate-100 pt-5 dark:border-slate-800">
                                <h2 class="text-xs font-extrabold uppercase tracking-[0.18em] text-slate-500 dark:text-slate-400">Your limits</h2>
                                <dl class="mt-4 grid grid-cols-1 gap-3 sm:grid-cols-3 lg:grid-cols-1 lg:gap-4">
                                    @foreach ([
                                        ['Creators favorited', $usage['reactors_remaining'], $usage['reactors_used'], $usage['reactors_limit']],
                                        ['Suggestions remaining', $usage['suggestions_remaining'], $usage['suggestions_used'], $usage['suggestions_limit']],
                                        ['Upvotes remaining', $usage['votes_remaining'], $usage['votes_used'], $usage['votes_limit']],
                                    ] as [$label, $remaining, $used, $limit])
                                        <div class="rounded-2xl bg-slate-50 p-3 dark:bg-slate-950/60">
                                            <dt class="text-xs font-semibold leading-4 text-slate-500 dark:text-slate-400">{{ $label }}</dt>
                                            <dd class="mt-2 text-xl font-extrabold text-slate-950 dark:text-white">{{ $remaining }}</dd>
                                            <p class="mt-1 text-[11px] text-slate-500 dark:text-slate-400">{{ $used }} of {{ $limit }} used</p>
                                        </div>
                                    @endforeach
                                </dl>
                            </div>

                            <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-slate-100 pt-4 text-sm font-bold dark:border-slate-800">
                                <a href="{{ route('profile.edit') }}" class="inline-flex min-h-11 items-center rounded-xl px-3 text-indigo-600 hover:bg-indigo-50 hover:text-indigo-500 dark:text-indigo-400 dark:hover:bg-indigo-950/60">
                                    Profile
                                </a>

                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="inline-flex min-h-11 items-center rounded-xl px-3 text-slate-500 hover:bg-slate-100 hover:text-slate-800 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-white">
                                        Log out
                                    </button>
                                </form>
                            </div>
                        @else
                            <p class="font-bold text-slate-950 dark:text-white">Join the community</p>
                            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Create a free account to suggest ideas and upvote this journey.</p>
                            <div class="mt-4 grid grid-cols-1 gap-2 sm:grid-cols-2 lg:grid-cols-1">
                                <a href="{{ route('register') }}" class="inline-flex min-h-11 items-center justify-center rounded-full bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-500">Register</a>
                                <a href="{{ route('login') }}" class="inline-flex min-h-11 items-center justify-center rounded-full border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-700 hover:border-indigo-200 hover:text-indigo-600 dark:border-slate-700 dark:text-slate-200">Log in</a>
                            </div>
                        @endauth
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
                        <div class="flex items-center justify-between gap-4 px-4 py-3.5 sm:px-5">
                            <div class="min-w-0">
                                <div class="flex flex-wrap items-center gap-2">
                                    <h2 class="text-base font-extrabold text-slate-950 dark:text-white">Filters</h2>
                                    @if ($activeFilterCount > 0)
                                        <span
                                            data-active-filter-count="{{ $activeFilterCount }}"
                                            aria-label="{{ $activeFilterCount }} active {{ Str::plural('filter', $activeFilterCount) }}"
                                            class="inline-flex min-w-6 items-center justify-center rounded-full bg-indigo-100 px-2 py-0.5 text-xs font-extrabold text-indigo-700 dark:bg-indigo-950 dark:text-indigo-300"
                                        >
                                            {{ $activeFilterCount }}
                                        </span>
                                    @endif
                                </div>
                                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">Search and filter suggestions</p>
                            </div>

                            <button
                                type="button"
                                x-on:click="open = ! open"
                                aria-expanded="false"
                                x-bind:aria-expanded="open.toString()"
                                x-bind:aria-label="open ? 'Hide filters' : 'Show filters'"
                                aria-controls="creator-queue-filters"
                                class="inline-flex min-h-10 shrink-0 items-center gap-2 rounded-xl px-3 py-2 text-sm font-bold text-indigo-600 transition hover:bg-indigo-50 hover:text-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:text-indigo-300 dark:hover:bg-indigo-950/60 dark:hover:text-indigo-200 dark:focus-visible:ring-offset-slate-900"
                            >
                                <span x-text="open ? 'Hide filters' : 'Show filters'">Show filters</span>
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
                                <label for="queue-search" class="block text-sm font-bold text-slate-700 dark:text-slate-300">Search recommendations</label>
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
                                <label for="queue-status" class="block text-sm font-bold text-slate-700 dark:text-slate-300">Status</label>
                                <select id="queue-status" name="status" class="mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                    <option value="">All statuses</option>
                                    @foreach ($statusOptions as $status => $label)
                                        <option value="{{ $status }}" @selected($filters['status'] === $status)>{{ $label }}</option>
                                    @endforeach
                                </select>
                            </div>

                            @if ($categoryOptions->isNotEmpty())
                                <div class="xl:col-span-2">
                                    <label for="queue-category" class="block text-sm font-bold text-slate-700 dark:text-slate-300">Category</label>
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
                                    <label for="queue-tag" class="block text-sm font-bold text-slate-700 dark:text-slate-300">Tag</label>
                                    <select id="queue-tag" name="tag" class="mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                        <option value="">All tags</option>
                                        @foreach ($tagOptions as $tag)
                                            <option value="{{ $tag->slug }}" @selected($filters['tag'] === $tag->slug)>{{ $tag->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif

                            <div class="xl:col-span-2">
                                <label for="queue-sort" class="block text-sm font-bold text-slate-700 dark:text-slate-300">Sort</label>
                                <select id="queue-sort" name="sort" class="mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white">
                                    <option value="votes" @selected($filters['sort'] === 'votes')>Most upvotes</option>
                                    <option value="newest" @selected($filters['sort'] === 'newest')>Newest</option>
                                    <option value="status" @selected($filters['sort'] === 'status')>Status</option>
                                    <option value="scheduled" @selected($filters['sort'] === 'scheduled')>Scheduled date</option>
                                </select>
                            </div>

                            <div class="flex w-full flex-col gap-2 xl:col-span-2 xl:flex-row">
                                <button type="submit" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-500">
                                    Apply
                                </button>

                                @if ($filters['q'] !== '' || $filters['status'] !== '' || $filters['category'] !== '' || $filters['tag'] !== '' || $filters['sort'] !== 'votes')
                                    <a href="{{ route('creator.queue', $creator) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:text-indigo-600 dark:border-slate-700 dark:text-slate-300">
                                        Clear
                                    </a>
                                @endif
                            </div>
                            </form>
                        </div>
                    </div>

                    @forelse ($recommendations as $recommendation)
                        <x-recommendation-card
                            :recommendation="$recommendation"
                            :creator="$creator"
                            :usage="$usage"
                            :top-requested="$recommendation->id === $topRequestedId"
                        />
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                            @if ($publicRecommendationsCount === 0)
                                <h2 class="text-lg font-bold text-slate-950 dark:text-white">No recommendations yet. Be the first to suggest something for this journey.</h2>

                                @if ($creator->submissions_open)
                                    <a href="{{ route('recommendations.create', $creator) }}" class="mt-5 inline-flex rounded-full bg-indigo-600 px-5 py-3 text-sm font-bold text-white hover:bg-indigo-500">
                                        Submit recommendation
                                    </a>
                                @endif
                            @else
                                <h2 class="text-lg font-bold text-slate-950 dark:text-white">No recommendations found.</h2>
                                <a href="{{ route('creator.queue', $creator) }}" class="mt-4 inline-flex text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
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
