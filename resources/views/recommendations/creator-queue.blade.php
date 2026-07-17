<x-public-layout :title="$creator->display_name.' | '.config('app.name', 'Guide My Journey')">
    <section class="px-4 py-4 sm:px-6 sm:py-6 lg:px-8">
        <div class="mx-auto min-w-0 max-w-5xl">
            <div
                x-data="{ creatorMenuOpen: false, biographyOpen: false, submissionGuidanceOpen: false, accoladeOpen: false }"
                x-on:keydown.escape.window="biographyOpen || submissionGuidanceOpen || accoladeOpen ? (biographyOpen = false, submissionGuidanceOpen = false, accoladeOpen = false) : creatorMenuOpen = false"
                class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900"
            >
                <x-creator.hero :creator="$creator" :header="$header" />


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
                        aria-label="Close request guidance"
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
                                    <h2 id="creator-submission-guidance-title" class="text-xl font-semibold tracking-tight sm:text-2xl">Request guidance</h2>
                                    <p class="mt-0.5 truncate text-sm font-medium text-slate-400">A note from {{ $creator->display_name }}</p>
                                </div>
                            </div>

                            <button
                                type="button"
                                x-on:click="submissionGuidanceOpen = false"
                                aria-label="Close request guidance"
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
                                    <span class="pointer-events-none absolute left-4 top-2 bg-gradient-to-br from-indigo-300/60 to-violet-300/20 bg-clip-text text-7xl font-black leading-none text-transparent" aria-hidden="true">&ldquo;</span>
                                    <div class="relative whitespace-pre-line pl-5 text-sm font-medium leading-6 text-slate-100 sm:text-base sm:leading-7">{{ $creator->submission_instructions }}</div>
                                    <span class="pointer-events-none absolute bottom-1 right-5 bg-gradient-to-br from-violet-300/35 to-indigo-300/10 bg-clip-text text-6xl font-black leading-none text-transparent" aria-hidden="true">&rdquo;</span>
                                </blockquote>
                            @else
                                <p class="rounded-2xl border border-white/10 bg-white/[0.04] px-5 py-4 text-sm font-medium leading-6 text-slate-300 sm:text-base">
                                    This creator has not added request guidance yet.
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                @if ($creatorAccolades['awards']->isNotEmpty())
                    <div x-show="accoladeOpen" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-slate-950/65 p-4 sm:items-center" role="dialog" aria-modal="true" aria-labelledby="creator-accolades-title" x-on:click.self="accoladeOpen = false">
                        <div class="max-h-[85vh] w-full max-w-xl overflow-y-auto rounded-3xl bg-white p-6 shadow-2xl dark:bg-slate-900">
                            <div class="flex items-start justify-between gap-4">
                                <div><p class="text-xs font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">Community milestones</p><h2 id="creator-accolades-title" class="mt-1 text-2xl font-extrabold">{{ $creator->display_name }} accolades</h2></div>
                                <button type="button" x-on:click="accoladeOpen = false" class="rounded-full p-2 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800" aria-label="Close accolades">&#10005;</button>
                            </div>
                            <div class="mt-6"><x-accolade-track-list :showcase="$creatorAccolades" /></div>
                        </div>
                    </div>
                @endif
            </div>

            <div class="mt-4 space-y-4">
                <x-creator.owner-toolbar :creator="$creator" :header="$header" />
                <x-creator.guide-activity-strip :creator="$creator" :header="$header" />
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
                            You’ll get votes back when requests you supported are published or closed.
                        </p>
                    @endif
                </div>
            @enderror

            @error('favorite')
                <div class="mb-5 rounded-2xl border border-red-200 bg-red-50 p-4 text-sm font-semibold text-red-700 dark:border-red-900 dark:bg-red-950 dark:text-red-200">{{ $message }}</div>
            @enderror

            <div class="flex min-w-0 flex-col gap-6">
                <aside class="order-2 min-w-0">
                    <div class="space-y-5">
                        <section class="w-full min-w-0 rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900" aria-labelledby="recently-published-title">
                            <x-subsection-label as="h2" id="recently-published-title">Recently Published</x-subsection-label>

                            @if ($recentPublishedRecommendations->isEmpty())
                                <p class="mt-4 text-sm leading-6 text-slate-500 dark:text-slate-400">No published requests yet.</p>
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
                                            aria-label="View published request: {{ $publishedDisplay['title'] }}"
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
                                    <div class="flex flex-wrap items-center gap-4">
                                    <a href="{{ route('creators.published', $creator) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 transition hover:text-emerald-600 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-emerald-500 dark:text-emerald-300 dark:hover:text-emerald-200">
                                        View all published
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                                        </svg>
                                    </a>
                                    <a href="{{ route('creators.closed', $creator) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-600 transition hover:text-indigo-600 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-slate-300 dark:hover:text-indigo-300">
                                        Closed Requests
                                    </a>
                                    </div>
                                </div>
                            @endif
                        </section>
                    </div>
                </aside>

                <div class="order-1 min-w-0 space-y-5">
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
                                x-bind:aria-label="open ? 'Hide filters' : 'Filter requests'"
                                aria-controls="creator-queue-filters"
                                class="inline-flex min-h-10 shrink-0 items-center gap-2 rounded-xl px-3 py-2 text-sm font-medium text-indigo-600 transition hover:bg-indigo-50 hover:text-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:text-indigo-300 dark:hover:bg-indigo-950/60 dark:hover:text-indigo-200 dark:focus-visible:ring-offset-slate-900"
                            >
                                <span x-text="open ? 'Hide filters' : 'Filter requests'">Filter requests</span>
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
                                <label for="queue-search" class="block text-sm font-medium text-slate-700 dark:text-slate-300">Search requests</label>
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
                        $visibleRecommendationIds = $recommendations->getCollection()
                            ->pluck('id')
                            ->map(fn ($id) => (int) $id)
                            ->values();
                    @endphp

                    <div
                        data-creator-request-accordion
                        class="space-y-5"
                        x-data="creatorRequestAccordion(@js($visibleRecommendationIds), @js($initialExpandedRequestId))"
                        x-init="
                            if (window.location.hash && openHashRequest()) {
                                $nextTick(() => document.getElementById(window.location.hash.slice(1))?.scrollIntoView({ block: 'start' }));
                            }
                        "
                        x-on:hashchange.window="
                            if (openHashRequest()) {
                                $nextTick(() => document.getElementById(window.location.hash.slice(1))?.scrollIntoView({ block: 'start' }));
                            }
                        "
                    >
                    @forelse ($recommendations as $recommendation)
                        @php
                            $rank = ($recommendations->firstItem() ?? 1) + $loop->index;
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
                            $isInitiallyExpanded = $initialExpandedRequestId === $recommendation->id;
                        @endphp

                        <div
                            data-creator-request-row
                            id="recommendation-{{ $recommendation->id }}"
                            x-data="{
                                requestId: @js($recommendation->id),
                                loaded: false,
                                loading: false,
                                error: false,
                                detailsHtml: '',
                                get open() {
                                    return this.expandedRequestId === this.requestId;
                                },
                                async loadDetails() {
                                    if (this.loaded || this.loading) return;
                                    this.loading = true;
                                    this.error = false;
                                    try {
                                        const response = await fetch(@js(route('requests.card-details', ['recommendation' => $recommendation, 'top' => $recommendation->id === $topRequestedId ? 1 : null])), {
                                            headers: { 'Accept': 'text/html', 'X-Requested-With': 'XMLHttpRequest' },
                                            credentials: 'same-origin',
                                        });
                                        if (! response.ok) throw new Error(`Request failed: ${response.status}`);
                                        this.detailsHtml = await response.text();
                                        this.loaded = true;
                                    } catch (error) {
                                        this.error = true;
                                    } finally {
                                        this.loading = false;
                                    }
                                },
                                toggleDetails() {
                                    this.toggleRequest(this.requestId);
                                },
                            }"
                            x-effect="if (open) loadDetails()"
                            class="scroll-mt-28 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 motion-reduce:transition-none hover:border-emerald-300 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900 dark:hover:border-emerald-700"
                            x-bind:class="open ? 'border-emerald-400 ring-2 ring-emerald-300/70 dark:border-emerald-500 dark:ring-emerald-500/40' : ''"
                        >
                            <button
                                type="button"
                                x-on:click="toggleDetails()"
                                aria-expanded="{{ $isInitiallyExpanded ? 'true' : 'false' }}"
                                x-bind:aria-expanded="open.toString()"
                                aria-controls="recommendation-details-{{ $recommendation->id }}"
                                class="group flex min-h-14 w-full min-w-0 cursor-pointer items-center gap-2.5 px-3 py-2 text-left transition hover:bg-emerald-50/60 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-inset dark:hover:bg-emerald-950/20 sm:min-h-[66px] sm:gap-4 sm:px-5"
                            >
                                <span class="inline-flex h-10 min-w-12 shrink-0 items-center justify-center rounded-xl border px-2.5 text-sm font-semibold sm:h-11 sm:min-w-14 {{ $rankClasses }}">
                                    {{ $rankLabel }}
                                </span>

                                <x-recommendation-compact-media :recommendation="$recommendation" />

                                <span class="min-w-0 flex-1">
                                    <span class="flex min-w-0 flex-col items-start gap-1 sm:flex-row sm:items-center sm:gap-2">
                                        <span class="min-w-0 flex-1 break-words text-sm font-semibold leading-snug text-slate-800 dark:text-slate-100 sm:text-base">
                                            {{ $recommendation->displayTitle() }}
                                        </span>
                                        <x-requests.status-badge :request="$recommendation" variant="compact" />
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
                                    class="size-5 shrink-0 text-slate-400 transition-transform duration-200 motion-reduce:transition-none"
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
                                @if (! $isInitiallyExpanded) x-cloak style="display: none;" @endif
                                x-transition:enter="transition ease-out duration-200"
                                x-transition:enter-start="opacity-0 -translate-y-1"
                                x-transition:enter-end="opacity-100 translate-y-0"
                                x-transition:leave="transition ease-in duration-150"
                                x-transition:leave-start="opacity-100 translate-y-0"
                                x-transition:leave-end="opacity-0 -translate-y-1"
                                class="border-t border-slate-200 bg-slate-50 p-3 motion-reduce:transition-none dark:border-slate-800 dark:bg-slate-950/40 sm:p-4"
                                x-bind:aria-busy="loading.toString()"
                            >
                                <div x-show="loading" class="flex min-h-32 items-center justify-center text-sm font-semibold text-slate-500 dark:text-slate-400" role="status">
                                    Loading request details&hellip;
                                </div>
                                <div x-show="error" style="display: none;" class="rounded-xl border border-red-200 bg-red-50 p-5 text-center dark:border-red-900 dark:bg-red-950/30" role="alert">
                                    <p class="text-sm font-semibold text-red-800 dark:text-red-200">Request details could not be loaded.</p>
                                    <button type="button" x-on:click="loadDetails()" class="mt-3 rounded-lg bg-red-700 px-3 py-2 text-sm font-bold text-white hover:bg-red-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500">Try again</button>
                                </div>
                                <div x-show="loaded" style="display: none;" x-html="detailsHtml"></div>
                            </div>
                        </div>
                    @empty
                        <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                            @if ($publicRecommendationsCount === 0)
                                <h2 class="text-lg font-semibold text-slate-950 dark:text-white">No requests yet. Be the first to suggest something for this journey.</h2>

                                @if ($creator->submissions_open)
                                    <a href="{{ route('recommendations.create', $creator) }}" class="mt-5 inline-flex rounded-full bg-indigo-600 px-5 py-3 text-sm font-semibold text-white hover:bg-indigo-500">
                                        Submit request
                                    </a>
                                @endif
                            @else
                                <h2 class="text-lg font-semibold text-slate-950 dark:text-white">No requests found.</h2>
                                <a href="{{ route('creator.queue', $creator) }}" class="mt-4 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                    Clear filters
                                </a>
                            @endif
                        </div>
                    @endforelse
                    </div>

                    @if ($recommendations->hasPages())
                        <div class="pt-2">
                            {{ $recommendations->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </section>

    @if (auth()->check() && $isFavorited && $usage['votes_used'] > 0)
        <x-participation-confirmation-modal />
    @endif
</x-public-layout>
