@props(['track'])

@php
    $themes = [
        'violet' => ['border' => 'border-violet-200 dark:border-violet-900/70', 'icon' => 'bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-200', 'bar' => 'bg-violet-500', 'text' => 'text-violet-700 dark:text-violet-300'],
        'amber' => ['border' => 'border-amber-200 dark:border-amber-900/70', 'icon' => 'bg-amber-100 text-amber-800 dark:bg-amber-950 dark:text-amber-200', 'bar' => 'bg-amber-500', 'text' => 'text-amber-700 dark:text-amber-300'],
        'emerald' => ['border' => 'border-emerald-200 dark:border-emerald-900/70', 'icon' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-200', 'bar' => 'bg-emerald-500', 'text' => 'text-emerald-700 dark:text-emerald-300'],
        'sky' => ['border' => 'border-sky-200 dark:border-sky-900/70', 'icon' => 'bg-sky-100 text-sky-700 dark:bg-sky-950 dark:text-sky-200', 'bar' => 'bg-sky-500', 'text' => 'text-sky-700 dark:text-sky-300'],
        'slate' => ['border' => 'border-slate-200 dark:border-slate-800', 'icon' => 'bg-slate-100 text-slate-700 dark:bg-slate-800 dark:text-slate-200', 'bar' => 'bg-slate-500', 'text' => 'text-slate-700 dark:text-slate-300'],
    ];
    $theme = $themes[$track['accent']] ?? $themes['slate'];
    $highest = $track['highest_earned'];
    $maximum = $track['next']['threshold'] ?? max(1, $track['effective_value']);
    $progressLabel = $track['completed'] ? $track['label'].' track complete' : $track['effective_value'].' of '.$track['next']['threshold'].' toward '.$track['next']['name'];
@endphp

<article data-accolade-track="{{ $track['key'] }}" class="flex min-w-0 flex-col rounded-2xl border bg-white p-4 shadow-sm {{ $theme['border'] }} dark:bg-slate-900 sm:p-5">
    <div class="flex min-w-0 items-start justify-between gap-3">
        <div class="flex min-w-0 items-center gap-3">
            <span class="flex size-10 shrink-0 items-center justify-center rounded-full {{ $theme['icon'] }}" aria-hidden="true">
                <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9">
                    @switch($track['icon_key'])
                        @case('globe') <circle cx="12" cy="12" r="9"/><path d="M3 12h18M12 3c3 3 3 15 0 18M12 3c-3 3-3 15 0 18"/> @break
                        @case('ripple') <circle cx="12" cy="12" r="2"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="10"/> @break
                        @case('boots') <path d="M7 3v9l-3 3v4h8v-4L9 12V3M15 3v9l-2 3v4h7v-4l-3-3V3"/> @break
                        @case('route') <path d="M5 19c0-4 5-3 5-7s5-3 5-7"/><circle cx="5" cy="19" r="2"/><circle cx="15" cy="5" r="2"/> @break
                        @default <path d="M12 3 9 9l-6 1 4 5-1 6 6-3 6 3-1-6 4-5-6-1-3-6Z"/>
                    @endswitch
                </svg>
            </span>
            <h3 class="min-w-0 text-base font-extrabold text-slate-950 dark:text-white">{{ $track['label'] }}</h3>
        </div>

        <div class="shrink-0 text-right">
            @if ($track['earned_date'])
                <p class="text-[10px] font-extrabold uppercase tracking-wider text-slate-400">Earned</p>
                <time datetime="{{ $track['earned_date']->toDateString() }}" class="mt-0.5 block text-xs font-semibold text-slate-600 dark:text-slate-300">{{ $track['earned_date']->format('M j, Y') }}</time>
            @else
                <p class="text-xs font-medium text-slate-400">Not earned yet</p>
            @endif
        </div>
    </div>

    <div class="mt-4 min-h-7">
        @if ($highest)<x-accolade-badge :definition="$highest['definition']" size="sm" />@else<span class="text-sm font-semibold text-slate-500 dark:text-slate-400">No accolade earned yet</span>@endif
    </div>

    <div class="mt-auto pt-4">
        <div class="mb-2 flex flex-wrap items-center justify-between gap-2 text-sm">
            @if ($track['completed'])
                <span class="inline-flex items-center gap-1.5 font-bold {{ $theme['text'] }}"><svg class="size-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.4" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6"/></svg>Track complete</span>
            @else
                <span class="text-slate-600 dark:text-slate-300"><strong class="text-slate-950 dark:text-white">{{ $track['effective_value'] }} / {{ $track['next']['threshold'] }}</strong> toward {{ $track['next']['name'] }}</span>
            @endif
        </div>
        <x-accolades.progress-bar :value="$track['completed'] ? $maximum : $track['effective_value']" :maximum="$maximum" :percent="$track['progress_percent']" :bar-class="$theme['bar']" :label="$progressLabel" />
    </div>
</article>
