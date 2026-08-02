@props(['title'])
<x-public-layout :title="$title.' | Creator Intelligence'">
    @php
        $routeSubject = request()->route('subject');
        $routeContentItem = request()->route('contentItem');
        $routeVideo = request()->route('creatorVideo');
        $routeBatch = request()->route('importBatch');
        $configuredChannel = request()->filled('creator_channel_id')
            ? \App\Models\CreatorChannel::find(request()->integer('creator_channel_id'))
            : ($routeSubject?->creatorChannel ?? $routeContentItem?->creatorChannel ?? $routeVideo?->channel ?? $routeBatch?->channel);
        $navigationLabels = \App\Support\CreatorIntelligenceLabels::for($configuredChannel);
    @endphp
    <div data-creator-intelligence class="ci-page mx-auto max-w-7xl px-4 py-10 sm:px-6 lg:px-8">
        <div class="mb-8 border-b border-slate-200 pb-6 dark:border-slate-800">
            <p class="text-sm font-bold uppercase tracking-wider text-indigo-600 dark:text-indigo-300">SuperAdmin</p>
            <h1 class="mt-1 text-3xl font-extrabold">Creator Intelligence</h1>
            <nav aria-label="Creator Intelligence" class="ci-tabs mt-5 text-sm font-bold">
                @foreach ([
                    'overview' => ['Overview', route('superadmin.creator-intelligence.overview')],
                    'analytics' => ['Analytics', route('superadmin.creator-intelligence.analytics.index')],
                    'videos' => ['Videos', route('superadmin.creator-intelligence.videos.index')],
                    'metadata-queue' => ['Metadata Queue', route('superadmin.creator-intelligence.metadata-queue.index')],
                    'metadata-suggestions' => ['Metadata Suggestions', route('superadmin.creator-intelligence.metadata-suggestions.index')],
                    'subjects' => [$navigationLabels->subjectPlural(), route('superadmin.creator-intelligence.subjects.index', array_filter(['creator_channel_id'=>$configuredChannel?->id]))],
                    'content-items' => [$navigationLabels->contentItemPlural(), route('superadmin.creator-intelligence.content-items.index', array_filter(['creator_channel_id'=>$configuredChannel?->id]))],
                    'profiles' => ['Creator Profiles', route('superadmin.creator-intelligence.profiles.index')],
                    'channels' => ['Creator Channels', route('superadmin.creator-intelligence.channels.index')],
                    'imports' => ['Imports', route('superadmin.creator-intelligence.imports.index')],
                ] as $key => [$label, $url])
                    <a href="{{ $url }}" class="rounded-xl border px-4 py-2 outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 {{ request()->routeIs('superadmin.creator-intelligence.'.$key.'*') ? 'border-indigo-500 bg-indigo-600 text-white' : 'border-slate-300 bg-white text-slate-800 hover:bg-slate-100 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-100 dark:hover:bg-slate-800' }}">{{ $label }}</a>
                @endforeach
            </nav>
        </div>
        @if (session('success'))<div role="status" class="ci-alert-success mb-6">{{ session('success') }}</div>@endif
        @if ($errors->any())<div role="alert" class="ci-alert-danger mb-6"><p>Correct the following errors:</p><ul class="mt-2 list-disc pl-5">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>@endif
        {{ $slot }}
    </div>
</x-public-layout>
