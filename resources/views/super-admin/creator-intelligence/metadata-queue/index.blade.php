<x-creator-intelligence-layout title="Metadata Queue">
    @php
        $controlClasses = 'mt-1 block min-h-11 w-full rounded-xl border-slate-300 bg-white text-slate-950 placeholder:text-slate-500 focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-slate-100 dark:placeholder:text-slate-400';
        $advancedFiltersActive = request()->filled('creator_profile_id') || request()->filled('min_completion') || request()->filled('max_completion') || request()->boolean('missing_content_item') || request()->boolean('title_unreviewed') || request()->boolean('thumbnail_unreviewed') || request()->boolean('editorial_unreviewed') || request()->filled('sort') || request()->filled('direction');
        $subjectOptions = $subjects->map(fn ($subject) => ['id'=>$subject->id, 'name'=>$subject->name, 'channel_id'=>$subject->creator_channel_id, 'channel_label'=>$subject->creatorChannel->subject_label]);
    @endphp

    <div class="mb-5">
        <h2 class="text-2xl font-extrabold">Metadata Queue</h2>
        <p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Find videos, review classification status, and apply safe metadata updates to selected rows.</p>
    </div>

    <form method="GET" class="mb-6 rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
        <div class="grid items-end gap-4 lg:grid-cols-12">
            <label class="font-bold lg:col-span-4">Search
                <input type="search" name="search" value="{{ request('search') }}" placeholder="Search video title, ID, subject, or content item" class="{{ $controlClasses }}">
            </label>
            <label class="font-bold lg:col-span-2">Creator Channel
                <select name="creator_channel_id" class="{{ $controlClasses }}"><option value="">All channels</option>@foreach($channels as $channel)<option value="{{ $channel->id }}" @selected(request('creator_channel_id') == $channel->id)>{{ $channel->channel_name }}</option>@endforeach</select>
            </label>
            <label class="font-bold lg:col-span-2">Completion Status
                <select name="status" class="{{ $controlClasses }}"><option value="">All statuses</option>@foreach(['not_started','in_progress','complete'] as $status)<option value="{{ $status }}" @selected(request('status') === $status)>{{ str($status)->replace('_',' ')->title() }}</option>@endforeach</select>
            </label>
            <label class="flex min-h-11 items-center gap-2 rounded-xl border border-slate-300 px-3 text-sm font-bold dark:border-slate-700 lg:col-span-2"><input type="checkbox" name="missing_subject" value="1" @checked(request()->boolean('missing_subject')) class="size-4 rounded border-slate-400 bg-white text-indigo-600 focus:ring-indigo-500 dark:border-slate-500 dark:bg-slate-950"> Missing {{ strtolower($subjectLabel) }}</label>
            <div class="flex gap-2 lg:col-span-2"><button class="min-h-11 flex-1 rounded-xl bg-indigo-600 px-4 py-2 font-bold text-white outline-none hover:bg-indigo-500 focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-slate-900">Apply Filters</button><a href="{{ route('superadmin.creator-intelligence.metadata-queue.index') }}" class="inline-flex min-h-11 items-center justify-center rounded-xl border border-slate-300 px-4 py-2 font-bold text-slate-800 outline-none hover:bg-slate-100 focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-slate-700 dark:text-slate-100 dark:hover:bg-slate-800">Clear Filters</a></div>
        </div>
        <details class="mt-4 rounded-xl border border-slate-200 p-4 dark:border-slate-700" @if($advancedFiltersActive) open @endif>
            <summary class="cursor-pointer rounded font-bold outline-none focus-visible:ring-2 focus-visible:ring-indigo-500">Additional filters and sorting</summary>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <label class="font-bold">Creator Profile<select name="creator_profile_id" class="{{ $controlClasses }}"><option value="">All profiles</option>@foreach($profiles as $profile)<option value="{{ $profile->id }}" @selected(request('creator_profile_id') == $profile->id)>{{ $profile->display_name }}</option>@endforeach</select></label>
                <label class="font-bold">Minimum Completion<input type="number" min="0" max="100" name="min_completion" value="{{ request('min_completion') }}" class="{{ $controlClasses }}"></label>
                <label class="font-bold">Maximum Completion<input type="number" min="0" max="100" name="max_completion" value="{{ request('max_completion') }}" class="{{ $controlClasses }}"></label>
                <label class="font-bold">Sort By<select name="sort" class="{{ $controlClasses }}">@foreach(['metadata_completion_percentage'=>'Completion','published_at'=>'Published At','title'=>'Video Title'] as $value=>$label)<option value="{{ $value }}" @selected(request('sort','metadata_completion_percentage')===$value)>{{ $label }}</option>@endforeach</select></label>
                <label class="font-bold">Sort Direction<select name="direction" class="{{ $controlClasses }}"><option value="asc" @selected(request('direction','asc')==='asc')>Ascending</option><option value="desc" @selected(request('direction')==='desc')>Descending</option></select></label>
                @foreach(['missing_content_item'=>'Missing '.strtolower($contentItemLabel),'title_unreviewed'=>'Title metadata not reviewed','thumbnail_unreviewed'=>'Thumbnail metadata not reviewed','editorial_unreviewed'=>'Editorial metadata not reviewed'] as $name=>$label)
                    <label class="flex min-h-11 items-center gap-2 rounded-xl border border-slate-300 px-3 text-sm font-bold dark:border-slate-700"><input type="checkbox" name="{{ $name }}" value="1" @checked(request()->boolean($name)) class="size-4 rounded border-slate-400 bg-white text-indigo-600 focus:ring-indigo-500 dark:border-slate-500 dark:bg-slate-950"> {{ $label }}</label>
                @endforeach
            </div>
        </details>
    </form>

    @if(!$subjectsExist && $totalVideos !== 0)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-amber-300 bg-amber-50 p-4 text-amber-950 dark:border-amber-700 dark:bg-amber-950/40 dark:text-amber-100"><p>No {{ strtolower($subjectLabel) }} records have been created for the selected channel.</p><a href="{{ route('superadmin.creator-intelligence.subjects.create', array_filter(['creator_channel_id'=>$selectedChannel?->id])) }}" class="rounded-lg bg-amber-900 px-3 py-2 font-bold text-white outline-none focus-visible:ring-2 focus-visible:ring-amber-500">Create {{ $subjectLabel }}</a></div>
    @endif
    @if(!$contentItemsExist && $totalVideos !== 0)
        <div class="mb-4 flex flex-wrap items-center justify-between gap-3 rounded-xl border border-sky-300 bg-sky-50 p-4 text-sky-950 dark:border-sky-700 dark:bg-sky-950/40 dark:text-sky-100"><p>No {{ strtolower($contentItemLabel) }} records have been created for the selected channel.</p><a href="{{ route('superadmin.creator-intelligence.content-items.create', array_filter(['creator_channel_id'=>$selectedChannel?->id])) }}" class="rounded-lg bg-sky-900 px-3 py-2 font-bold text-white outline-none focus-visible:ring-2 focus-visible:ring-sky-500">Create {{ $contentItemLabel }}</a></div>
    @endif

    <form method="POST" action="{{ route('superadmin.creator-intelligence.metadata-queue.bulk-update', request()->query()) }}" x-data="metadataQueue(@js($subjectOptions), @js($selectedChannel?->id))">
        @csrf
        <section aria-labelledby="bulk-actions-title" class="mb-5 rounded-2xl border border-slate-200 bg-white p-5 dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-wrap items-center justify-between gap-3"><div><h3 id="bulk-actions-title" class="text-lg font-extrabold">Bulk metadata update</h3><p class="text-sm text-slate-600 dark:text-slate-300">Only checked videos on this page will be updated.</p></div><p class="rounded-full bg-slate-100 px-3 py-1 text-sm font-bold text-slate-800 dark:bg-slate-800 dark:text-slate-100" aria-live="polite"><span x-text="selected.length">0</span> selected on current page</p></div>
            <div class="mt-4 flex flex-wrap gap-2"><button type="button" @click="toggleAll(true)" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold outline-none hover:bg-slate-100 focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-slate-700 dark:hover:bg-slate-800">Select All on Current Page</button><button type="button" @click="clearSelection()" class="rounded-lg border border-slate-300 px-3 py-2 text-sm font-bold outline-none hover:bg-slate-100 focus-visible:ring-2 focus-visible:ring-indigo-500 dark:border-slate-700 dark:hover:bg-slate-800">Clear Selection</button></div>
            <div class="mt-4 grid items-end gap-4 md:grid-cols-2 xl:grid-cols-4">
                <label class="font-bold">Bulk Action<select name="operation" x-model="operation" @change="actionChanged()" required class="{{ $controlClasses }}"><option value="">Select an action</option><option value="assign_subject">Assign {{ $subjectLabel }}</option><option value="assign_primary_subject">Assign Primary {{ $subjectLabel }}</option><option value="subject_relationship_type">Assign {{ $subjectLabel }} Relationship Type</option><option value="content_type">Assign Content Type</option><option value="creator_sentiment">Assign Creator Sentiment</option><option value="reaction_style">Assign Reaction Style</option><option value="copyright_status">Assign Copyright Status</option><option value="is_monetized">Assign Monetization Status</option><option value="review_title">Mark Title Metadata Reviewed</option><option value="review_thumbnail">Mark Thumbnail Metadata Reviewed</option><option value="review_editorial">Mark Editorial Metadata Reviewed</option></select></label>
                <div>
                    <span class="block font-bold">Value</span>
                    <div x-show="subjectAction" x-cloak class="mt-1 space-y-2"><label class="sr-only" for="subject-search">Search {{ strtolower($subjectLabel) }} options</label><input id="subject-search" type="search" x-model="subjectSearch" placeholder="Search {{ strtolower($subjectLabel) }}" class="{{ $controlClasses }} !mt-0"><label class="sr-only" for="bulk-subject-value">{{ $subjectLabel }}</label><select id="bulk-subject-value" name="value" x-model="value" :disabled="!subjectAction" :required="subjectAction" class="{{ $controlClasses }}"><option value="">Select {{ $subjectLabel }}</option><template x-for="subject in filteredSubjects" :key="subject.id"><option :value="subject.id" x-text="subject.name"></option></template></select></div>
                    <select name="value" x-show="operation==='subject_relationship_type'" :disabled="operation!=='subject_relationship_type'" :required="operation==='subject_relationship_type'" class="{{ $controlClasses }}" x-cloak><option value="">Select relationship type</option>@foreach($relationshipTypes as $option)<option value="{{ $option->value }}">{{ str($option->value)->headline() }}</option>@endforeach</select>
                    <select name="value" x-show="operation==='content_type'" :disabled="operation!=='content_type'" :required="operation==='content_type'" class="{{ $controlClasses }}" x-cloak><option value="">Select content type</option>@foreach($contentTypes as $option)<option value="{{ $option->value }}">{{ str($option->value)->headline() }}</option>@endforeach</select>
                    <select name="value" x-show="operation==='creator_sentiment'" :disabled="operation!=='creator_sentiment'" :required="operation==='creator_sentiment'" class="{{ $controlClasses }}" x-cloak><option value="">Select sentiment</option>@foreach($sentiments as $option)<option value="{{ $option->value }}">{{ str($option->value)->headline() }}</option>@endforeach</select>
                    <select name="value" x-show="operation==='reaction_style'" :disabled="operation!=='reaction_style'" :required="operation==='reaction_style'" class="{{ $controlClasses }}" x-cloak><option value="">Select reaction style</option>@foreach($reactionStyles as $option)<option value="{{ $option->value }}">{{ str($option->value)->headline() }}</option>@endforeach</select>
                    <select name="value" x-show="operation==='copyright_status'" :disabled="operation!=='copyright_status'" :required="operation==='copyright_status'" class="{{ $controlClasses }}" x-cloak><option value="">Select copyright status</option>@foreach($copyrights as $option)<option value="{{ $option->value }}">{{ str($option->value)->headline() }}</option>@endforeach</select>
                    <select name="value" x-show="operation==='is_monetized'" :disabled="operation!=='is_monetized'" :required="operation==='is_monetized'" class="{{ $controlClasses }}" x-cloak><option value="">Select monetization status</option><option value="1">Monetized</option><option value="0">Not Monetized</option></select>
                    <p x-show="operation===''" class="mt-3 text-sm text-slate-500">Choose a bulk action first.</p><p x-show="operation.startsWith('review_')" x-cloak class="mt-3 text-sm text-slate-600 dark:text-slate-300">No value is required for review actions.</p>
                </div>
                <label class="font-bold">Update Mode<select name="mode" class="{{ $controlClasses }}"><option value="fill" selected>Fill Missing Values Only</option><option value="replace">Replace Existing Values</option></select></label>
                <button type="submit" :disabled="selected.length===0 || mixedSubjectChannels" class="min-h-11 rounded-xl bg-indigo-600 px-4 py-2 font-bold text-white outline-none hover:bg-indigo-500 focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-50 dark:focus-visible:ring-offset-slate-900">Apply to Selected</button>
            </div>
            <p x-show="mixedSubjectChannels" x-cloak role="alert" class="mt-3 rounded-lg bg-rose-100 p-3 text-sm font-bold text-rose-900 dark:bg-rose-950/50 dark:text-rose-100">Subject assignment requires selected videos from one creator channel.</p>
            <p x-show="subjectAction && !mixedSubjectChannels && selected.length>0 && filteredSubjects.length===0" x-cloak role="status" class="mt-3 rounded-lg bg-amber-100 p-3 text-sm font-bold text-amber-900 dark:bg-amber-950/50 dark:text-amber-100">No {{ strtolower($subjectLabel) }} options are available for the selected channel.</p>
            <label class="mt-4 flex items-start gap-2 text-sm text-slate-700 dark:text-slate-200"><input type="checkbox" name="confirmed" value="1" required class="mt-0.5 size-4 rounded border-slate-400 bg-white text-indigo-600 focus:ring-indigo-500 dark:border-slate-500 dark:bg-slate-950"><span>I confirm this action should be applied to the selected videos using the chosen update mode.</span></label>
        </section>

        @if($videos->isEmpty())
            <div class="rounded-2xl border border-dashed border-slate-300 bg-white p-10 text-center text-slate-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200">
                @if($emptyState==='no_imported_videos')<p class="font-bold">No imported videos are available yet.</p><p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Import analytics data to begin reviewing video metadata.</p>
                @elseif($emptyState==='no_missing_metadata')<p class="font-bold">No videos are missing the selected metadata.</p><p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Clear or adjust the missing-metadata filters to browse other videos.</p>
                @else<p class="font-bold">No videos match the current Metadata Queue filters.</p><p class="mt-2 text-sm text-slate-500 dark:text-slate-400">Try a different search or clear the active filters.</p>@endif
            </div>
        @else
            <div class="max-w-full overflow-x-auto rounded-2xl border border-slate-700 bg-slate-900" tabindex="0" aria-label="Scrollable Metadata Queue table">
                <table class="min-w-[1900px] text-sm text-slate-100">
                    <thead class="bg-slate-950 text-xs uppercase tracking-wide text-slate-300"><tr><th scope="col" class="p-4 text-left"><input type="checkbox" :checked="allPageSelected" @change="toggleAll($event.target.checked)" aria-label="Select all videos on current page" class="size-4 rounded border-slate-500 bg-slate-900 text-indigo-500 focus:ring-indigo-400"></th><th scope="col" class="p-4 text-left">Thumbnail</th><th scope="col" class="min-w-96 p-4 text-left">Video Title</th><th scope="col" class="min-w-36 p-4 text-left">Published At</th><th scope="col" class="min-w-40 p-4 text-left">Primary {{ $subjectLabel }}</th><th scope="col" class="min-w-40 p-4 text-left">Primary {{ $contentItemLabel }}</th><th scope="col" class="min-w-36 p-4 text-left">Title Metadata Status</th><th scope="col" class="min-w-40 p-4 text-left">Thumbnail Metadata Status</th><th scope="col" class="min-w-40 p-4 text-left">Editorial Metadata Status</th><th scope="col" class="min-w-36 p-4 text-right">Completion Percentage</th><th scope="col" class="min-w-32 p-4 text-left">Actions</th></tr></thead>
                    <tbody class="divide-y divide-slate-700 bg-slate-900">
                        @foreach($videos as $video)
                            <tr class="transition hover:bg-slate-800/80">
                                <td class="p-4 align-top"><input type="checkbox" name="video_ids[]" value="{{ $video->id }}" data-video-select data-channel-id="{{ $video->creator_channel_id }}" x-model="selected" @change="$nextTick(() => value='')" aria-label="Select {{ $video->title }}" class="size-4 rounded border-slate-500 bg-slate-950 text-indigo-500 focus:ring-indigo-400"></td>
                                <td class="p-4 align-top"><div class="relative flex aspect-video w-28 items-center justify-center overflow-hidden rounded-lg bg-slate-800 text-xs text-slate-400"><span>No image</span>@if($video->thumbnail_url)<img src="{{ $video->thumbnail_url }}" alt="Thumbnail for {{ $video->title }}" loading="lazy" onerror="this.hidden=true" class="absolute inset-0 h-full w-full object-cover">@endif</div></td>
                                <td class="p-4 align-top"><a href="{{ route('superadmin.creator-intelligence.videos.show', $video) }}#classification" class="line-clamp-2 max-w-xl font-bold text-indigo-300 outline-none hover:text-indigo-200 focus-visible:ring-2 focus-visible:ring-indigo-400">{{ $video->title }}</a><span class="mt-1 block font-mono text-xs text-slate-400">{{ $video->platform_video_id }}</span></td>
                                <td class="p-4 align-top text-slate-300">{{ $video->published_at?->timezone($video->channel->default_publish_timezone)->format('Y-m-d H:i') ?? 'Not published' }}</td>
                                <td class="p-4 align-top font-semibold text-slate-100">{{ $video->primarySubject->first()?->name ?? 'Missing' }}</td>
                                <td class="p-4 align-top font-semibold text-slate-100">{{ $video->primaryContentItem->first()?->name ?? 'Missing' }}</td>
                                @foreach([$video->titleMetadata?->reviewed_at, $video->thumbnailMetadata?->reviewed_at, $video->editorialMetadata?->reviewed_at] as $reviewedAt)<td class="p-4 align-top"><span class="inline-flex whitespace-nowrap rounded-full px-2 py-1 text-xs font-bold {{ $reviewedAt ? 'bg-emerald-900 text-emerald-100' : 'bg-amber-900 text-amber-100' }}">{{ $reviewedAt ? 'Reviewed' : 'Not reviewed' }}</span></td>@endforeach
                                <td class="p-4 text-right align-top"><span class="inline-flex rounded-full bg-indigo-950 px-3 py-1 font-bold text-indigo-100">{{ $video->metadata_completion_percentage }}% · {{ str($video->metadata_completion_status->value)->replace('_',' ')->title() }}</span></td>
                                <td class="p-4 align-top"><a href="{{ route('superadmin.creator-intelligence.videos.show', $video) }}#classification" class="inline-flex min-h-10 items-center rounded-lg bg-indigo-600 px-3 py-2 font-bold text-white outline-none hover:bg-indigo-500 focus-visible:ring-2 focus-visible:ring-indigo-400">Edit Metadata</a></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </form>
    <div class="mt-5 text-slate-700 dark:text-slate-200">{{ $videos->links() }}</div>
</x-creator-intelligence-layout>
