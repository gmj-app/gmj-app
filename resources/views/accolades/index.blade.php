<x-app-layout>
    <x-slot name="title">My Accolades</x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto min-w-0 max-w-5xl px-4 sm:px-6 lg:px-8">
            <header class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-amber-600 dark:text-amber-400">Featured accolade</p>
                    <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white">Accolades</h1>
                    <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Your earned milestones and progress across the Guide journey.</p>
                </div>
                <a href="{{ route('dashboard') }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-500 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-indigo-300">Back to My Hub</a>
            </header>

            @if (session('success'))
                <div class="mt-4 rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm font-semibold text-emerald-800 dark:border-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-200" role="status">{{ session('success') }}</div>
            @endif

            <div class="mt-5 space-y-5 sm:space-y-6">
                <x-accolades.featured-summary :summary="$accoladeSummary" />
                <x-accolades.early-guide-banner :recognition="$accoladeSummary['early_guide']" />

                <section aria-labelledby="accolade-tracks-heading">
                    <div class="flex flex-wrap items-end justify-between gap-3">
                        <div>
                            <h2 id="accolade-tracks-heading" class="text-xl font-extrabold text-slate-950 dark:text-white">Your Accolade Tracks</h2>
                            <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Keep moving toward your next Guide milestones.</p>
                        </div>
                        @if (! $accoladeSummary['has_earned'])
                            <a href="{{ route('home') }}" class="inline-flex min-h-10 items-center rounded-full bg-amber-500 px-4 py-2 text-sm font-bold text-slate-950 hover:bg-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500">Explore creators</a>
                        @endif
                    </div>

                    <div class="mt-4 grid grid-cols-1 gap-4 lg:grid-cols-2">
                        @foreach ($accoladeSummary['tracks'] as $track)
                            <x-accolades.track-card :track="$track" />
                        @endforeach
                    </div>
                </section>
            </div>
        </div>
    </div>
</x-app-layout>
