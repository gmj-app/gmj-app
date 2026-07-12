<section aria-labelledby="workflow-title" class="px-4 py-20 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl">
        <div class="text-center"><p class="text-xs font-extrabold uppercase tracking-[0.2em] text-indigo-300">How it works</p><h2 id="workflow-title" class="mt-3 text-3xl font-extrabold sm:text-4xl">From a spark to a published journey</h2></div>
        @php
            $stages = [
                ['title' => 'A fan shares the spark', 'copy' => 'Fans can suggest a topic, idea, video, link, or alternate version in seconds.', 'type' => 'form'],
                ['title' => 'The community adds its signal', 'copy' => 'Guides use their limited votes to support the ideas they care about most.', 'type' => 'vote'],
                ['title' => 'The strongest ideas rise', 'copy' => 'The best-supported ideas rise through transparent community ranking.', 'type' => 'rank'],
                ['title' => 'The creator chooses the next move', 'copy' => 'Creators always retain final control over what they make, when they make it, and what they pass on.', 'type' => 'decide'],
            ];
        @endphp
        <ol data-workflow-stages class="relative mt-14 grid gap-6 lg:grid-cols-4">
            <span class="pointer-events-none absolute left-[12.5%] right-[12.5%] top-6 hidden h-px bg-gradient-to-r from-indigo-500 via-violet-500 to-emerald-500 lg:block" aria-hidden="true"></span>
            @foreach ($stages as $stage)
                <li class="relative border-l border-slate-700 pl-7 lg:border-l-0 lg:pl-0 lg:pt-12">
                    <span class="absolute -left-4 top-0 z-10 flex size-8 items-center justify-center rounded-full border border-indigo-400 bg-slate-950 text-xs font-black text-indigo-200 lg:left-1/2 lg:-top-4 lg:-translate-x-1/2">{{ $loop->iteration }}</span>
                    <article class="h-full rounded-3xl border border-slate-800 bg-slate-900 p-5 shadow-xl shadow-slate-950/30">
                        <div class="min-h-52 rounded-2xl border border-slate-700/70 bg-slate-950 p-4 text-xs text-slate-300" aria-label="Example {{ strtolower($stage['title']) }} interface">
                            @if ($stage['type'] === 'form')
                                <p class="font-bold text-white">Topic or idea</p><div class="mt-2 rounded-lg border border-slate-700 p-2.5">React to SB19’s live arrangement of MANA</div><p class="mt-3 font-bold text-white">Optional link</p><div class="mt-2 rounded-lg border border-slate-800 p-2.5 text-slate-500">youtube.com/…</div><p class="mt-3 font-bold text-white">Why this matters</p><div class="mt-2 rounded-lg border border-slate-800 p-2.5 text-slate-500">The harmonies deserve a closer listen.</div><span class="mt-3 block rounded-lg bg-indigo-500 p-2.5 text-center font-bold text-white">Submit request</span>
                            @elseif ($stage['type'] === 'vote')
                                <p class="font-bold text-white">MANA — live arrangement</p><p class="mt-2 leading-5 text-slate-400">A breakdown of the vocals and stage arrangement.</p><div class="mt-4 flex items-center justify-between"><x-how-it-works.demo-avatars size="xs" :limit="3" /><span class="text-base font-black text-indigo-300">↑ 42</span></div><span class="mt-4 block rounded-lg bg-indigo-500/15 p-2.5 text-center font-bold text-indigo-200">You voted</span>
                            @elseif ($stage['type'] === 'rank')
                                @foreach ([['8th', '+2 votes'], ['3rd', '+6 votes'], ['1st', 'Trending']] as $row)<div class="mb-3 flex items-center justify-between rounded-xl border border-slate-800 p-3"><span class="text-lg font-black text-white">{{ $row[0] }}</span><span class="font-bold text-emerald-300">{{ $row[1] }}</span></div>@endforeach<p class="text-center font-bold text-indigo-300">Moved up 4 places</p>
                            @else
                                <div class="flex flex-wrap gap-2">@foreach (['Approved', 'Scheduled', 'Recorded', 'Published', 'Passed'] as $status)<span class="rounded-full border border-slate-700 px-2 py-1 {{ $status === 'Published' ? 'bg-emerald-400/15 text-emerald-300' : 'text-slate-300' }}">{{ $status }}</span>@endforeach</div><div class="mt-5 rounded-xl border border-emerald-400/20 bg-emerald-400/5 p-3"><p class="font-bold text-emerald-300">Published</p><p class="mt-2 text-sm font-bold text-white">The community’s top request is live</p><p class="mt-2 text-slate-400">Watch the finished journey →</p></div>
                            @endif
                        </div>
                        <h3 class="mt-6 text-xl font-extrabold">{{ $stage['title'] }}</h3><p class="mt-3 leading-7 text-slate-400">{{ $stage['copy'] }}</p>
                    </article>
                </li>
            @endforeach
        </ol>
        <p class="mt-12 text-center text-xl font-bold text-slate-200">The community guides the journey. <span class="text-emerald-300">The creator owns the destination.</span></p>
    </div>
</section>
