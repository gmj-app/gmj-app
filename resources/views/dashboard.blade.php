<x-app-layout>
    <x-slot name="title">My Hub</x-slot>

    <x-slot name="header">
        <div class="mx-auto min-w-0 max-w-5xl">
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">Guide My Journey</p>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-slate-50">My Hub</h2>
        </div>
    </x-slot>

    <div class="py-10 sm:py-12">
        <div class="px-4 sm:px-6 lg:px-8">
        <div class="mx-auto min-w-0 max-w-5xl">
            <section class="overflow-hidden rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                <div class="max-w-3xl">
                    <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">Your launchpad</p>
                    <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white sm:text-4xl">
                        Welcome back, {{ auth()->user()->publicName() }}
                    </h1>
                    <p class="mt-4 text-lg font-bold leading-7 text-slate-800 dark:text-slate-100 sm:text-xl">
                        Fans suggest. Communities vote. Creators decide.
                    </p>
                    <p class="mt-2 text-base leading-7 text-slate-600 dark:text-slate-300">
                        Use your resources to favorite creators, submit suggestions, and vote for ideas.
                    </p>
                </div>

                <dl class="mt-6 grid gap-3 sm:grid-cols-3">
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-950/50">
                        <dt class="text-sm font-semibold text-slate-500 dark:text-slate-400">Creator favorites</dt>
                        <dd class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white">
                            {{ $resources['creator_favorites_used'] }}
                            <span class="text-sm font-semibold text-slate-400">/ {{ $resources['creator_favorites_limit'] }}</span>
                        </dd>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-950/50">
                        <dt class="text-sm font-semibold text-slate-500 dark:text-slate-400">Active votes</dt>
                        <dd class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white">{{ $resources['active_upvotes'] }}</dd>
                    </div>
                    <div class="rounded-2xl border border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-950/50">
                        <dt class="text-sm font-semibold text-slate-500 dark:text-slate-400">Suggestions submitted</dt>
                        <dd class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white">{{ $resources['suggestions_submitted'] }}</dd>
                    </div>
                </dl>
            </section>

            <section class="mt-6 rounded-3xl border border-slate-200 bg-slate-50/70 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-950/40 sm:p-8">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[0.18em] text-amber-600 dark:text-amber-400">My Guide Activity</p>
                        <h2 class="mt-2 text-2xl font-extrabold text-slate-950 dark:text-white">Your creators and activity</h2>
                        <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">See what you’ve suggested and where your votes are currently allocated.</p>
                    </div>
                    @if ($favoriteCreators->isNotEmpty())
                        <a href="{{ route('home') }}" class="text-sm font-bold text-indigo-600 hover:text-indigo-500 dark:text-indigo-400">Find more creators</a>
                    @endif
                </div>

                @if ($favoriteCreators->isEmpty())
                    <div class="mt-6 rounded-2xl border border-dashed border-slate-300 bg-white px-5 py-8 text-center dark:border-slate-700 dark:bg-slate-900">
                        <h3 class="font-bold text-slate-950 dark:text-white">No favorite creators yet.</h3>
                        <p class="mt-2 text-sm text-slate-600 dark:text-slate-300">Favorite a creator to track your suggestions and votes here.</p>
                        <a href="{{ route('home') }}" class="mt-4 inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white hover:bg-indigo-500">Find creators</a>
                    </div>
                @else
                    <div class="mt-6 space-y-3">
                        @foreach ($favoriteCreators as $creator)
                            <x-dashboard.guide-activity-creator
                                :creator="$creator"
                                :active-votes="$activeVotesByCreator->get($creator->id, collect())"
                                :suggestions="$suggestionsByCreator->get($creator->id, collect())"
                            />
                        @endforeach
                    </div>
                @endif
            </section>

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
                            'Manage recommendation requests',
                            'Approve, schedule, pass, or publish suggestions',
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
                            'Submit suggestions',
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
