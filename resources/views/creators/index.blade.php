<x-app-layout>
    <x-slot name="title">My Creator Pages</x-slot>

    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600">Creator Dashboard</p>
            <h2 class="text-xl font-semibold text-gray-800 dark:text-slate-50">My Creator Pages</h2>
        </div>
    </x-slot>

    <div class="py-12">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            <div class="mb-6 flex justify-end">
                <a href="{{ route('creators.create') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-950">
                    Set up another creator page
                </a>
            </div>

            @forelse ($creators as $creator)
                <article class="mb-4 flex flex-col gap-4 rounded-lg bg-white p-6 shadow-sm ring-1 ring-gray-200 dark:bg-slate-900 dark:ring-slate-800 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="flex flex-wrap items-center gap-2">
                            <h3 class="text-lg font-semibold text-gray-900 dark:text-slate-50">{{ $creator->display_name }}</h3>
                            <span class="rounded-full px-2.5 py-1 text-xs font-semibold {{ $creator->status === 'active' ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300' : 'bg-gray-100 text-gray-600 dark:bg-slate-800 dark:text-slate-300' }}">
                                {{ ucfirst($creator->status) }}
                            </span>
                        </div>
                        <p class="mt-1 text-sm text-gray-500 dark:text-slate-400">/{{ $creator->slug }}</p>
                    </div>

                    <div class="flex flex-wrap gap-3">
                        <a href="{{ route('creator.queue', $creator) }}" class="rounded-md bg-white px-4 py-2 text-sm font-semibold text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-100 dark:ring-slate-600 dark:hover:bg-slate-800">
                            View public page
                        </a>
                        <a href="{{ route('creators.dashboard', $creator) }}" class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">
                            Open dashboard
                        </a>
                    </div>
                </article>
            @empty
                <div class="rounded-lg bg-white p-10 text-center shadow-sm ring-1 ring-gray-200 dark:bg-slate-900 dark:ring-slate-800">
                    <h3 class="font-semibold text-gray-900 dark:text-slate-50">No creator pages yet</h3>
                    <p class="mt-2 text-sm text-gray-600 dark:text-slate-300">Creator pages you own will appear here.</p>
                </div>
            @endforelse
        </div>
    </div>
</x-app-layout>
