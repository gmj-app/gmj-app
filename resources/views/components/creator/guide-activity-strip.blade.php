@props(['creator', 'header'])

@if ($header['context']['show_guide_activity'])
    @php($usage = $header['guide_activity'])
    <section aria-labelledby="guide-activity-title" class="rounded-2xl border border-indigo-200 bg-indigo-50/80 px-4 py-4 dark:border-indigo-900 dark:bg-indigo-950/30 sm:px-5">
        <div class="grid gap-4 md:grid-cols-[minmax(0,1fr)_2fr] md:items-center">
            <div><h2 id="guide-activity-title" class="font-extrabold text-slate-950 dark:text-white">Your activity with {{ $creator->display_name }}</h2><p class="mt-1 text-xs text-slate-600 dark:text-slate-400">Creator-specific Guide activity</p></div>
            <dl class="grid grid-cols-1 gap-2 sm:grid-cols-3">
                <div class="rounded-xl bg-white/80 px-3 py-2 dark:bg-slate-900/70"><dt class="text-xs font-bold text-slate-500 dark:text-slate-400">Favorite status</dt><dd class="mt-1 font-extrabold text-slate-950 dark:text-white">{{ $usage['is_favorited'] ? 'Favorited' : 'Not favorited' }}</dd></div>
                <div class="rounded-xl bg-white/80 px-3 py-2 dark:bg-slate-900/70"><dt class="text-xs font-bold text-slate-500 dark:text-slate-400">Requests</dt><dd class="mt-1 font-extrabold text-slate-950 dark:text-white">{{ $usage['suggestions_used'] }} / {{ $usage['suggestions_limit'] }} used</dd><p class="text-xs text-slate-500">{{ $usage['suggestions_remaining'] }} remaining</p></div>
                <div class="rounded-xl bg-white/80 px-3 py-2 dark:bg-slate-900/70"><dt class="text-xs font-bold text-slate-500 dark:text-slate-400">Votes</dt><dd class="mt-1 font-extrabold text-slate-950 dark:text-white">{{ $usage['votes_used'] }} / {{ $usage['votes_limit'] }} used</dd><p class="text-xs text-slate-500">{{ $usage['votes_remaining'] }} remaining</p></div>
            </dl>
        </div>
    </section>
@endif
