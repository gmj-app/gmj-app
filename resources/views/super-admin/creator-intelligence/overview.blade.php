<x-creator-intelligence-layout title="Overview">
    @if ($channelCount === 0)
        <div class="ci-empty-state">
            <h2 class="text-xl font-extrabold">Creator Intelligence is installed</h2>
            <p class="mt-2 text-slate-600 dark:text-slate-300">No channel data has been added yet. Create a creator profile, then connect its first channel record.</p>
            <a href="{{ route('superadmin.creator-intelligence.profiles.create') }}" class="mt-5 inline-flex rounded-xl bg-indigo-600 px-4 py-2 font-bold text-white">Create a profile</a>
        </div>
    @else
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <div class="ci-card"><p class="ci-muted text-sm font-bold">Creator profiles</p><p class="mt-2 text-4xl font-extrabold">{{ $profileCount }}</p></div>
            <div class="ci-card"><p class="ci-muted text-sm font-bold">Creator channels</p><p class="mt-2 text-4xl font-extrabold">{{ $channelCount }}</p></div>
            <div class="ci-card"><p class="ci-muted text-sm font-bold">Active channel videos</p><p class="mt-2 text-4xl font-extrabold">{{ $analytics['coverage']['videos'] }}</p></div>
            <div class="ci-card"><p class="ci-muted text-sm font-bold">Median views</p><p class="mt-2 text-4xl font-extrabold">{{ $analytics['summary']['views']['median'] === null ? 'No data' : number_format($analytics['summary']['views']['median']) }}</p></div>
        </div>
        <div class="mt-6 flex flex-wrap gap-3"><a class="rounded-xl bg-indigo-600 px-4 py-2 font-bold text-white" href="{{ route('superadmin.creator-intelligence.analytics.index', ['creator_channel_id'=>$context->channel?->id]) }}">Open Analytics</a><a class="rounded-xl border px-4 py-2 font-bold" href="{{ route('superadmin.creator-intelligence.metadata-queue.index') }}">Review Metadata Queue</a><a class="rounded-xl border px-4 py-2 font-bold" href="{{ route('superadmin.creator-intelligence.imports.create') }}">Import New Data</a><a class="rounded-xl border px-4 py-2 font-bold" href="{{ route('superadmin.creator-intelligence.videos.index') }}">Browse Videos</a></div>
    @endif
</x-creator-intelligence-layout>
