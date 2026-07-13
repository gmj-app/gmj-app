<x-app-layout>
    <x-slot name="title">My Hub</x-slot>

    <div class="py-10 sm:py-12">
        <div class="px-4 sm:px-6 lg:px-8">
        <div class="mx-auto min-w-0 max-w-5xl">
            <section class="grid min-w-0 gap-5 overflow-hidden rounded-3xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:px-6 sm:py-5 lg:grid-cols-[minmax(0,1fr)_minmax(25rem,1.15fr)] lg:items-center lg:gap-6">
                <div class="min-w-0">
                    <h1 class="text-2xl font-semibold tracking-tight text-slate-950 dark:text-white md:text-3xl">
                        Welcome back, {{ auth()->user()->publicName() }}
                    </h1>
                    <p class="mt-2 text-sm font-medium leading-6 text-slate-600 dark:text-slate-300 md:text-base">
                        Fans suggest. Communities vote. Creators decide.
                    </p>
                </div>

                <dl aria-label="Plan resource capacity" class="grid min-w-0 grid-cols-1 divide-y divide-slate-200 overflow-hidden rounded-xl border border-slate-200 bg-slate-50/80 dark:divide-slate-700 dark:border-slate-700 dark:bg-slate-950/50 sm:grid-cols-3 sm:divide-x sm:divide-y-0">
                    <div aria-label="{{ $resources['creator_favorites_used'] }} of {{ $resources['creator_favorites_limit'] }} favorite creator slots used" class="min-w-0 px-3 py-3 text-center sm:px-4">
                        <dd class="text-xl font-semibold leading-none text-slate-950 dark:text-white">
                            {{ $resources['creator_favorites_used'] }}
                            <span class="text-sm font-medium text-slate-400">/ {{ $resources['creator_favorites_limit'] }}</span>
                        </dd>
                        <dt class="mt-1.5 text-xs font-medium leading-4 text-slate-500 dark:text-slate-400">Favorite creators used</dt>
                    </div>
                    <div aria-label="{{ $resources['votes_per_creator'] }} votes available per creator" class="min-w-0 px-3 py-3 text-center sm:px-4">
                        <dd class="text-xl font-semibold leading-none text-slate-950 dark:text-white">{{ $resources['votes_per_creator'] }}</dd>
                        <dt class="mt-1.5 text-xs font-medium leading-4 text-slate-500 dark:text-slate-400">Votes per creator</dt>
                    </div>
                    <div aria-label="{{ $resources['requests_per_creator'] }} requests available per creator" class="min-w-0 px-3 py-3 text-center sm:px-4">
                        <dd class="text-xl font-semibold leading-none text-slate-950 dark:text-white">{{ $resources['requests_per_creator'] }}</dd>
                        <dt class="mt-1.5 text-xs font-medium leading-4 text-slate-500 dark:text-slate-400">Requests per creator</dt>
                    </div>
                </dl>
            </section>

            @php($hasActivity = $activitySummary['active_vote_count'] > 0 || $activitySummary['suggestion_count'] > 0)
            <a
                href="{{ $hasActivity ? route('activity.index') : route('home') }}"
                aria-label="{{ $hasActivity ? 'View your votes, requests, and published activity' : 'Find creators and start your activity history' }}"
                class="group mt-6 flex min-w-0 flex-col gap-4 rounded-2xl border border-slate-200 bg-slate-900 p-5 text-white shadow-sm transition hover:border-emerald-400 hover:bg-slate-800 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-500 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:hover:border-emerald-500 sm:flex-row sm:items-center sm:justify-between sm:p-6"
            >
                <span class="flex min-w-0 items-start gap-4">
                    <span class="flex size-11 shrink-0 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-300" aria-hidden="true">
                        <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M5 6h14M5 12h14M5 18h9" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="m17 17 2 2 4-5" />
                        </svg>
                    </span>
                    <span class="min-w-0">
                        <span class="block text-xs font-extrabold uppercase tracking-[0.18em] text-emerald-300">My Activity</span>
                        <span class="mt-1 block text-lg font-extrabold">Your votes and requests</span>
                        @if ($hasActivity)
                            <span class="mt-1 block text-sm font-semibold text-slate-200">
                                {{ $activitySummary['active_vote_count'] }} active {{ Str::plural('vote', $activitySummary['active_vote_count']) }}
                                <span aria-hidden="true">&middot;</span>
                                {{ $activitySummary['suggestion_count'] }} {{ Str::plural('request', $activitySummary['suggestion_count']) }}
                                <span aria-hidden="true">&middot;</span>
                                {{ $activitySummary['published_count'] }} published
                            </span>
                            <span class="mt-2 block text-sm leading-6 text-slate-400">See where your votes are allocated and track what happened to your requests.</span>
                        @else
                            <span class="mt-1 block text-sm font-semibold text-slate-200">No activity yet</span>
                            <span class="mt-2 block text-sm leading-6 text-slate-400">Favorite a creator, submit a request, or cast a vote to start building your activity history.</span>
                        @endif
                    </span>
                </span>
                <span class="inline-flex min-h-11 shrink-0 items-center gap-2 self-start rounded-xl bg-emerald-400 px-4 py-2 text-sm font-extrabold text-slate-950 transition group-hover:bg-emerald-300 sm:self-center">
                    {{ $hasActivity ? 'View My Activity' : 'Find creators' }}
                    <span aria-hidden="true">&rarr;</span>
                </span>
            </a>

            <section class="mt-6 grid gap-6 lg:grid-cols-2">
                <article class="flex min-w-0 flex-col rounded-3xl border border-indigo-200 bg-gradient-to-br from-indigo-50 to-white p-6 shadow-sm dark:border-indigo-900 dark:from-indigo-950/60 dark:to-slate-900 sm:p-8">
                    <span class="flex size-12 items-center justify-center rounded-2xl border border-indigo-200 bg-white text-indigo-600 shadow-sm dark:border-indigo-800 dark:bg-indigo-950 dark:text-indigo-300">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <rect x="4" y="5" width="16" height="14" rx="3" />
                            <path stroke-linecap="round" d="M8 10h8M8 14h5" />
                        </svg>
                    </span>

                    <h2 class="mt-5 text-2xl font-extrabold text-slate-950 dark:text-white">I'm a Creator</h2>
                    <p class="mt-3 leading-7 text-slate-600 dark:text-slate-300">
                        Create your creator page so fans can suggest, vote, and help guide what you make next.
                    </p>

                    <ul class="mt-5 space-y-3 text-sm font-medium text-slate-700 dark:text-slate-200">
                        @foreach ([
                            'Manage community requests',
                            'Approve, schedule, pass, or publish requests',
                            'Let your community vote on what matters most',
                            'Customize your creator profile',
                        ] as $benefit)
                            <li class="flex gap-3">
                                <svg class="mt-0.5 size-5 shrink-0 text-indigo-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                                </svg>
                                <span>{{ $benefit }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-auto pt-7">
                        @if ($ownedCreators->count() === 1)
                            <a href="{{ route('creators.dashboard', $ownedCreators->first()) }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900 sm:w-auto">
                                Manage creator page
                            </a>
                        @elseif ($ownedCreators->count() > 1)
                            <a href="{{ route('creators.index') }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900 sm:w-auto">
                                Manage creator pages
                            </a>
                        @else
                            <a href="{{ route('creators.create') }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-indigo-600 px-5 py-3 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900 sm:w-auto">
                                Set up creator page
                            </a>
                        @endif
                    </div>
                </article>

                <article class="flex min-w-0 flex-col rounded-3xl border border-amber-200 bg-gradient-to-br from-amber-50 to-white p-6 shadow-sm dark:border-amber-900 dark:from-amber-950/40 dark:to-slate-900 sm:p-8">
                    <span class="flex size-12 items-center justify-center rounded-2xl border border-amber-200 bg-white text-amber-600 shadow-sm dark:border-amber-800 dark:bg-amber-950 dark:text-amber-300">
                        <svg class="size-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m12 3.75 2.475 5.016 5.535.804-4.005 3.904.946 5.512L12 16.383l-4.951 2.603.946-5.512L3.99 9.57l5.535-.804L12 3.75Z" />
                        </svg>
                    </span>

                    <h2 class="mt-5 text-2xl font-extrabold text-slate-950 dark:text-white">I'm a Guide</h2>
                    <p class="mt-3 leading-7 text-slate-600 dark:text-slate-300">
                        Favorite creators, suggest ideas or links, and vote for what you want to see next.
                    </p>

                    <ul class="mt-5 space-y-3 text-sm font-medium text-slate-700 dark:text-slate-200">
                        @foreach ([
                            'Favorite creators you follow',
                            'Submit requests',
                            'Vote for active ideas',
                            'Track your resources',
                        ] as $benefit)
                            <li class="flex gap-3">
                                <svg class="mt-0.5 size-5 shrink-0 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="m5 12 4 4L19 6" />
                                </svg>
                                <span>{{ $benefit }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="mt-auto pt-7">
                        <a href="{{ route('home') }}" class="inline-flex min-h-12 w-full items-center justify-center rounded-xl bg-amber-500 px-5 py-3 text-sm font-bold text-slate-950 shadow-sm hover:bg-amber-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-amber-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900 sm:w-auto">
                            Explore creators
                        </a>
                    </div>
                </article>
            </section>

        </div>
        </div>
    </div>
</x-app-layout>
