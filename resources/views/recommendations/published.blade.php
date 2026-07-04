<x-public-layout :title="'Published Recommendations | '.$creator->display_name.' | '.config('app.name', 'Guide My Journey')">
    <section class="px-4 py-6 sm:px-6 sm:py-8 lg:px-8">
        <div class="mx-auto min-w-0 max-w-4xl">
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

            <div class="mt-6 space-y-4">
                @forelse ($publishedRecommendations as $recommendation)
                    @php
                        $publishedDate = $recommendation->published_at ?? $recommendation->updated_at ?? $recommendation->created_at;
                    @endphp

                    <div
                        id="recommendation-{{ $recommendation->id }}"
                        x-data="{ open: false }"
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
                        class="scroll-mt-28 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm transition dark:border-slate-800 dark:bg-slate-900"
                    >
                        <button
                            type="button"
                            x-on:click="open = ! open"
                            aria-expanded="false"
                            x-bind:aria-expanded="open.toString()"
                            aria-controls="recommendation-details-{{ $recommendation->id }}"
                            class="flex min-h-16 w-full min-w-0 cursor-pointer items-center gap-3 px-4 py-3 text-left transition hover:bg-slate-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-inset dark:hover:bg-slate-800/70 sm:min-h-20 sm:gap-4 sm:px-5"
                        >
                            <span class="inline-flex size-10 shrink-0 items-center justify-center rounded-xl bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                                </svg>
                            </span>

                            <span class="min-w-0 flex-1">
                                <span class="block break-words text-sm font-extrabold leading-5 text-slate-950 dark:text-white sm:text-base sm:leading-6">
                                    {{ $recommendation->title }}
                                </span>
                                <span class="mt-1 flex flex-wrap items-center gap-x-3 gap-y-1 text-xs font-semibold text-slate-500 dark:text-slate-400">
                                    <span>Published {{ $publishedDate->format('M j, Y') }}</span>
                                    @if ($recommendation->channel_title)
                                        <span>{{ $recommendation->channel_title }}</span>
                                    @elseif ($recommendation->artist)
                                        <span>{{ $recommendation->artist }}</span>
                                    @endif
                                </span>
                            </span>

                            <span class="hidden shrink-0 text-right sm:block">
                                <span class="block text-base font-extrabold leading-none text-slate-950 dark:text-white">{{ $recommendation->user_picks_count }}</span>
                                <span class="mt-1 block text-[11px] font-bold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ Str::plural('vote', $recommendation->user_picks_count) }}</span>
                            </span>

                            <svg
                                class="size-5 shrink-0 text-slate-400 transition-transform duration-200"
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
                                :usage="null"
                                :top-requested="false"
                                :anchor="false"
                                :show-voting-controls="false"
                            />
                        </div>
                    </div>
                @empty
                    <div class="rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-14 text-center dark:border-slate-700 dark:bg-slate-900">
                        @if ($publishedRecommendationsCount === 0)
                            <h2 class="text-lg font-bold text-slate-950 dark:text-white">No published recommendations yet.</h2>
                            <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Published suggestions will appear here once this creator makes them happen.</p>
                        @else
                            <h2 class="text-lg font-bold text-slate-950 dark:text-white">No published recommendations found.</h2>
                            <a href="{{ route('creators.published', $creator) }}" class="mt-4 inline-flex text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                                Clear search
                            </a>
                        @endif
                    </div>
                @endforelse
            </div>
        </div>
    </section>
</x-public-layout>
