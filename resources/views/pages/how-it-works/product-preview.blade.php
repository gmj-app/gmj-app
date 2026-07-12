<div class="relative mx-auto max-w-xl" aria-label="Example ranked creator request board">
    <div class="absolute -inset-8 -z-10 bg-[radial-gradient(circle,rgba(99,102,241,0.2),transparent_65%)]" aria-hidden="true"></div>
    <div class="rounded-[2rem] border border-slate-700/80 bg-slate-900/95 p-4 shadow-2xl shadow-indigo-950/40 sm:p-5">
        <div class="flex items-center justify-between border-b border-slate-800 pb-4">
            <div><p class="text-xs font-bold uppercase tracking-[0.18em] text-indigo-300">Community journey board</p><p class="mt-1 font-bold">What should we make next?</p></div>
            <span class="rounded-full bg-emerald-400/10 px-3 py-1 text-xs font-bold text-emerald-300">Live signal</span>
        </div>
        <div class="mt-4 space-y-3">
            @foreach ([
                ['rank' => 1, 'title' => 'React to SB19’s live arrangement of MANA', 'votes' => 86, 'accent' => 'from-indigo-500 to-violet-700', 'chip' => 'Creator approved'],
                ['rank' => 2, 'title' => 'A deep dive into Filipino vocal harmonies', 'votes' => 71, 'accent' => 'from-cyan-500 to-indigo-700', 'chip' => null],
                ['rank' => 3, 'title' => 'First listen: modern OPM discoveries', 'votes' => 54, 'accent' => 'from-violet-500 to-fuchsia-800', 'chip' => 'You voted'],
            ] as $suggestion)
                <article class="grid grid-cols-[2rem_3.5rem_minmax(0,1fr)] items-center gap-3 rounded-2xl border border-slate-700/70 bg-slate-950/80 p-3">
                    <span class="text-center text-lg font-black text-slate-400">{{ $suggestion['rank'] }}</span>
                    <span class="aspect-square rounded-xl bg-gradient-to-br {{ $suggestion['accent'] }}" aria-hidden="true"></span>
                    <div class="min-w-0"><p class="truncate text-sm font-bold text-white">{{ $suggestion['title'] }}</p><div class="mt-2 flex flex-wrap items-center gap-2 text-xs"><span class="font-bold text-indigo-300">↑ {{ $suggestion['votes'] }} votes</span>@if ($suggestion['chip'])<span class="rounded-full {{ $suggestion['rank'] === 1 ? 'bg-emerald-400/10 text-emerald-300' : 'bg-indigo-400/10 text-indigo-300' }} px-2 py-1 font-bold">{{ $suggestion['chip'] }}</span>@endif</div>@if ($suggestion['rank'] === 1)<p class="mt-2 text-[11px] font-bold text-emerald-300">↗ Moved up 3 places today</p>@endif @if ($suggestion['rank'] < 3)<x-how-it-works.demo-avatars size="xs" :limit="$suggestion['rank'] === 1 ? 3 : 4" class="mt-1" />@endif</div>
                </article>
            @endforeach
        </div>
    </div>
    <div class="mt-4 rounded-2xl border border-emerald-400/30 bg-slate-900 p-4 shadow-xl sm:absolute sm:-bottom-8 sm:-right-6 sm:w-56" aria-label="Example creator decision controls">
        <p class="text-xs font-bold uppercase tracking-wider text-slate-400">Creator decision</p>
        <div class="mt-3 grid grid-cols-3 gap-2 text-center text-xs font-bold"><span class="rounded-lg bg-emerald-500 px-2 py-2 text-slate-950">Approve</span><span class="rounded-lg bg-indigo-500/20 px-2 py-2 text-indigo-200">Schedule</span><span class="rounded-lg bg-slate-800 px-2 py-2 text-slate-300">Pass</span></div>
    </div>
</div>
