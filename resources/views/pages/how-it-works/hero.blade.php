<section class="relative px-4 pb-24 pt-16 sm:px-6 sm:pt-20 lg:px-8 lg:pb-32 lg:pt-24">
    <div class="pointer-events-none absolute inset-x-0 top-0 h-[42rem] bg-[radial-gradient(circle_at_25%_15%,rgba(79,70,229,0.18),transparent_40%)]" aria-hidden="true"></div>
    <div class="relative mx-auto grid max-w-7xl gap-16 lg:grid-cols-[minmax(0,1fr)_minmax(28rem,0.9fr)] lg:items-center">
        <div>
            <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-indigo-300">Built for creators and the communities behind them</p>
            <h1 class="mt-5 max-w-3xl text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl">Turn <span class="bg-gradient-to-r from-sky-400 to-violet-400 bg-clip-text text-transparent">fan requests</span> into a <span class="text-emerald-300">content roadmap.</span></h1>
            <p class="mt-6 max-w-2xl text-lg leading-8 text-slate-300">Guide My Journey gives every suggestion a place, every fan a voice, and every creator a clear view of what their community wants next.</p>
            <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                <a href="{{ route('home') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl bg-indigo-500 px-6 py-3 text-sm font-extrabold text-white transition hover:bg-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">Explore creator boards</a>
                @auth<a href="{{ route('dashboard') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-900 px-6 py-3 text-sm font-extrabold text-white hover:border-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300">Open My Hub</a>@else<a href="{{ route('register') }}" class="inline-flex min-h-12 items-center justify-center rounded-xl border border-slate-700 bg-slate-900 px-6 py-3 text-sm font-extrabold text-white hover:border-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300">Open My Hub</a>@endauth
            </div>
            <p class="mt-4 text-sm font-semibold text-slate-400">Free to join <span aria-hidden="true">·</span> Suggest in seconds <span aria-hidden="true">·</span> Creators stay in control</p>
            <div class="mt-9 rounded-2xl border border-slate-800 bg-slate-900/70 p-4 sm:p-5">
                <dl class="grid grid-cols-3 gap-3">@foreach (['Guides joined', 'Creator boards', 'Community votes'] as $metric)<div><dd class="text-xl font-black text-white">—</dd><dt class="mt-1 text-xs font-semibold text-slate-400">{{ $metric }}</dt></div>@endforeach</dl>
                <div class="mt-5 flex flex-wrap items-center gap-3 border-t border-slate-800 pt-4"><x-how-it-works.demo-avatars /><span class="rounded-full border border-amber-400/30 bg-amber-400/10 px-2.5 py-1 text-xs font-bold text-amber-300">Founders —</span><span class="rounded-full border border-slate-600 bg-slate-800 px-2.5 py-1 text-xs font-bold text-slate-200">OGs —</span><span class="text-xs text-slate-400">and more…</span></div>
            </div>
        </div>
        @include('pages.how-it-works.product-preview')
    </div>
</section>

<section class="px-4 py-20 text-center sm:px-6 lg:px-8">
    <h2 class="mx-auto max-w-4xl text-3xl font-extrabold tracking-tight sm:text-4xl">No more digging through comments, DMs, spreadsheets, or forgotten links.</h2>
    <p class="mx-auto mt-5 max-w-2xl text-lg leading-8 text-slate-400">One organized board shows what your community wants and why it matters.</p>
</section>
