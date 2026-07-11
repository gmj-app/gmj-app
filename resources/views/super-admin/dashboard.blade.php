<x-super-admin-layout title="Overview">
    <div class="grid gap-4 sm:grid-cols-2">
        <a href="{{ route('super-admin.ads.index') }}" class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <p class="text-sm font-bold text-slate-500">Homepage advertisements</p><p class="mt-2 text-4xl font-extrabold">{{ $advertisementCount }}</p><p class="mt-4 text-sm text-indigo-600 dark:text-indigo-300">Manage advertisements →</p>
        </a>
    </div>
</x-super-admin-layout>
