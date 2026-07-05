<x-app-layout>
    <x-slot name="title">Internal Tools</x-slot>

    <x-slot name="header">
        <div>
            <p class="text-sm font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">Internal</p>
            <h2 class="text-xl font-semibold leading-tight text-gray-800 dark:text-slate-50">Creator Tools</h2>
        </div>
    </x-slot>

    <div class="py-10 sm:py-12">
        <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="mb-6 rounded-2xl border border-indigo-200 bg-indigo-50 px-4 py-3 text-sm font-semibold text-indigo-800 dark:border-indigo-900 dark:bg-indigo-950/60 dark:text-indigo-200">
                    {{ session('status') }}
                </div>
            @endif

            <section class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900 sm:p-8">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <p class="text-xs font-extrabold uppercase tracking-[0.2em] text-indigo-600 dark:text-indigo-400">Restricted tools</p>
                        <h1 class="mt-3 text-3xl font-extrabold tracking-tight text-slate-950 dark:text-white">Creator operations</h1>
                        <p class="mt-3 max-w-2xl text-sm leading-6 text-slate-600 dark:text-slate-300">
                            Tools in this area can change creator-platform data. Access is limited to approved internal users.
                        </p>
                    </div>
                </div>

                <div class="mt-8 grid gap-4 sm:grid-cols-2">
                    <a href="{{ route('tools.youtube.index') }}" class="group rounded-2xl border border-slate-200 bg-slate-50 p-5 transition hover:border-indigo-300 hover:bg-indigo-50 dark:border-slate-700 dark:bg-slate-950/50 dark:hover:border-indigo-500/60 dark:hover:bg-indigo-950/30">
                        <div class="flex items-start justify-between gap-4">
                            <div>
                                <h2 class="text-lg font-bold text-slate-950 dark:text-white">YouTube descriptions</h2>
                                <p class="mt-2 text-sm leading-6 text-slate-600 dark:text-slate-300">
                                    Preview and apply bulk append or exact replacement updates to uploaded video descriptions.
                                </p>
                            </div>
                            <span class="rounded-full bg-white px-3 py-1 text-xs font-bold text-indigo-700 ring-1 ring-indigo-200 dark:bg-slate-900 dark:text-indigo-300 dark:ring-indigo-900">Open</span>
                        </div>
                    </a>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
