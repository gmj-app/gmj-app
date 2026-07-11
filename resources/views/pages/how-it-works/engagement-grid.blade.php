<section aria-labelledby="engagement-title" class="px-4 py-20 sm:px-6 lg:px-8"><div class="mx-auto max-w-7xl"><h2 id="engagement-title" class="text-center text-3xl font-extrabold sm:text-4xl">Participation with purpose</h2>
    <div class="mt-12 grid gap-5 md:grid-cols-2">
        @foreach ([
            ['Votes carry weight','Guides cannot vote endlessly. Limited allocations make support more intentional.','votes'],
            ['People, not anonymous numbers','See who suggested an idea and who helped it rise.','people'],
            ['No algorithm chooses for you','Rankings reveal demand. Creators make the final call.','decisions'],
            ['Every journey builds history','Published ideas become part of the creator’s archive and the Guide’s activity.','history'],
        ] as $tile)
            <article class="rounded-3xl border border-slate-800 bg-slate-900 p-6 sm:p-8"><div class="mb-7 flex min-h-20 items-center rounded-2xl border border-slate-700/70 bg-slate-950 p-4" aria-hidden="true">
                @if($tile[2] === 'votes')<div class="grid w-full grid-cols-[2.75rem_1fr_2.75rem] items-center gap-2 text-center"><span class="rounded-xl bg-slate-800 p-3">−</span><span class="font-black">3/3 votes</span><span class="rounded-xl bg-indigo-500 p-3">+</span></div>
                @elseif($tile[2] === 'people')<x-how-it-works.demo-avatars size="md" />
                @elseif($tile[2] === 'decisions')<div class="grid w-full grid-cols-3 gap-2 text-center text-xs font-bold"><span class="rounded-lg bg-emerald-500 p-3 text-slate-950">Approve</span><span class="rounded-lg bg-indigo-500/20 p-3 text-indigo-200">Schedule</span><span class="rounded-lg bg-slate-800 p-3">Pass</span></div>
                @else<div class="flex w-full items-center justify-between gap-2 text-xs font-bold"><span>Suggested</span><span>→</span><span>Approved</span><span>→</span><span class="text-emerald-300">Published</span></div>@endif
            </div><h3 class="text-2xl font-extrabold">{{ $tile[0] }}</h3><p class="mt-3 leading-7 text-slate-400">{{ $tile[1] }}</p></article>
        @endforeach
    </div>
</div></section>
