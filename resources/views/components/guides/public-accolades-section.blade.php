@props(['summary', 'guide'])

<section class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-7" aria-labelledby="guide-accolades-title">
    <div class="flex items-start justify-between gap-4">
        <div>
            <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-amber-600 dark:text-amber-300">Guide accolades</p>
            <h2 id="guide-accolades-title" class="mt-1 text-2xl font-extrabold text-slate-950 dark:text-white">Accolades</h2>
            <p class="mt-1 max-w-2xl text-sm text-slate-600 dark:text-slate-300">Recognition earned through requests, support, and community participation.</p>
        </div>
        @if ($summary['has_recognition'])
            <button type="button" x-data x-on:click="$dispatch('open-modal', 'public-guide-accolades')" class="shrink-0 rounded-lg px-2 py-1 text-sm font-bold text-amber-700 hover:text-amber-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:text-amber-300">View all accolades</button>
        @endif
    </div>

    @if ($summary['has_recognition'])
        <div class="mt-5 grid gap-3 lg:grid-cols-[minmax(0,1.4fr)_minmax(280px,0.6fr)]">
            @if ($summary['featured'])
                <x-accolades.public-featured-card :item="$summary['featured']" />
            @elseif ($summary['early_guide'])
                <x-accolades.early-guide-recognition-card :recognition="$summary['early_guide']" />
            @endif
            @if ($summary['early_guide'] && $summary['featured'])
                <x-accolades.early-guide-recognition-card :recognition="$summary['early_guide']" />
            @endif
        </div>

        @if ($summary['highest_by_track']->isNotEmpty())
            <div class="mt-5 border-t border-slate-200 pt-4 dark:border-slate-800">
                <h3 class="text-sm font-extrabold text-slate-950 dark:text-white">Earned accolades</h3>
                <div class="mt-3 grid grid-cols-1 gap-2 min-[420px]:grid-cols-2 md:grid-cols-3 lg:grid-cols-5">
                    @foreach ($summary['highest_by_track'] as $item)
                        <x-accolades.public-earned-tile :item="$item" />
                    @endforeach
                </div>
            </div>
        @endif
        <x-accolades.public-collection-modal :summary="$summary" :guide="$guide" />
    @else
        <div class="mt-4 rounded-xl bg-slate-50 px-4 py-3 dark:bg-slate-950/50">
            <p class="text-sm font-bold text-slate-950 dark:text-white">No accolades earned yet.</p>
            <p class="mt-0.5 text-sm text-slate-600 dark:text-slate-300">This Guide’s earned recognition will appear here.</p>
        </div>
    @endif
</section>
