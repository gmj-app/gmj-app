<x-public-layout title="Guide My Journey">
    <section class="relative overflow-hidden px-4 pb-16 pt-12 text-center sm:px-6 sm:pb-24 sm:pt-20 lg:px-8">
        <div class="absolute inset-x-0 top-0 -z-10 mx-auto h-80 max-w-4xl rounded-full bg-indigo-200/50 blur-3xl dark:bg-indigo-900/20"></div>

        <div class="mx-auto max-w-3xl">
            <h1 class="text-4xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-6xl">
                Guide My Journey
            </h1>
            <p class="mx-auto mt-4 max-w-2xl text-xl font-bold leading-8 text-slate-800 dark:text-slate-100">
                <x-brand-tagline />
            </p>

            <form method="GET" action="{{ route('home') }}" class="mx-auto mt-8 max-w-2xl">
                <label for="creator-search" class="sr-only">Search for a creator</label>
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
                            placeholder="Search for a creator..."
                            class="min-w-0 flex-1 border-0 bg-transparent px-3 py-3 text-base text-slate-950 placeholder:text-slate-400 focus:ring-0 dark:text-white sm:px-4 sm:text-lg"
                        >
                    </div>
                    <button type="submit" class="min-h-12 w-full rounded-xl bg-indigo-600 px-4 py-3 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 sm:w-auto sm:px-6">
                        Search
                    </button>
                </div>
            </form>
        </div>
    </section>

    <section class="border-t border-slate-200 bg-white/60 px-4 py-14 dark:border-slate-800 dark:bg-slate-900/40 sm:px-6 sm:py-20 lg:px-8">
        <div class="mx-auto max-w-7xl">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">Creator journeys</p>
                    <h2 class="mt-2 text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white">
                        {{ $search !== '' ? 'Search results' : 'Popular creators' }}
                    </h2>
                </div>

                @if ($search !== '')
                    <a href="{{ route('home') }}" class="shrink-0 text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">
                        Clear search
                    </a>
                @endif
            </div>

            @if ($creators->isEmpty())
                <div class="mt-8 rounded-3xl border border-dashed border-slate-300 bg-white px-6 py-16 text-center dark:border-slate-700 dark:bg-slate-900">
                    <h3 class="text-lg font-bold text-slate-950 dark:text-white">No creators found.</h3>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-400">Try another creator name, channel, or slug.</p>
                </div>
            @else
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($creators as $creator)
                        @php($creatorTopRequests = $topRequests->get($creator->id, collect()))

                        <a
                            href="{{ route('creator.queue', $creator) }}"
                            aria-label="View {{ $creator->display_name }}'s journey"
                            class="group flex h-full min-w-0 cursor-pointer flex-col rounded-3xl border border-slate-200 bg-white p-5 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-indigo-300 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-500/60 dark:focus-visible:ring-offset-slate-950"
                        >
                            <div class="flex items-center gap-4">
                                <x-creator-avatar :creator="$creator" />

                                <div class="min-w-0 flex-1">
                                    <div class="flex flex-wrap items-center gap-2">
                                        <h3 class="truncate text-lg font-bold text-slate-950 dark:text-white">{{ $creator->display_name }}</h3>
                                        @if ($creator->verification_status === 'verified')
                                            <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Verified</span>
                                        @endif
                                    </div>
                                    <p class="mt-1 line-clamp-2 text-sm leading-5 text-slate-600 dark:text-slate-400">
                                        {{ $creator->card_description }}
                                    </p>
                                </div>
                            </div>

                            <div class="mt-6 rounded-2xl bg-slate-50 p-4 dark:bg-slate-950/60">
                                <p class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Top requests</p>

                                @if ($creatorTopRequests->isEmpty())
                                    <p class="mt-3 text-sm font-semibold leading-6 text-slate-700 dark:text-slate-300">
                                        No public requests yet
                                    </p>
                                @else
                                    <ol class="mt-3 space-y-2.5">
                                        @foreach ($creatorTopRequests as $request)
                                            <li class="flex min-w-0 items-start gap-2.5">
                                                <span class="inline-flex h-6 w-6 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-xs font-extrabold text-indigo-700 dark:bg-indigo-500/15 dark:text-indigo-300">
                                                    {{ $loop->iteration }}
                                                </span>
                                                <span class="line-clamp-2 min-w-0 text-sm font-semibold leading-5 text-slate-800 dark:text-slate-200">
                                                    {{ $request->title }}
                                                </span>
                                            </li>
                                        @endforeach
                                    </ol>
                                @endif
                            </div>

                            <div class="mt-auto border-t border-slate-200/80 pt-5 dark:border-slate-800">
                                <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm text-slate-500 dark:text-slate-400">
                                    <span><strong class="text-slate-950 dark:text-white">{{ $creator->total_votes_count }}</strong> {{ Str::plural('upvote', $creator->total_votes_count) }}</span>
                                    <span><strong class="text-slate-950 dark:text-white">{{ $creator->visible_recommendations_count }}</strong> requests</span>
                                </div>
                            </div>
                        </a>
                    @endforeach
                </div>

                @if ($creators->hasPages())
                    <div class="mt-10">{{ $creators->links() }}</div>
                @endif
            @endif
        </div>
    </section>
</x-public-layout>
