<x-app-layout>
    <x-slot name="title">My Accolades</x-slot>

    <div class="py-10 sm:py-12">
        <div class="mx-auto min-w-0 max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-amber-600 dark:text-amber-400">My Accolades</p>
                    <h1 class="mt-1 text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white">Your journey so far</h1>
                    <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Your private view of earned Guide accolades and persisted track progress.</p>
                </div>
                <a href="{{ route('dashboard') }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-300">Back to My Hub</a>
            </div>

            @if (! $accoladeSummary['has_earned'])
                <section class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white p-6 dark:border-slate-700 dark:bg-slate-900">
                    <h2 class="text-xl font-extrabold text-slate-950 dark:text-white">Your journey starts here</h2>
                    <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">Submit requests, support community ideas, and explore creators to earn accolades.</p>
                    <a href="{{ route('home') }}" class="mt-5 inline-flex min-h-10 items-center rounded-full bg-amber-500 px-4 py-2 text-sm font-bold text-slate-950 hover:bg-amber-400">Explore creators</a>
                </section>
            @else
                @if ($featured = $accoladeSummary['featured'])
                    <section class="mt-6 rounded-2xl border border-amber-200 bg-amber-50/70 p-5 dark:border-amber-900 dark:bg-amber-950/25 sm:p-6">
                        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-xs font-bold uppercase tracking-wider text-amber-700 dark:text-amber-300">Featured Guide accolade</p>
                                <div class="mt-3"><x-accolade-badge :definition="$featured['definition']" size="lg" /></div>
                                <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $featured['definition']['description'] }}</p>
                            </div>
                            <form method="POST" action="{{ route('profile.accolades.featured') }}" class="flex items-center gap-2">
                                @csrf @method('PATCH')
                                <label for="private-featured-accolade" class="sr-only">Featured accolade</label>
                                <select id="private-featured-accolade" name="accolade_id" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                                    @foreach ($accoladeSummary['awards']->filter(fn ($item) => $item['award']->is_public) as $item)
                                        <option value="{{ $item['award']->id }}" @selected($item['award']->id === $featured['award']->id)>{{ $item['definition']['name'] }}</option>
                                    @endforeach
                                </select>
                                <button class="rounded-xl bg-slate-900 px-3 py-2 text-sm font-bold text-white dark:bg-white dark:text-slate-900">Feature</button>
                            </form>
                        </div>
                    </section>
                @endif

                <section class="mt-6 rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-6">
                    <h2 class="text-xl font-extrabold text-slate-950 dark:text-white">Guide tracks</h2>
                    <div class="mt-6"><x-accolade-track-list :showcase="$accoladeSummary" /></div>
                </section>
            @endif
        </div>
    </div>
</x-app-layout>
