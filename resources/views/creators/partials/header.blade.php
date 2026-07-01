<div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
    <a
        href="{{ route('creator.queue', $creator) }}"
        aria-label="View {{ $creator->display_name }} public page"
        class="group inline-flex min-w-0 cursor-pointer items-center gap-3 self-start rounded-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900"
    >
        <x-creator-avatar
            :creator="$creator"
            size="md"
            class="ring-1 ring-slate-200 transition group-hover:ring-indigo-400 group-focus-visible:ring-indigo-500 dark:ring-slate-700 dark:group-hover:ring-indigo-400"
        />

        <span class="min-w-0">
            <span class="block text-sm font-semibold uppercase tracking-wide text-indigo-600 dark:text-indigo-300">Creator Dashboard</span>
            <span class="block truncate text-xl font-semibold leading-tight text-gray-800 transition group-hover:text-indigo-700 group-focus-visible:text-indigo-700 dark:text-slate-50 dark:group-hover:text-indigo-200 dark:group-focus-visible:text-indigo-200">
                {{ $creator->display_name }}
            </span>
            @isset($section)
                <span class="mt-0.5 block text-sm text-gray-500 dark:text-slate-400">{{ $section }}</span>
            @endisset
        </span>
    </a>

    <a
        href="{{ route('creators.settings.edit', $creator) }}"
        class="inline-flex min-h-11 items-center self-start rounded-lg px-3 text-sm font-semibold text-gray-600 hover:bg-gray-100 hover:text-gray-900 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white sm:self-auto"
    >
        Settings
    </a>
</div>
