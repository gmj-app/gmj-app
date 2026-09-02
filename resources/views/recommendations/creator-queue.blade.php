<x-public-layout :title="$creator->display_name.' | '.config('app.name', 'Guide My Journey')" :canonical="route('creator.queue', $creator)">
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
                @if ($recentPublishedRecommendations->isNotEmpty())
                    <section class="order-2 min-w-0 pt-2" aria-labelledby="recently-published-title" data-published-preview>
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <x-subsection-label as="h2" id="recently-published-title">Recently Published</x-subsection-label>
                            @if ($hasMorePublishedRecommendations)
                                <a href="{{ route('creators.published', $creator) }}" class="inline-flex items-center gap-1.5 text-sm font-semibold text-emerald-700 transition hover:text-emerald-600 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-emerald-500 dark:text-emerald-300 dark:hover:text-emerald-200">
                                    View all published
                                    <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" /></svg>
                                </a>
                            @endif
                        </div>

                        <div class="mt-4 grid grid-cols-1 items-stretch gap-5 md:grid-cols-2 xl:grid-cols-3">
                            @foreach ($recentPublishedRecommendations as $publishedRecommendation)
                                <x-published-recommendation-preview-tile :recommendation="$publishedRecommendation" :creator="$creator" />
                            @endforeach
                        </div>

                        <div class="mt-4 flex justify-end">
                            <a href="{{ route('creators.closed', $creator) }}" class="text-sm font-semibold text-slate-600 transition hover:text-indigo-600 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-slate-300 dark:hover:text-indigo-300">Closed Requests</a>
                        </div>
                    </section>
                @endif

                <div class="order-1 min-w-0 space-y-5">
                    @if ($recordedRecommendationsCount > 0)
                        <section
                            data-recorded-progress
                            aria-labelledby="recorded-progress-title"
                            class="overflow-hidden rounded-2xl border border-amber-300/70 bg-gradient-to-r from-amber-50 via-orange-50 to-white shadow-sm dark:border-amber-500/30 dark:from-amber-950/45 dark:via-orange-950/25 dark:to-slate-900"
                        >
                            <div class="flex flex-col gap-4 p-4 sm:flex-row sm:items-center sm:justify-between sm:p-5">
                                <div class="flex min-w-0 items-start gap-3.5">
                                    <span class="relative mt-0.5 inline-flex size-11 shrink-0 items-center justify-center rounded-full bg-amber-500 text-white shadow-sm shadow-amber-900/20" aria-hidden="true">
                                        <span class="absolute inset-0 animate-ping rounded-full bg-amber-400/25 motion-reduce:animate-none"></span>
                                        <svg class="relative size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 3v3m0 12v3M3 12h3m12 0h3" />
                                            <circle cx="12" cy="12" r="4" />
                                        </svg>
                                    </span>

                                    <div class="min-w-0">
                                        <p class="text-xs font-bold uppercase tracking-[0.12em] text-amber-700 dark:text-amber-300">Journey progress</p>
                                        <h2 id="recorded-progress-title" class="mt-1 text-base font-extrabold text-slate-950 dark:text-white sm:text-lg">
                                            {{ $recordedRecommendationsCount }} recorded {{ Str::plural('request', $recordedRecommendationsCount) }} moving toward publication
                                        </h2>
                                        <p class="mt-1 text-sm leading-5 text-slate-600 dark:text-slate-300">
                                            Recording is complete. Publication is the next step, so these community requests are getting close to the finish line.
                                        </p>
                                    </div>
                                </div>

                                @if ($filters['status'] === 'recorded')
                                    <span class="inline-flex min-h-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 px-4 py-2 text-sm font-bold text-amber-800 dark:bg-amber-500/15 dark:text-amber-200">
                                        Showing recorded requests
                                    </span>
                                @else
                                    <a
                                        href="{{ route('creator.queue', ['creator' => $creator, 'status' => 'recorded', 'per_page' => $perPage]) }}"
                                        class="inline-flex min-h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl bg-amber-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-amber-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:bg-amber-500 dark:text-slate-950 dark:hover:bg-amber-400 dark:focus-visible:ring-offset-slate-900"
                                    >
                                        See recorded requests
                                        <svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m9 18 6-6-6-6" />
                                        </svg>
                                    </a>
                                @endif
                            </div>
                        </section>
                    @endif

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
                                <input type="hidden" name="per_page" value="{{ $perPage }}">
                                @if($duplicateSource)<input type="hidden" name="duplicate_source" value="{{ $duplicateSource->id }}">@endif
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
                                    <a href="{{ route('creator.queue', ['creator' => $creator, 'per_page' => $perPage]) }}" class="inline-flex min-h-11 flex-1 items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-medium text-slate-600 hover:text-indigo-600 dark:border-slate-700 dark:text-slate-300">
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

                    @if($duplicateSource)
                        <div class="sticky top-3 z-20 flex flex-wrap items-center justify-between gap-3 rounded-2xl border border-amber-300 bg-amber-50 p-4 shadow-lg dark:border-amber-700 dark:bg-amber-950" role="status">
                            <div><p class="font-bold">Possible duplicate</p><p class="text-sm">Select the Request that matches “{{ $duplicateSource->displayTitle() }}”.</p></div>
                            <a href="{{ route('creator.queue', ['creator'=>$creator] + request()->except(['duplicate_source','duplicate_target'])) }}" class="rounded-xl border px-4 py-2 text-sm font-bold">Cancel</a>
                        </div>
                    @endif

                    <div
                        data-creator-request-accordion
                        class="space-y-3 sm:space-y-5"
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
                            $requestTitle = $recommendation->displayTitle();
                            $hasVoted = $recommendation->votedBy(auth()->user());
                            $totalVotes = $recommendation->totalVotes();
                            $isVotable = $recommendation->isVotable();
                            $loginReturnUrl = route('creator.queue', $creator, absolute: false).'#recommendation-'.$recommendation->id;
                            $duplicateQuery = request()->except(['duplicate_target','page']);
                            $isDuplicateCandidate = $duplicateSource && $duplicateSource->id !== $recommendation->id && $recommendation->isVotable();
                        @endphp

                        <div
                            data-creator-request-row
                            id="recommendation-{{ $recommendation->id }}"
                            x-data="creatorRequestVote({
                                requestId: @js($recommendation->id),
                                requestTitle: @js($requestTitle),
                                detailsUrl: @js(route('requests.card-details', ['recommendation' => $recommendation, 'top' => $recommendation->id === $topRequestedId ? 1 : null])),
                                voteUrl: @js(route('recommendations.vote', [$creator, $recommendation])),
                                loginUrl: @js(route('login.required', ['return' => $loginReturnUrl])),
                                authenticated: @js(auth()->check()),
                                votable: @js($isVotable),
                                hasVoted: @js($hasVoted),
                                votes: @js($totalVotes),
                                csrfToken: @js(csrf_token()),
                            })"
                            x-effect="if (open) loadDetails()"
                            class="scroll-mt-28 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition duration-200 motion-reduce:transition-none hover:border-emerald-300 hover:shadow-lg dark:border-slate-800 dark:bg-slate-900 dark:hover:border-emerald-700"
                            x-bind:class="open ? 'border-emerald-400 ring-2 ring-emerald-300/70 dark:border-emerald-500 dark:ring-emerald-500/40' : ''"
                        >
                            <div data-request-mobile-grid class="request-blade-header group {{ $isDuplicateCandidate ? 'request-blade-has-duplicate' : '' }}">
                                <span class="request-blade-rank {{ $rankClasses }}">
                                    {{ $rankLabel }}
                                </span>

                                <span class="request-blade-media">
                                    <x-recommendation-compact-media :recommendation="$recommendation" />
                                </span>

                                <button
                                    type="button"
                                    data-request-disclosure-body
                                    x-on:click="toggleDetails()"
                                    aria-expanded="{{ $isInitiallyExpanded ? 'true' : 'false' }}"
                                    x-bind:aria-expanded="open.toString()"
                                    aria-controls="recommendation-details-{{ $recommendation->id }}"
                                    class="request-blade-disclosure"
                                >
                                    <span class="request-blade-title-wrap">
                                        <span class="request-blade-title-row">
                                            <span data-request-mobile-title title="{{ $requestTitle }}" class="request-blade-title" x-bind:class="open ? 'line-clamp-none' : ''">
                                                {{ $requestTitle }}
                                            </span>
                                            <x-requests.status-badge :request="$recommendation" variant="compact" class="hidden lg:inline-flex" />
                                        </span>
                                        <x-recommendation-user-indicators
                                            :recommendation="$recommendation"
                                            :show-vote-indicator="false"
                                            class="mt-1"
                                        />
                                    </span>
                                </button>

                                <div data-request-secondary-actions class="request-blade-actions">
                                <x-requests.status-badge :request="$recommendation" variant="compact" class="request-blade-status" />

                                @auth
                                    @if($isDuplicateCandidate)
                                        <a x-on:click.stop href="{{ route('creator.queue', ['creator'=>$creator] + $duplicateQuery + ['duplicate_source'=>$duplicateSource->id,'duplicate_target'=>$recommendation->id]) }}" aria-label="Select “{{ $requestTitle }}” as possible duplicate" class="request-blade-duplicate">Select as duplicate</a>
                                    @elseif(!$duplicateSource && $recommendation->isReportable())
                                        <div x-data="requestOverflowMenu(@js($recommendation->id), @js(route('creator.queue', ['creator'=>$creator] + request()->query() + ['duplicate_source'=>$recommendation->id])), @js(route('recommendations.reports.store',[$creator,$recommendation])))" x-on:keydown.escape.window="escape()" class="request-blade-overflow shrink-0">
                                            <button type="button" x-ref="trigger" x-on:click.stop="toggle()" aria-label="More actions for “{{ $requestTitle }}”" aria-haspopup="menu" aria-controls="request-actions-{{ $recommendation->id }}" x-bind:aria-expanded="open.toString()" class="inline-flex size-11 items-center justify-center rounded-xl text-xl leading-none text-slate-500 transition hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-slate-300 dark:hover:bg-slate-800">⋮</button>
                                            <template x-teleport="body">
                                                <div id="request-actions-{{ $recommendation->id }}" x-ref="menu" x-show="open" x-cloak x-bind:style="`position: fixed; top: ${top}px; left: ${left}px; width: min(15rem, calc(100vw - 1rem));`" class="z-30 rounded-xl border border-slate-700 bg-slate-900 p-1.5 text-slate-100 shadow-xl" role="menu" aria-label="Request actions">
                                                    @if($recommendation->isVotable())<button type="button" x-ref="action" x-on:click.stop="beginDuplicateMode()" role="menuitem" class="flex min-h-11 w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-semibold transition hover:bg-slate-800 focus:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M8 12h8M12 8v8"/><circle cx="12" cy="12" r="9"/></svg>Report possible duplicate</button>@endif
                                                    <button type="button" @if(!$recommendation->isVotable()) x-ref="action" @endif x-on:click.stop="openReport()" role="menuitem" class="flex min-h-11 w-full items-center gap-2 rounded-lg px-3 py-2 text-left text-sm font-semibold transition hover:bg-slate-800 focus:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-400"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M5 21V4m0 0h11l-2 4 2 4H5"/></svg>Report</button>
                                                </div>
                                            </template>
                                            <template x-teleport="body"><div x-show="reportOpen" x-cloak class="fixed inset-0 z-50 flex items-end justify-center bg-black/70 p-4 sm:items-center" role="dialog" aria-modal="true" aria-labelledby="report-request-title-{{ $recommendation->id }}" x-on:click.self="closeReport()"><form method="POST" x-bind:action="reportUrl" class="max-h-[90vh] w-full max-w-lg overflow-y-auto rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900">@csrf<h2 id="report-request-title-{{ $recommendation->id }}" class="text-xl font-bold">Report Request</h2><p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Let the Creator know if this Request may be inappropriate.</p><label class="mt-5 block text-sm font-bold" for="report-reason-{{ $recommendation->id }}">Reason</label><select x-ref="reason" id="report-reason-{{ $recommendation->id }}" name="reason" required class="mt-1 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950">@foreach(\App\Models\RequestReport::REASONS as $value=>$label)<option value="{{ $value }}">{{ $label }}</option>@endforeach</select><label class="mt-4 block text-sm font-bold" for="report-details-{{ $recommendation->id }}">Additional details <span class="font-normal">(optional)</span></label><textarea id="report-details-{{ $recommendation->id }}" name="details" maxlength="500" rows="4" class="mt-1 w-full rounded-xl border-slate-300 dark:border-slate-700 dark:bg-slate-950"></textarea><div class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end"><button type="button" x-on:click="closeReport()" class="rounded-xl border px-5 py-3 font-bold">Cancel</button><button type="submit" class="rounded-xl bg-indigo-600 px-5 py-3 font-bold text-white">Submit report</button></div></form></div></template>
                                        </div>
                                    @endif
                                @endauth
                                @guest
                                    @if(!$duplicateSource && $recommendation->isReportable())
                                        @php($reportLoginUrl = route('login.required', ['return' => route('creator.queue', $creator, absolute: false).'#recommendation-'.$recommendation->id]))
                                        <div x-data="requestOverflowMenu(@js($recommendation->id), '', '')" x-on:keydown.escape.window="escape()" class="request-blade-overflow shrink-0"><button type="button" x-ref="trigger" x-on:click.stop="toggle()" aria-label="More actions for “{{ $requestTitle }}”" aria-haspopup="menu" aria-controls="request-actions-guest-{{ $recommendation->id }}" x-bind:aria-expanded="open.toString()" class="inline-flex size-11 items-center justify-center rounded-xl text-xl text-slate-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">⋮</button><template x-teleport="body"><div id="request-actions-guest-{{ $recommendation->id }}" x-ref="menu" x-show="open" x-cloak x-bind:style="`position: fixed; top: ${top}px; left: ${left}px; width: min(15rem, calc(100vw - 1rem));`" class="z-30 rounded-xl border border-slate-700 bg-slate-900 p-1.5 text-slate-100 shadow-xl" role="menu">@if($recommendation->isVotable())<button type="button" x-ref="action" x-on:click.stop="signIn(@js($reportLoginUrl))" role="menuitem" class="min-h-11 w-full rounded-lg px-3 text-left text-sm font-semibold hover:bg-slate-800">Report possible duplicate</button>@endif<button type="button" @if(!$recommendation->isVotable()) x-ref="action" @endif x-on:click.stop="signIn(@js($reportLoginUrl))" role="menuitem" class="min-h-11 w-full rounded-lg px-3 text-left text-sm font-semibold hover:bg-slate-800">Report</button></div></template></div>
                                    @endif
                                @endguest

                                @if ($isVotable)
                                    @auth
                                        <form
                                            method="POST"
                                            action="{{ route('recommendations.vote', [$creator, $recommendation]) }}"
                                            x-on:click.stop
                                            x-on:submit.prevent.stop="toggleVote($event, 'collapsed_blade')"
                                            class="request-blade-vote shrink-0"
                                        >
                                            @csrf
                                            <input type="hidden" name="_method" value="DELETE" @disabled(! $hasVoted) x-bind:disabled="! hasVoted">
                                            <button
                                                type="submit"
                                                data-collapsed-vote-button
                                                aria-label="{{ $hasVoted ? "Remove vote from “{$requestTitle}”" : "Vote for “{$requestTitle}”" }}"
                                                x-bind:aria-label="voteLabel"
                                                aria-pressed="{{ $hasVoted ? 'true' : 'false' }}"
                                                x-bind:aria-pressed="hasVoted.toString()"
                                                x-bind:disabled="votePending"
                                                class="inline-flex min-h-11 min-w-11 items-center justify-center gap-1.5 rounded-xl border px-2.5 text-sm font-bold transition focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-wait disabled:opacity-70 sm:min-w-[4.5rem] sm:px-3"
                                                x-bind:class="hasVoted
                                                    ? 'border-indigo-500 bg-indigo-600 text-white shadow-sm dark:border-indigo-400 dark:bg-indigo-500'
                                                    : 'border-slate-300 bg-white text-slate-700 hover:border-indigo-400 hover:bg-indigo-50 hover:text-indigo-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-indigo-600 dark:hover:bg-indigo-950/40'"
                                            >
                                                <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 10v10H4V10h3Zm0 10h10.2a2 2 0 0 0 1.94-1.52l1.5-6A2 2 0 0 0 18.7 10H14l.7-3.5A2.08 2.08 0 0 0 12.66 4L7 10Z" /></svg>
                                                <span class="tabular-nums" x-text="votes">{{ $totalVotes }}</span>
                                                <svg x-show="hasVoted" class="hidden size-3.5 shrink-0 sm:block" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" /></svg>
                                            </button>
                                        </form>
                                    @else
                                        <a
                                            href="{{ route('login.required', ['return' => $loginReturnUrl]) }}"
                                            data-collapsed-vote-button
                                            aria-label="Vote for “{{ $requestTitle }}”"
                                            x-on:click.stop
                                            class="request-blade-vote inline-flex min-h-11 min-w-11 shrink-0 items-center justify-center gap-1.5 rounded-xl border border-slate-300 bg-white px-2.5 text-sm font-bold text-slate-700 transition hover:border-indigo-400 hover:bg-indigo-50 hover:text-indigo-700 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 sm:min-w-[4.5rem] sm:px-3"
                                        >
                                            <svg class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.25" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M7 10v10H4V10h3Zm0 10h10.2a2 2 0 0 0 1.94-1.52l1.5-6A2 2 0 0 0 12.66 4L7 10Z" /></svg>
                                            <span class="tabular-nums">{{ $totalVotes }}</span>
                                        </a>
                                    @endauth
                                @else
                                    <span data-collapsed-vote-count class="request-blade-vote min-w-11 shrink-0 text-center">
                                        <span class="block text-base font-semibold leading-none text-slate-950 dark:text-white" x-text="votes">{{ $totalVotes }}</span>
                                        <span class="mt-1 block text-[10px] font-medium uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ Str::plural('vote', $totalVotes) }}</span>
                                    </span>
                                @endif

                                <button
                                    type="button"
                                    data-request-disclosure-chevron
                                    x-on:click="toggleDetails()"
                                    aria-label="{{ $isInitiallyExpanded ? 'Collapse' : 'Expand' }} “{{ $requestTitle }}”"
                                    x-bind:aria-label="`${open ? 'Collapse' : 'Expand'} “${requestTitle}”`"
                                    aria-expanded="{{ $isInitiallyExpanded ? 'true' : 'false' }}"
                                    x-bind:aria-expanded="open.toString()"
                                    aria-controls="recommendation-details-{{ $recommendation->id }}"
                                    class="request-blade-chevron inline-flex size-11 shrink-0 items-center justify-center rounded-xl text-slate-400 transition focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500"
                                >
                                    <svg
                                        class="size-5 transition-transform duration-200 motion-reduce:transition-none"
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
                                </div>
                            </div>

                            <p x-show="voteError" x-cloak class="border-t border-red-100 px-4 py-2 text-xs font-semibold text-red-700 dark:border-red-950 dark:text-red-300">
                                We couldn’t update your vote. Try again.
                            </p>
                            <span class="sr-only" aria-live="polite" aria-atomic="true" x-text="voteAnnouncement"></span>

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
                                <a href="{{ route('creator.queue', ['creator' => $creator, 'per_page' => $perPage]) }}" class="mt-4 inline-flex text-sm font-medium text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                    Clear filters
                                </a>
                            @endif
                        </div>
                    @endforelse
                    </div>

                    <div data-request-pagination-controls class="flex min-w-0 flex-col gap-3 pt-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
                        <p class="text-sm text-slate-600 dark:text-slate-300" aria-live="polite">
                            @if ($recommendations->total() > 0)
                                Showing <span class="font-medium">{{ $recommendations->firstItem() }}</span> to <span class="font-medium">{{ $recommendations->lastItem() }}</span> of <span class="font-medium">{{ $recommendations->total() }}</span> results
                            @else
                                Showing 0 results
                            @endif
                        </p>

                        <form method="GET" action="{{ route('creator.queue', $creator) }}" class="flex min-w-0 items-center gap-2" data-request-per-page-form>
                            @if($duplicateSource)<input type="hidden" name="duplicate_source" value="{{ $duplicateSource->id }}">@endif
                            @foreach (['q', 'status', 'category', 'tag', 'sort'] as $parameter)
                                @if ($filters[$parameter] !== '' && ! ($parameter === 'sort' && $filters[$parameter] === 'votes'))
                                    <input type="hidden" name="{{ $parameter }}" value="{{ $filters[$parameter] }}">
                                @endif
                            @endforeach

                            <label for="requests-per-page" class="shrink-0 text-sm font-medium text-slate-700 dark:text-slate-200">Show</label>
                            <select
                                id="requests-per-page"
                                name="per_page"
                                aria-label="Requests per page"
                                class="min-h-11 rounded-xl border-slate-300 bg-white py-2 pl-3 pr-8 text-base text-slate-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white sm:text-sm"
                                onchange="this.form.requestSubmit()"
                            >
                                @foreach ($perPageOptions as $option)
                                    <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
                                @endforeach
                            </select>
                            <span class="shrink-0 text-sm text-slate-600 dark:text-slate-300">per page</span>
                            <noscript>
                                <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Apply</button>
                            </noscript>
                        </form>

                        @if ($recommendations->hasPages())
                            <div class="min-w-0 sm:ml-auto [&>nav>div:last-child>div:first-child]:hidden [&>nav>div:last-child]:justify-end">
                                {{ $recommendations->links() }}
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </section>

    @if($duplicateSource && $duplicateTarget)
        <div class="fixed inset-0 z-50 flex items-end justify-center overflow-y-auto bg-black/70 p-4 sm:items-center" role="dialog" aria-modal="true" aria-labelledby="duplicate-dialog-title">
            <div class="w-full max-w-4xl rounded-2xl bg-white p-5 shadow-2xl dark:bg-slate-900 sm:p-7">
                <h2 id="duplicate-dialog-title" class="text-xl font-bold">Report possible duplicate</h2><p class="mt-2 text-sm text-slate-600 dark:text-slate-300">These Requests look like they may ask for the same thing. The Creator will review them and decide whether they should be merged.</p>
                <div class="mt-5 grid gap-4 md:grid-cols-2">@foreach([$duplicateSource,$duplicateTarget] as $item)<section class="rounded-xl border p-4"><h3 class="font-bold">{{ $item->displayTitle() }}</h3><p class="mt-2 text-sm">{{ $item->totalVotes() }} unique supporters · {{ $item->statusLabel() }}</p><p class="text-sm text-slate-500">{{ $item->submittedBy?->publicName() ?: 'Creator' }} · {{ $item->created_at->format('M j, Y') }}</p>@if($item->canonicalMediaUrl())<p class="mt-2 break-all text-xs">{{ parse_url($item->canonicalMediaUrl(), PHP_URL_HOST) }}</p>@endif</section>@endforeach</div>
                <form method="POST" action="{{ route('recommendations.duplicates.store',[$creator,$duplicateSource]) }}" class="mt-6 flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">@csrf<input type="hidden" name="duplicate_request_id" value="{{ $duplicateTarget->id }}"><a href="{{ route('creator.queue',['creator'=>$creator] + request()->except('duplicate_target')) }}" class="rounded-xl border px-5 py-3 text-center font-bold">Cancel</a><button class="rounded-xl bg-indigo-600 px-5 py-3 font-bold text-white">Report duplicate pair</button></form>
            </div>
        </div>
    @endif

</x-public-layout>
