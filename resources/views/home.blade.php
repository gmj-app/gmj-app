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
        <div class="mx-auto max-w-7xl">
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
                <div data-popular-creators-grid class="mt-5 grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($gridItems as $gridItem)
                        @if ($gridItem['type'] === 'creator')
                        @php($creator = $gridItem['item'])
                        <a
                            href="{{ route('creator.queue', $creator) }}"
                            aria-label="View {{ $creator->display_name }}"
                            data-creator-card
                            class="group flex h-full min-h-[15.5rem] min-w-0 cursor-pointer flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:border-indigo-300 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-500/60 dark:focus-visible:ring-offset-slate-950"
                        >
                            <div class="relative h-24 shrink-0 overflow-hidden bg-gradient-to-br from-indigo-600 via-sky-600 to-violet-600">
                                @if (filled($creator->hero_url))
                                    <img
                                        src="{{ $creator->hero_url }}"
                                        alt=""
                                        width="640"
                                        height="192"
                                        loading="lazy"
                                        class="absolute inset-0 h-full w-full object-cover object-center transition duration-300 group-hover:scale-105"
                                        onerror="this.remove()"
                                    >
                                @endif

                                <div class="absolute inset-0 bg-gradient-to-t from-slate-950/65 via-slate-950/20 to-transparent"></div>
                                <div class="absolute inset-0 bg-gradient-to-r from-slate-950/45 via-slate-950/10 to-transparent"></div>
                            </div>

                            <div class="flex flex-1 flex-col p-4 pt-0">
                                <div class="-mt-5 flex min-w-0 items-end gap-3">
                                    <x-creator-avatar :creator="$creator" size="lg" class="ring-4 ring-white dark:ring-slate-900" />

                                    <div class="min-w-0 flex-1 pb-1">
                                        <div class="flex min-w-0 items-center gap-2">
                                            <h3 title="{{ $creator->display_name }}" class="truncate text-lg font-bold text-slate-950 dark:text-white">{{ $creator->display_name }}</h3>
                                            @if ($creator->verification_status === 'verified')
                                                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Verified</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>

                                <p class="mt-2 line-clamp-2 text-sm leading-5 text-slate-600 dark:text-slate-400 lg:line-clamp-1">
                                    {{ $creator->card_description }}
                                </p>

                                <div class="mt-auto border-t border-slate-200/80 pt-4 dark:border-slate-800">
                                    <div class="flex flex-wrap items-center justify-center gap-x-2 gap-y-1 text-center text-xs tabular-nums text-slate-500 dark:text-slate-400 sm:text-sm">
                                        <span><strong class="text-slate-950 dark:text-white">{{ number_format((int) $creator->followers_count) }}</strong> {{ Str::plural('follower', (int) $creator->followers_count) }}</span>
                                        <span aria-hidden="true" class="text-slate-300 dark:text-slate-700">|</span>
                                        <span><strong class="text-slate-950 dark:text-white">{{ (int) $creator->visible_recommendations_count }}</strong> {{ Str::plural('request', (int) $creator->visible_recommendations_count) }}</span>
                                        <span aria-hidden="true" class="text-slate-300 dark:text-slate-700">|</span>
                                        <span><strong class="text-slate-950 dark:text-white">{{ (int) ($creator->published_recommendations_count ?? 0) }}</strong> published</span>
                                    </div>
                                </div>
                            </div>
                        </a>
                        @elseif ($gridItem['type'] === 'advertisement')
                            @php($advertisement = $gridItem['item'])
                            <a href="{{ route('ads.click', $advertisement) }}" target="_blank" rel="noopener noreferrer sponsored" aria-label="Sponsored: {{ $advertisement->advertiser_name ?: $advertisement->alt_text }}" class="group relative flex min-h-[15.5rem] min-w-0 overflow-hidden rounded-3xl border border-slate-200 bg-slate-900 shadow-sm transition duration-200 hover:-translate-y-1 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-slate-800 dark:focus-visible:ring-offset-slate-950">
                                <img src="{{ $advertisement->imageUrl() }}" alt="{{ $advertisement->alt_text }}" loading="lazy" class="absolute inset-0 h-full w-full object-cover transition duration-300 group-hover:scale-105">
                                @if ($advertisement->advertiser_name || $advertisement->cta_label)
                                    <span class="absolute inset-x-0 bottom-0 flex items-end bg-gradient-to-t from-slate-950 via-slate-950/80 to-transparent p-5 pr-32 pt-20 text-white">
                                        <span class="flex min-w-0 flex-col items-start gap-3">
                                            @if ($advertisement->advertiser_name)<span class="font-extrabold">{{ $advertisement->advertiser_name }}</span>@endif
                                            @if ($advertisement->cta_label)<span class="rounded-xl bg-white px-3 py-2 text-sm font-bold text-slate-950">{{ $advertisement->cta_label }}</span>@endif
                                        </span>
                                    </span>
                                @endif
                                <span class="absolute bottom-4 right-4 rounded-full bg-indigo-600 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-white shadow-sm ring-1 ring-white/20">Sponsored</span>
                            </a>
                        @elseif ($gridItem['type'] === 'add_creator')
                        <a
                            href="{{ route('creators.create') }}"
                            aria-label="Add Creator Account"
                            data-add-creator-card
                            class="group flex h-full min-h-[15.5rem] min-w-0 cursor-pointer flex-col items-center justify-center rounded-3xl border-2 border-dashed border-slate-300 bg-white p-5 text-center shadow-sm transition duration-200 hover:-translate-y-1 hover:border-indigo-400 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-indigo-500/70 dark:focus-visible:ring-offset-slate-950"
                        >
                            <div class="flex h-14 w-14 items-center justify-center rounded-full bg-indigo-50 text-4xl font-extrabold leading-none text-indigo-600 transition duration-200 group-hover:bg-indigo-600 group-hover:text-white dark:bg-indigo-500/10 dark:text-indigo-300 dark:group-hover:bg-indigo-500 dark:group-hover:text-white" aria-hidden="true">
                                +
                            </div>

                            <h3 class="mt-4 text-lg font-extrabold text-slate-950 dark:text-white">Add Creator Account</h3>
                            <p class="mt-2 max-w-xs text-sm leading-5 text-slate-600 dark:text-slate-400">
                                Create a page where fans can suggest, vote, and guide what you make next.
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
