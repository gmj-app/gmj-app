<x-public-layout title="Guide My Journey">
    <section data-home-hero class="relative overflow-hidden px-4 pb-10 pt-10 text-center sm:px-6 sm:pb-8 sm:pt-12 lg:px-8 lg:pb-6 lg:pt-8">
        <div class="absolute inset-x-0 top-0 -z-10 mx-auto h-80 max-w-4xl rounded-full bg-indigo-200/50 blur-3xl dark:bg-indigo-900/20"></div>

        <div class="mx-auto max-w-5xl">
            <h1 class="text-4xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-6xl">
                Guide My Journey
            </h1>
            <p class="mx-auto mt-3 max-w-2xl text-xl font-bold leading-8 text-slate-800 dark:text-slate-100 sm:mt-2">
                <x-brand-tagline />
            </p>

            <div data-home-search-group class="mx-auto mt-6 w-full max-w-[42rem] lg:mt-4">
                <form method="GET" action="{{ route('search.index') }}" class="min-w-0 w-full">
                    <label for="creator-search" class="sr-only">Search creators, artists, songs, or topics</label>
                    <div class="flex flex-col gap-2 rounded-2xl border border-slate-200 bg-white p-2 shadow-2xl shadow-slate-900/10 focus-within:border-indigo-400 focus-within:ring-4 focus-within:ring-indigo-100 dark:border-slate-700 dark:bg-slate-900 dark:shadow-black/30 dark:focus-within:ring-indigo-950 sm:flex-row sm:items-center">
                        <div class="flex min-w-0 flex-1 items-center">
                            <svg viewBox="0 0 24 24" aria-hidden="true" class="ml-3 h-6 w-6 shrink-0 fill-none stroke-slate-400" stroke-width="2">
                                <circle cx="11" cy="11" r="7"></circle>
                                <path d="m20 20-4-4"></path>
                            </svg>
                            <input
                                id="creator-search"
                                name="q"
                                type="search"
                                value="{{ $search }}"
                                placeholder="Search creators, artists, songs, or topics..."
                                minlength="2"
                                class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-base text-slate-950 placeholder:text-slate-400 focus:ring-0 dark:text-white sm:px-4 sm:text-lg"
                            >
                        </div>
                        <button type="submit" class="min-h-12 w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 sm:w-auto sm:px-6">
                            Search
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </section>

    <section class="border-t border-slate-200 bg-white/60 px-4 py-10 dark:border-slate-800 dark:bg-slate-900/40 sm:px-6 sm:pb-14 sm:pt-8 lg:px-8 lg:pt-6">
        <div data-popular-creators-container class="mx-auto max-w-5xl">
            <x-section-header eyebrow="Creator journeys" :title="$search !== '' ? 'Search results' : 'Popular creators'">
                @if ($search !== '')
                    <x-slot:actions><a href="{{ route('home') }}" class="shrink-0 text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">Clear search</a></x-slot:actions>
                @endif
            </x-section-header>

            @if ($creators->isEmpty() && $search !== '')
                <div class="mt-8 rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                    <h3 class="text-lg font-bold text-slate-950 dark:text-white">No creators found.</h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Try another creator name, channel, or slug.</p>
                </div>
            @else
                @php($homeGridTileHeight = 'min-h-[19rem] md:h-[19rem] 2xl:h-72')
                <div data-popular-creators-grid class="mt-5 grid grid-cols-1 gap-x-4 gap-y-4 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($gridItems as $gridItem)
                        @if ($gridItem['type'] === 'creator')
                            <x-home-creator-card :creator="$gridItem['item']" :height-classes="$homeGridTileHeight" />
                        @elseif ($gridItem['type'] === 'advertisement')
                            <x-home-creator-card :advertisement="$gridItem['item']" :height-classes="$homeGridTileHeight" />
                        @elseif ($gridItem['type'] === 'add_creator')
                        <a
                            href="{{ route('creators.create') }}"
                            aria-label="Add Creator Account"
                            data-home-grid-tile
                            data-add-creator-card
                            class="group flex min-w-0 cursor-pointer flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-300 bg-white p-5 text-center shadow-sm transition duration-200 hover:-translate-y-1 hover:border-indigo-400 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-indigo-500/70 dark:focus-visible:ring-offset-slate-950 2xl:p-3 {{ $homeGridTileHeight }}"
                        >
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-50 text-4xl font-extrabold leading-none text-indigo-600 transition duration-200 group-hover:bg-indigo-600 group-hover:text-white dark:bg-indigo-500/10 dark:text-indigo-300 dark:group-hover:bg-indigo-500 dark:group-hover:text-white 2xl:h-12 2xl:w-12 2xl:text-3xl" aria-hidden="true">
                                +
                            </div>

                            <h3 class="mt-4 text-lg font-extrabold text-slate-950 dark:text-white">Add Creator Account</h3>
                            <p class="mt-2 line-clamp-2 max-w-xs text-sm leading-5 text-slate-600 dark:text-slate-400">
                                Create a page where fans can guide what you make next.
                            </p>
                            <span class="mt-4 inline-flex min-h-10 items-center justify-center rounded-xl bg-slate-950 px-4 py-2 text-sm font-bold text-white transition duration-200 group-hover:bg-indigo-600 dark:bg-white dark:text-slate-950 dark:group-hover:bg-indigo-400">
                                Get started
                            </span>
                        </a>
                        @endif
                    @endforeach
                </div>

                @if ($creators->hasPages())
                    <div class="mt-10">{{ $creators->links() }}</div>
                @endif
            @endif
        </div>
    </section>
</x-public-layout>
