<x-public-layout :title="'Published Recommendations | '.$creator->display_name.' | '.config('app.name', 'Guide My Journey')">
    <section class="px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <div
            class="mx-auto min-w-0 max-w-6xl"
            x-data="{
                selectedId: null,
                resultIds: @js($publishedRecommendations->pluck('id')->map(fn ($id) => (int) $id)->values()),
                init() {
                    this.syncFromHash();
                    if (this.selectedId !== null) {
                        this.$nextTick(() => this.scrollToSelected());
                    }
                },
                syncFromHash() {
                    const match = window.location.hash.match(/^#recommendation-(\d+)$/);
                    this.selectedId = match ? Number(match[1]) : null;
                },
                hasSelectedResult() {
                    return this.selectedId !== null && this.resultIds.includes(this.selectedId);
                },
                selectRecommendation(id) {
                    this.selectedId = Number(id);
                    history.replaceState(null, '', `${window.location.pathname}${window.location.search}#recommendation-${id}`);
                    this.$nextTick(() => this.scrollToSelected());
                },
                scrollToSelected() {
                    const target = this.$refs.selectedPublishedRecommendation;
                    if (target) {
                        target.scrollIntoView({ block: 'start', behavior: 'smooth' });
                    }
                },
            }"
            x-on:hashchange.window="syncFromHash(); $nextTick(() => scrollToSelected())"
        >
            <div class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <x-creator-hero-background :creator="$creator" class="h-28 sm:h-36">
                    <div class="absolute inset-x-0 bottom-0 p-4">
                        <div class="flex min-w-0 items-end gap-3">
                            <x-creator-avatar
                                :creator="$creator"
                                size="lg"
                                class="hidden border-2 border-white/40 shadow-xl ring-4 ring-slate-950/30 sm:inline-flex"
                            />

                            <div class="min-w-0 pb-0.5">
                                <a href="{{ route('creator.queue', $creator) }}" class="text-sm font-bold text-white/85 drop-shadow hover:text-white">
                                    {{ $creator->display_name }}'s Journey
                                </a>
                                <h1 class="mt-1 break-words text-2xl font-extrabold leading-tight text-white drop-shadow-sm sm:text-3xl">Published Recommendations</h1>
                            </div>
                        </div>
                    </div>
                </x-creator-hero-background>

                <div class="p-4 sm:p-5">
                    <p class="max-w-2xl text-sm font-medium leading-6 text-slate-600 dark:text-slate-300">
                        Ideas this creator has already made, covered, explored, or published.
                    </p>

                    <div class="mt-4 flex flex-wrap items-center gap-2 text-xs font-bold text-slate-600 dark:text-slate-300">
                        <span class="rounded-full border border-slate-200 bg-slate-50 px-3 py-1.5 dark:border-slate-800 dark:bg-white/5">{{ $publishedRecommendationsCount }} {{ $publishedRecommendationsCount === 1 ? 'published recommendation' : 'published recommendations' }}</span>
                        <span class="rounded-full bg-emerald-100 px-3 py-1.5 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Made from the community's suggestions</span>
                    </div>
                </div>
            </div>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
                <form method="GET" action="{{ route('creators.published', $creator) }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="min-w-0 flex-1">
                        <label for="published-search" class="block text-sm font-bold text-slate-700 dark:text-slate-300">Search published recommendations</label>
                        <input
                            id="published-search"
                            name="q"
                            type="search"
                            value="{{ $filters['q'] }}"
                            placeholder="Title, channel, category, tag, description, or URL"
                            class="mt-1 block w-full rounded-xl border-slate-300 bg-white text-slate-950 shadow-sm placeholder:text-slate-400 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white"
                        >
                    </div>

                    <div class="flex gap-2">
                        <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2.5 text-sm font-bold text-white hover:bg-indigo-500">
                            Search
                        </button>

                        @if ($filters['q'] !== '')
                            <a href="{{ route('creators.published', $creator) }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-200 px-4 py-2.5 text-sm font-bold text-slate-600 hover:text-indigo-600 dark:border-slate-700 dark:text-slate-300">
                                Clear
                            </a>
                        @endif
                    </div>
                </form>
            </div>

            @if ($publishedRecommendations->isNotEmpty())
                <div
                    x-ref="selectedPublishedRecommendation"
                    x-show="selectedId !== null"
                    x-cloak
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    class="mt-6 scroll-mt-28"
                    aria-live="polite"
                >
                    <template x-if="selectedId !== null && ! hasSelectedResult()">
                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 text-sm font-semibold text-amber-900 dark:border-amber-800/70 dark:bg-amber-950/40 dark:text-amber-100">
                            The selected published recommendation is not in these search results.
                        </div>
                    </template>

                    @foreach ($publishedRecommendations as $recommendation)
                        <div x-show="selectedId === {{ $recommendation->id }}" x-cloak>
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                                <h2 class="text-sm font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400">Selected published recommendation</h2>
                                <a href="{{ route('creators.published', $creator) }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400" x-on:click="selectedId = null">
                                    View full catalog
                                </a>
                            </div>
                            <x-published-recommendation-detail :recommendation="$recommendation" />
                        </div>
                    @endforeach
                </div>
            @endif

            <div class="mt-6">
                @if ($publishedRecommendations->isNotEmpty())
                    <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                        <h2 class="text-sm font-extrabold uppercase tracking-wide text-slate-500 dark:text-slate-400" x-text="selectedId === null ? 'Published catalog' : 'More published recommendations'">Published catalog</h2>
                    </div>

                    <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($publishedRecommendations as $recommendation)
                            <x-published-recommendation-tile
                                :recommendation="$recommendation"
                                :creator="$creator"
                                x-on:click.prevent="selectRecommendation({{ $recommendation->id }})"
                                x-bind:class="selectedId === {{ $recommendation->id }} ? 'border-emerald-400 ring-2 ring-emerald-300/70 dark:border-emerald-500 dark:ring-emerald-500/40' : ''"
                            />
                        @endforeach
                    </div>
                @else
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                        @if ($publishedRecommendationsCount === 0)
                            <h2 class="text-lg font-bold text-slate-950 dark:text-white">No published recommendations yet.</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">When this creator marks recommendations as published, they'll appear here.</p>
                        @else
                            <h2 class="text-lg font-bold text-slate-950 dark:text-white">No published recommendations found.</h2>
                            <a href="{{ route('creators.published', $creator) }}" class="mt-4 inline-flex text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                Clear search
                            </a>
                        @endif
                    </div>
                @endif
            </div>
        </div>
    </section>
</x-public-layout>
