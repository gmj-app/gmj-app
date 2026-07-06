<x-app-layout>
    <x-slot name="title">Creator Dashboard</x-slot>

    <x-slot name="header">
        @include('creators.partials.header')
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
            @if (session('success'))
                <div class="mb-6 rounded-md bg-green-50 p-4 text-sm font-medium text-green-800 ring-1 ring-green-200 dark:bg-emerald-950 dark:text-emerald-200 dark:ring-emerald-900">
                    {{ session('success') }}
                </div>
            @endif

            @include('creators.partials.navigation')

            <dl class="mt-6 grid grid-cols-1 gap-4 sm:grid-cols-2 xl:grid-cols-5">
                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <div class="flex min-h-12 items-center gap-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-indigo-100 bg-indigo-50 text-indigo-600 dark:border-indigo-400/20 dark:bg-indigo-500/10 dark:text-indigo-300 dark:shadow-[0_0_24px_rgba(99,102,241,0.08)]">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M6.75 6.75V5.625A1.875 1.875 0 0 1 8.625 3.75h6.75a1.875 1.875 0 0 1 1.875 1.875V6.75M5.625 6.75h12.75c1.036 0 1.875.84 1.875 1.875v9.75c0 1.036-.84 1.875-1.875 1.875H5.625a1.875 1.875 0 0 1-1.875-1.875v-9.75c0-1.036.84-1.875 1.875-1.875Z" />
                                <path stroke-linecap="round" d="M12 10.5v6M9 13.5h6" />
                            </svg>
                        </span>
                        <dt class="text-sm font-semibold leading-5 text-gray-700 dark:text-slate-200">Recommendations received</dt>
                    </div>
                    <dd class="mt-4 border-t border-dashed border-gray-200 pt-4 text-4xl font-bold tracking-tight text-gray-950 dark:border-slate-800 dark:text-slate-50">
                        {{ $stats['recommendations'] }}
                    </dd>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <div class="flex min-h-12 items-center gap-3">
                        <span class="relative flex size-11 shrink-0 items-center justify-center rounded-xl border border-amber-100 bg-amber-50 text-amber-600 dark:border-amber-400/20 dark:bg-amber-500/10 dark:text-amber-300 dark:shadow-[0_0_24px_rgba(245,158,11,0.08)]">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="12" r="8.25" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M12 7.5V12l3 1.875" />
                            </svg>
                            <span class="absolute -bottom-0.5 -right-0.5 size-2.5 rounded-full border-2 border-white bg-amber-500 dark:border-slate-900"></span>
                        </span>
                        <dt class="text-sm font-semibold leading-5 text-gray-700 dark:text-slate-200">Pending review</dt>
                    </div>
                    <dd class="mt-4 border-t border-dashed border-gray-200 pt-4 text-4xl font-bold tracking-tight text-gray-950 dark:border-slate-800 dark:text-slate-50">
                        {{ $stats['pending'] }}
                    </dd>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <div class="flex min-h-12 items-center gap-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-violet-100 bg-violet-50 text-violet-600 dark:border-violet-400/20 dark:bg-violet-500/10 dark:text-violet-300 dark:shadow-[0_0_24px_rgba(139,92,246,0.08)]">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="m4.5 16.5 5.25-5.25 3.75 3.75 6-7.5" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="M15 7.5h4.5V12" />
                            </svg>
                        </span>
                        <dt class="text-sm font-semibold leading-5 text-gray-700 dark:text-slate-200">Total votes</dt>
                    </div>
                    <dd class="mt-4 border-t border-dashed border-gray-200 pt-4 text-4xl font-bold tracking-tight text-gray-950 dark:border-slate-800 dark:text-slate-50">
                        {{ $stats['votes'] }}
                    </dd>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <div class="flex min-h-12 items-center gap-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-pink-100 bg-pink-50 text-pink-600 dark:border-pink-400/20 dark:bg-pink-500/10 dark:text-pink-300 dark:shadow-[0_0_24px_rgba(236,72,153,0.08)]">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.625c0 5.625-8.25 10.125-8.25 10.125S3.75 14.25 3.75 8.625A4.125 4.125 0 0 1 12 8.625a4.125 4.125 0 0 1 8.25 0Z" />
                            </svg>
                        </span>
                        <dt class="text-sm font-semibold leading-5 text-gray-700 dark:text-slate-200">Followers</dt>
                    </div>
                    <dd class="mt-4 border-t border-dashed border-gray-200 pt-4 text-4xl font-bold tracking-tight text-gray-950 dark:border-slate-800 dark:text-slate-50">
                        {{ $stats['followers'] }}
                    </dd>
                </div>

                <div class="rounded-2xl border border-gray-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <div class="flex min-h-12 items-center gap-3">
                        <span class="flex size-11 shrink-0 items-center justify-center rounded-xl border border-emerald-100 bg-emerald-50 text-emerald-600 dark:border-emerald-400/20 dark:bg-emerald-500/10 dark:text-emerald-300 dark:shadow-[0_0_24px_rgba(16,185,129,0.08)]">
                            <svg class="size-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="11.25" cy="11.25" r="7.5" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="m9.75 8.625 4.5 2.625-4.5 2.625v-5.25Z" />
                                <path stroke-linecap="round" stroke-linejoin="round" d="m16.5 18 1.5 1.5 3-3.375" />
                            </svg>
                        </span>
                        <dt class="text-sm font-semibold leading-5 text-gray-700 dark:text-slate-200">Published recommendations</dt>
                    </div>
                    <dd class="mt-4 border-t border-dashed border-gray-200 pt-4 text-4xl font-bold tracking-tight text-gray-950 dark:border-slate-800 dark:text-slate-50">
                        {{ $stats['published'] }}
                    </dd>
                </div>
            </dl>

            <section class="mt-8">
                <div class="flex items-center justify-between gap-4">
                    <div>
                        <h3 class="text-xl font-semibold text-gray-900 dark:text-slate-50">Needs action</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-slate-300">The latest recommendations waiting for review.</p>
                    </div>
                    <a href="{{ route('creators.recommendations.index', $creator) }}" class="text-sm font-semibold text-indigo-600 hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200">
                        View all recommendations
                    </a>
                </div>

                <div class="mt-4 overflow-hidden rounded-lg bg-white shadow-sm ring-1 ring-gray-200 dark:bg-slate-900 dark:ring-slate-800">
                    <div class="divide-y divide-gray-100 dark:divide-slate-800">
                        @forelse ($pendingRecommendations as $recommendation)
                            <article class="p-5 sm:p-6">
                                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                                    <div class="min-w-0">
                                        <h4 class="truncate font-semibold text-gray-900 dark:text-slate-50">{{ $recommendation->title }}</h4>

                                        @if ($recommendation->channel_title || $recommendation->artist)
                                            <p class="mt-1 text-sm text-gray-600 dark:text-slate-300">
                                                {{ $recommendation->channel_title ?: $recommendation->artist }}
                                            </p>
                                        @endif

                                        <div class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-gray-500 dark:text-slate-400">
                                            <span>Submitted {{ $recommendation->created_at->format('M j, Y') }}</span>
                                            <span>{{ $recommendation->totalVotes() }} {{ Str::plural('vote', $recommendation->totalVotes()) }}</span>
                                        </div>
                                    </div>

                                    <div class="flex flex-wrap items-center gap-2">
                                        <form method="POST" action="{{ route('creators.recommendations.status', [$creator, $recommendation]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="approved">
                                            <button class="rounded-md bg-indigo-600 px-3 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Approve</button>
                                        </form>

                                        <form method="POST" action="{{ route('creators.recommendations.status', [$creator, $recommendation]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <input type="hidden" name="status" value="passed">
                                            <button class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-100 dark:ring-slate-600 dark:hover:bg-slate-800">Pass</button>
                                        </form>

                                        <form method="POST" action="{{ route('creators.recommendations.hide', [$creator, $recommendation]) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-amber-700 ring-1 ring-amber-200 hover:bg-amber-50 dark:bg-amber-950/30 dark:text-amber-300 dark:ring-amber-700/70 dark:hover:bg-amber-950/50">Hide</button>
                                        </form>

                                        @if ($recommendation->youtube_url)
                                            <a href="{{ $recommendation->youtube_url }}" target="_blank" rel="noopener noreferrer" class="rounded-md bg-white px-3 py-2 text-sm font-semibold text-red-600 ring-1 ring-red-200 hover:bg-red-50 dark:bg-red-950/20 dark:text-red-300 dark:ring-red-700/70 dark:hover:bg-red-950/40">
                                                Open YouTube
                                            </a>
                                        @endif
                                    </div>
                                </div>
                            </article>
                        @empty
                            <div class="p-10 text-center">
                                <h4 class="font-semibold text-gray-900 dark:text-slate-50">You are all caught up</h4>
                                <p class="mt-1 text-sm text-gray-600 dark:text-slate-300">There are no pending recommendations to review.</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
