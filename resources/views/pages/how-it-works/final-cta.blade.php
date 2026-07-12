<section data-primary-hero class="relative px-4 pb-24 pt-20 text-center sm:px-6 sm:pb-28 sm:pt-24 lg:px-8 lg:pb-32 lg:pt-28">
    <div class="pointer-events-none absolute inset-0 bg-[radial-gradient(circle_at_center_top,rgba(99,102,241,0.24),transparent_58%)]" aria-hidden="true"></div>
    <div class="relative mx-auto max-w-5xl">
        <h1 class="text-4xl font-extrabold leading-[1.05] tracking-tight sm:text-5xl lg:text-6xl">
            Your community already has ideas.<br class="hidden sm:block"> Give them somewhere better to go.
        </h1>
        <p class="mx-auto mt-7 max-w-2xl text-lg leading-8 text-slate-300">Start with one creator board, one request, or one vote. The journey grows from there.</p>
        <div class="mx-auto mt-9 flex max-w-xl flex-col justify-center gap-3 sm:flex-row">
            <a href="{{ route('home') }}" class="inline-flex min-h-12 flex-1 items-center justify-center rounded-xl bg-indigo-500 px-6 py-3 font-extrabold transition hover:bg-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">Explore creators</a>
            @auth
                <a href="{{ route('dashboard') }}" class="inline-flex min-h-12 flex-1 items-center justify-center rounded-xl border border-slate-600 bg-slate-900 px-6 py-3 font-extrabold transition hover:border-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">Go to My Hub</a>
            @else
                <a href="{{ route('register') }}" class="inline-flex min-h-12 flex-1 items-center justify-center rounded-xl border border-slate-600 bg-slate-900 px-6 py-3 font-extrabold transition hover:border-indigo-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-950">Go to My Hub</a>
            @endauth
        </div>
        <p class="mt-9 text-lg font-bold text-slate-300">Fans suggest. Communities vote. Creators decide.</p>
        <div class="mx-auto mt-12 flex max-w-2xl flex-col items-center justify-center gap-4 rounded-3xl border border-slate-800 bg-slate-900/80 p-6 shadow-2xl shadow-indigo-950/30 sm:flex-row sm:gap-5 sm:p-7" aria-label="Community Guides organize ideas on a ranked creator board before the creator decides">
            <x-how-it-works.demo-avatars size="sm" :limit="3" />
            <span class="text-2xl font-black text-indigo-300 sm:hidden" aria-hidden="true">↓</span><span class="hidden text-2xl font-black text-indigo-300 sm:inline" aria-hidden="true">→</span>
            <span class="rounded-xl border border-indigo-400/30 px-4 py-3 text-base font-bold">Ranked creator board</span>
            <span class="text-2xl font-black text-emerald-300 sm:hidden" aria-hidden="true">↓</span><span class="hidden text-2xl font-black text-emerald-300 sm:inline" aria-hidden="true">→</span>
            <span class="rounded-xl bg-emerald-400/10 px-4 py-3 text-base font-bold text-emerald-300">Creator decision</span>
        </div>
    </div>
</section>
