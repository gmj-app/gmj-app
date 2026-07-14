@props(['summary', 'guide'])

<x-modal name="public-guide-accolades" max-width="2xl" labelled-by="public-guide-accolades-title" focusable>
    <div class="flex items-start justify-between gap-4 border-b border-slate-200 p-5 dark:border-slate-800 sm:p-6">
        <div>
            <p class="text-xs font-extrabold uppercase tracking-[0.16em] text-amber-600 dark:text-amber-300">Earned recognition</p>
            <h2 id="public-guide-accolades-title" class="mt-1 text-2xl font-extrabold text-slate-950 dark:text-white">{{ $guide->publicName() }}’s accolades</h2>
        </div>
        <button type="button" x-on:click="$dispatch('close')" class="rounded-full p-2 text-slate-500 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 dark:hover:bg-slate-800" aria-label="Close all accolades">&#10005;</button>
    </div>
    <div class="max-h-[70vh] space-y-6 overflow-y-auto p-5 sm:p-6">
        @if ($summary['early_guide'])
            <x-accolades.early-guide-recognition-card :recognition="$summary['early_guide']" />
        @endif
        @forelse ($summary['grouped'] as $track)
            <section aria-labelledby="accolade-track-{{ Str::slug($track->first()['track_key']) }}">
                <h3 id="accolade-track-{{ Str::slug($track->first()['track_key']) }}" class="text-sm font-extrabold text-slate-950 dark:text-white">{{ $track->first()['track_label'] }}</h3>
                <div class="mt-2 divide-y divide-slate-200 rounded-2xl border border-slate-200 px-4 dark:divide-slate-800 dark:border-slate-700">
                    @foreach ($track as $item)
                        <article class="flex items-start gap-3 py-4">
                            <x-accolade-badge :definition="$item['definition']" :show-label="false" size="sm" class="shrink-0" />
                            <div class="min-w-0">
                                <h4 class="font-extrabold text-slate-950 dark:text-white">{{ $item['name'] }}</h4>
                                <p class="mt-0.5 text-sm text-slate-600 dark:text-slate-300">{{ $item['description'] }}</p>
                                @if ($item['awarded_date'])<p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">Earned {{ $item['awarded_date'] }}</p>@endif
                            </div>
                        </article>
                    @endforeach
                </div>
            </section>
        @empty
            <p class="text-sm text-slate-600 dark:text-slate-300">No track accolades earned yet.</p>
        @endforelse
    </div>
</x-modal>
