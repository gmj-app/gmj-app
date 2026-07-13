@props(['recognition'])

@if ($recognition)
    @php
        $gold = $recognition['css_variant'] === 'gold';
        $classes = $gold ? 'border-amber-300 bg-gradient-to-r from-amber-50 via-yellow-50 to-white dark:border-amber-700/70 dark:from-amber-950/55 dark:via-yellow-950/25 dark:to-slate-900' : 'border-slate-300 bg-gradient-to-r from-slate-100 via-white to-slate-50 dark:border-slate-600 dark:from-slate-800 dark:via-slate-900 dark:to-slate-900';
        $badgeClasses = $gold ? 'border-amber-300 bg-amber-100 text-amber-900 dark:border-amber-600 dark:bg-amber-950 dark:text-amber-100' : 'border-slate-300 bg-white text-slate-800 dark:border-slate-600 dark:bg-slate-800 dark:text-slate-100';
    @endphp
    <section class="relative overflow-hidden rounded-2xl border p-4 shadow-sm {{ $classes }} sm:p-5" aria-labelledby="early-guide-heading">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex min-w-0 items-center gap-3">
                <span class="flex size-11 shrink-0 items-center justify-center rounded-full {{ $gold ? 'bg-amber-400/20 text-amber-700 dark:text-amber-300' : 'bg-slate-300/60 text-slate-700 dark:bg-slate-700 dark:text-slate-200' }}" aria-hidden="true">
                    <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"><path d="m12 3 2.5 5 5.5.8-4 3.9.9 5.5-4.9-2.6-4.9 2.6.9-5.5-4-3.9L9.5 8 12 3Z"/></svg>
                </span>
                <div class="min-w-0"><h2 id="early-guide-heading" class="text-base font-extrabold text-slate-950 dark:text-white">Early Guide recognition</h2><p class="mt-1 text-sm text-slate-600 dark:text-slate-300">{{ $recognition['description'] }}</p></div>
            </div>
            <span class="inline-flex w-fit shrink-0 items-center rounded-full border px-3 py-1.5 text-sm font-extrabold {{ $badgeClasses }}">{{ $recognition['label'] }} ({{ $recognition['plate_text'] }})</span>
        </div>
    </section>
@endif
