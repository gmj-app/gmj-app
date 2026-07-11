@props(['changelog'])

<div class="max-h-[62vh] overflow-y-auto pr-1">
    <div>
        <h3 class="text-lg font-semibold text-slate-950 dark:text-white">Recent Updates</h3>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">See what has changed during beta testing.</p>
    </div>

    @if (! $changelog['available'])
        <p class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">Change log is temporarily unavailable.</p>
    @elseif ($changelog['entries']->isEmpty())
        <p class="mt-5 rounded-2xl border border-dashed border-slate-300 bg-slate-50 px-5 py-8 text-center text-sm font-semibold text-slate-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-400">No recent updates are available yet.</p>
    @else
        <div class="mt-5 space-y-6">
            @foreach ($changelog['groups'] as $entries)
                <section>
                    <h4 class="text-xs font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400">{{ $entries->first()['date']->format('M j, Y') }}</h4>
                    <ul class="mt-2 divide-y divide-slate-200 border-y border-slate-200 dark:divide-slate-800 dark:border-slate-800">
                        @foreach ($entries as $entry)
                            <li class="py-3 text-sm leading-6 text-slate-700 dark:text-slate-200">{{ $entry['subject'] }}</li>
                        @endforeach
                    </ul>
                </section>
            @endforeach
        </div>
    @endif
</div>
