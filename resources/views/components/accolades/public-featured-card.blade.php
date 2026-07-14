@props(['item'])

<article class="rounded-2xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-4 dark:border-amber-900 dark:from-amber-950/35 dark:to-slate-900 sm:p-5">
    <p class="text-xs font-extrabold uppercase tracking-wider text-amber-700 dark:text-amber-300">Featured accolade</p>
    <div class="mt-3 flex items-start gap-3">
        <x-accolade-badge :definition="$item['definition']" :show-label="false" class="shrink-0" />
        <div class="min-w-0">
            <h3 class="text-lg font-extrabold text-slate-950 dark:text-white">{{ $item['name'] }}</h3>
            <p class="mt-1 text-sm leading-5 text-slate-600 dark:text-slate-300">{{ $item['description'] }}</p>
            <p class="mt-2 text-xs font-bold text-amber-700 dark:text-amber-300">{{ $item['track_label'] }}@if($item['awarded_date']) · Earned {{ $item['awarded_date'] }}@endif</p>
        </div>
    </div>
</article>
