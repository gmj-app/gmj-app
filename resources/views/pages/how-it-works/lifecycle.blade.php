<section aria-labelledby="lifecycle-title" class="border-y border-slate-800 bg-slate-900/80 px-4 py-20 sm:px-6 lg:px-8">
    <div class="mx-auto max-w-7xl"><h2 id="lifecycle-title" class="text-center text-3xl font-extrabold sm:text-4xl">A request does not disappear after the vote.</h2>
        <ol data-lifecycle class="mt-12 grid gap-3 md:grid-cols-5">
            @foreach ([['Suggested','Idea shared'],['Community backed','42 votes'],['Approved','Creator selected'],['Scheduled','Coming soon'],['Published','Published 2 days ago']] as $step)
                <li class="relative rounded-2xl border {{ $loop->last ? 'border-emerald-400/40 bg-emerald-400/10' : 'border-slate-700 bg-slate-950' }} p-4"><span class="text-xs font-black uppercase tracking-wider {{ $loop->last ? 'text-emerald-300' : 'text-indigo-300' }}">{{ $step[0] }}</span><p class="mt-3 font-bold">{{ $step[1] }}</p>@unless($loop->last)<span class="absolute -right-2 top-1/2 z-10 hidden -translate-y-1/2 text-slate-500 md:block" aria-hidden="true">→</span>@endunless</li>
            @endforeach
        </ol>
        <p class="mx-auto mt-8 max-w-3xl text-center text-lg leading-8 text-slate-400">Guides can see what happened. Creators build a visible history. Communities learn what kind of participation makes an impact.</p>
    </div>
</section>
