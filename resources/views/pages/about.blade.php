<x-public-layout title="How It Works | Guide My Journey">
    <section class="relative overflow-hidden px-4 py-14 sm:px-6 sm:py-20 lg:px-8 lg:py-24">
        <div class="pointer-events-none absolute inset-x-0 top-0 -z-10 mx-auto h-96 max-w-5xl bg-[radial-gradient(circle_at_top,rgba(99,102,241,0.16),transparent_65%)] dark:bg-[radial-gradient(circle_at_top,rgba(99,102,241,0.2),transparent_65%)]"></div>

        <div class="mx-auto max-w-6xl">
            <div class="mx-auto max-w-4xl text-center">
                <p class="text-xs font-extrabold uppercase tracking-[0.24em] text-indigo-600 dark:text-indigo-400 sm:text-sm">
                    How it works
                </p>
                <h1 class="mt-4 text-4xl font-extrabold leading-tight tracking-tight text-slate-950 dark:text-white sm:text-5xl lg:text-6xl">
                    Fans <span class="bg-gradient-to-r from-sky-500 via-indigo-500 to-violet-500 bg-clip-text text-transparent">suggest</span>.
                    Communities <span class="bg-gradient-to-r from-sky-500 via-indigo-500 to-violet-500 bg-clip-text text-transparent">vote</span>.
                    Creators <span class="bg-gradient-to-r from-sky-500 via-indigo-500 to-violet-500 bg-clip-text text-transparent">decide</span>.
                </h1>

                <p class="mx-auto mt-8 max-w-3xl text-base leading-7 text-slate-600 dark:text-slate-300 sm:text-lg sm:leading-8">
                    Guide My Journey turns scattered comments, DMs, and requests into one organized board creators can actually use.
                </p>
            </div>

            <section aria-label="How Guide My Journey works" class="mt-14 lg:mt-16">
                <div class="relative">
                    <div class="pointer-events-none absolute left-[16.66%] right-[16.66%] top-12 hidden h-px bg-gradient-to-r from-sky-300 via-indigo-400 to-violet-400 md:block dark:from-sky-700 dark:via-indigo-600 dark:to-violet-600" aria-hidden="true"></div>

                    <ol data-journey-infographic class="relative grid gap-5 md:grid-cols-3 md:gap-6">
                    @foreach ([
                        [
                            'number' => '01',
                            'title' => 'Fans suggest',
                            'copy' => 'Fans submit ideas, topics, videos, links, and questions.',
                            'iconClasses' => 'from-sky-500 to-indigo-600 shadow-sky-500/20',
                            'icon' => 'suggest',
                        ],
                        [
                            'number' => '02',
                            'title' => 'Communities vote',
                            'copy' => 'The best ideas rise as the community focuses the signal.',
                            'iconClasses' => 'from-indigo-500 to-violet-600 shadow-indigo-500/20',
                            'icon' => 'vote',
                        ],
                        [
                            'number' => '03',
                            'title' => 'Creators decide',
                            'copy' => 'Creators review the board and choose what to make next.',
                            'iconClasses' => 'from-violet-500 to-fuchsia-600 shadow-violet-500/20',
                            'icon' => 'decide',
                        ],
                    ] as $step)
                        <li class="relative flex min-w-0">
                            <article class="flex w-full min-w-0 flex-col items-start rounded-3xl border border-slate-200 bg-white p-6 shadow-sm transition duration-200 hover:-translate-y-0.5 hover:border-indigo-200 hover:shadow-xl dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-800 sm:p-7">
                                <div class="flex w-full items-center justify-between gap-4">
                                    <span class="relative z-10 flex size-14 items-center justify-center rounded-2xl bg-gradient-to-br text-white shadow-lg {{ $step['iconClasses'] }}">
                                        @if ($step['icon'] === 'suggest')
                                            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M8 10h8m-4-4v8M5 19l-1 3 4-2h9a4 4 0 0 0 4-4V7a4 4 0 0 0-4-4H7a4 4 0 0 0-4 4v9a4 4 0 0 0 2 3Z" />
                                            </svg>
                                        @elseif ($step['icon'] === 'vote')
                                            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 19V5m0 0-6 6m6-6 6 6M5 20h14" />
                                            </svg>
                                        @else
                                            <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                                <path stroke-linecap="round" stroke-linejoin="round" d="M9 6h11M9 12h11M9 18h11M4 6l1 1 2-2M4 12l1 1 2-2M4 18l1 1 2-2" />
                                            </svg>
                                        @endif
                                    </span>
                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-extrabold tracking-[0.18em] text-slate-500 dark:bg-slate-800 dark:text-slate-400">
                                        {{ $step['number'] }}
                                    </span>
                                </div>

                                <h3 class="mt-7 text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white">{{ $step['title'] }}</h3>
                                <p class="mt-3 text-base leading-7 text-slate-600 dark:text-slate-300">{{ $step['copy'] }}</p>
                            </article>
                        </li>
                    @endforeach
                    </ol>
                </div>
            </section>

            <p class="mx-auto mt-9 max-w-3xl text-center text-base font-semibold leading-7 text-slate-600 dark:text-slate-300 sm:text-lg">
                Voting guides the journey, but creators always stay in control.
            </p>

            <section class="mx-auto mt-14 max-w-4xl border-t border-slate-200 px-4 pt-10 text-center dark:border-slate-800 sm:mt-16 sm:pt-12">
                <h2 class="text-2xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-3xl">Start guiding a creator's journey</h2>
                <p class="mx-auto mt-3 max-w-xl text-base leading-7 text-slate-600 dark:text-slate-300">
                    Find a creator and add your signal to the board.
                </p>

                <div class="mt-6 flex flex-col justify-center gap-3 sm:flex-row">
                    <a href="{{ route('home') }}" class="inline-flex min-h-12 items-center justify-center rounded-full bg-indigo-600 px-6 py-3 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950">
                        Explore creators
                    </a>

                    @auth
                        <a href="{{ route('dashboard') }}" class="inline-flex min-h-12 items-center justify-center rounded-full border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-indigo-600 dark:hover:text-indigo-300 dark:focus-visible:ring-offset-slate-950">
                            My Hub
                        </a>
                    @else
                        <a href="{{ route('register') }}" class="inline-flex min-h-12 items-center justify-center rounded-full border border-slate-300 bg-white px-6 py-3 text-sm font-bold text-slate-700 transition hover:border-indigo-300 hover:text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-indigo-600 dark:hover:text-indigo-300 dark:focus-visible:ring-offset-slate-950">
                            Create free account
                        </a>
                    @endauth
                </div>
            </section>
        </div>
    </section>
</x-public-layout>
