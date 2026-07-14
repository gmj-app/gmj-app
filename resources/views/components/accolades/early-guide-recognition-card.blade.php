@props(['recognition'])
@php
    $isGold = ($recognition['css_class'] ?? '') !== 'accolade-og';
@endphp

<article @class([
    'rounded-2xl border p-4 sm:p-5',
    'border-amber-300 bg-gradient-to-br from-amber-50 to-yellow-100/70 dark:border-amber-700 dark:from-amber-950/45 dark:to-yellow-950/20' => $isGold,
    'border-slate-300 bg-gradient-to-br from-slate-50 to-slate-200 dark:border-slate-600 dark:from-slate-800 dark:to-slate-950' => ! $isGold,
])>
    <p class="text-xs font-extrabold uppercase tracking-wider {{ $isGold ? 'text-amber-700 dark:text-amber-300' : 'text-slate-600 dark:text-slate-300' }}">Early Guide recognition</p>
    <h3 class="mt-3 text-lg font-extrabold text-slate-950 dark:text-white">{{ $recognition['name'] }} {{ $recognition['plate_text'] }}</h3>
    <p class="mt-1 text-sm leading-5 text-slate-600 dark:text-slate-300">{{ $recognition['description'] }}</p>
</article>
