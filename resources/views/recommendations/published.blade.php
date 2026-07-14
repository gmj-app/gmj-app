<x-public-layout :title="'Published Requests | '.$creator->display_name.' | '.config('app.name', 'Guide My Journey')">
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
                <x-creator-hero-background :creator="$creator" class="min-h-40 sm:min-h-36">
                    <div class="relative z-10 flex min-h-40 flex-col justify-center gap-4 px-4 py-5 sm:min-h-36 sm:px-5 lg:flex-row lg:items-center lg:justify-between lg:gap-6 lg:px-6">
                        <div class="flex min-w-0 flex-1 items-center gap-3 sm:gap-4">
                            <x-creator-avatar
                                :creator="$creator"
                                size="xl"
                                class="size-16 shrink-0 border-2 border-white/50 shadow-xl ring-4 ring-slate-950/25 sm:size-20 sm:text-2xl lg:text-3xl"
                            />

                            <div class="min-w-0 flex-1">
                                <a href="{{ route('creator.queue', $creator) }}" class="text-sm font-bold text-white/85 drop-shadow hover:text-white">
                                    {{ $creator->display_name }}
                                </a>
                                <h1 class="mt-1 max-w-3xl break-words text-2xl font-extrabold leading-tight text-white drop-shadow-sm sm:text-3xl">Published Requests</h1>
                                <p class="mt-2 max-w-2xl text-sm font-medium leading-5 text-white/85 drop-shadow">
                                    Ideas this creator has already made, covered, explored, or published.
                                </p>
                            </div>
                        </div>

                        <div class="flex flex-wrap gap-2 text-xs font-medium text-white/90 lg:max-w-xs lg:shrink-0 lg:justify-end">
                            <span class="rounded-full border border-white/20 bg-white/15 px-3 py-1.5 backdrop-blur-sm">{{ $publishedRecommendationsCount }} published {{ Str::plural('request', $publishedRecommendationsCount) }}</span>
                            <span class="rounded-full border border-white/20 bg-white/15 px-3 py-1.5 backdrop-blur-sm">Made from community requests</span>
                        </div>
                    </div>
                </x-creator-hero-background>
            </div>

            <nav aria-label="Creator request sections" class="mt-5 flex flex-wrap gap-2">
                <a href="{{ route('creator.queue', $creator) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">Active</a>
                <a href="{{ route('creators.published', $creator) }}" aria-current="page" class="rounded-xl border border-emerald-500 bg-emerald-50 px-4 py-2 text-sm font-bold text-emerald-700 dark:bg-emerald-950/50 dark:text-emerald-300">Published</a>
                <a href="{{ route('creators.closed', $creator) }}" class="rounded-xl border border-slate-200 bg-white px-4 py-2 text-sm font-bold text-slate-600 hover:border-indigo-300 hover:text-indigo-600 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-300">Closed</a>
            </nav>

            <div class="mt-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-5">
                <form method="GET" action="{{ route('creators.published', $creator) }}" class="flex flex-col gap-3 sm:flex-row sm:items-end">
                    <div class="min-w-0 flex-1">
                        <label for="published-search" class="block text-sm font-bold text-slate-700 dark:text-slate-300">Search published requests</label>
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
                            The selected published request is not in these search results.
                        </div>
                    </template>

                    @foreach ($publishedRecommendations as $recommendation)
                        <div x-show="selectedId === {{ $recommendation->id }}" x-cloak>
                            <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
                            <x-subsection-label as="h2">Selected published request</x-subsection-label>
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
                        <x-subsection-label as="h2" x-text="selectedId === null ? 'Published catalog' : 'More published requests'">Published catalog</x-subsection-label>
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
                    @if ($publishedRecommendations->hasPages())
                        <div class="mt-6">{{ $publishedRecommendations->links() }}</div>
                    @endif
                @else
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                        @if ($publishedRecommendationsCount === 0)
                            <h2 class="text-lg font-bold text-slate-950 dark:text-white">No published requests yet.</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">When this creator marks requests as published, they'll appear here.</p>
                        @else
                            <h2 class="text-lg font-bold text-slate-950 dark:text-white">No published requests found.</h2>
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
