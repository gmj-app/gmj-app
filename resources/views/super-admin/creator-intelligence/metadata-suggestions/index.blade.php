<x-creator-intelligence-layout title="Metadata Suggestions">
    @php $control = 'min-h-11 rounded-xl border-slate-300 bg-white text-slate-950 dark:border-slate-700 dark:bg-slate-950 dark:text-white'; @endphp
    <div class="flex flex-wrap items-end justify-between gap-4">
        <div><h2 class="text-2xl font-extrabold">Metadata Suggestions</h2><p class="mt-1 text-sm text-slate-600 dark:text-slate-300">Deterministic title matches awaiting review. No external AI is used.</p></div>
        <form method="POST" action="{{ route('superadmin.creator-intelligence.metadata-suggestions.generate') }}" class="flex gap-2">@csrf
            <select name="creator_channel_id" class="{{ $control }}"><option value="">All channels</option>@foreach($channels as $channel)<option value="{{ $channel->id }}">{{ $channel->channel_name }}</option>@endforeach</select>
            <button class="rounded-xl bg-indigo-600 px-4 py-2 font-bold text-white">Generate Suggestions</button>
        </form>
    </div>

    <div class="mt-5 grid gap-3 sm:grid-cols-2 lg:grid-cols-4">
        @foreach([['High-confidence subjects',$summary->where('confidence','high')->where('suggestion_type','subject')->sum('aggregate')],['High-confidence content items',$summary->where('confidence','high')->where('suggestion_type','content_item')->sum('aggregate')],['Medium-confidence suggestions',$summary->where('confidence','medium')->sum('aggregate')],['Unresolved videos',$unresolved]] as [$label,$value])
            <div class="ci-card"><p class="ci-muted text-sm">{{ $label }}</p><p class="mt-1 text-2xl font-extrabold">{{ $value }}</p></div>
        @endforeach
    </div>

    <form method="GET" class="mt-5 grid gap-3 rounded-2xl border border-slate-200 bg-white p-4 sm:grid-cols-2 lg:grid-cols-4 dark:border-slate-800 dark:bg-slate-900">
        <input name="search" value="{{ request('search') }}" placeholder="Search video title" class="{{ $control }}">
        <select name="creator_channel_id" class="{{ $control }}"><option value="">All channels</option>@foreach($channels as $channel)<option value="{{ $channel->id }}" @selected(request('creator_channel_id')==$channel->id)>{{ $channel->channel_name }}</option>@endforeach</select>
        @foreach(['confidence'=>['high','medium','low'],'suggestion_type'=>['subject','content_item'],'status'=>['pending','approved','rejected','applied']] as $name=>$values)<select name="{{ $name }}" class="{{ $control }}"><option value="">All {{ str($name)->replace('_',' ') }}</option>@foreach($values as $value)<option value="{{ $value }}" @selected(request($name)===$value)>{{ str($value)->replace('_',' ')->title() }}</option>@endforeach</select>@endforeach
        <label class="flex items-center gap-2"><input type="checkbox" name="missing_subject" value="1" @checked(request()->boolean('missing_subject'))> Missing subject</label>
        <label class="flex items-center gap-2"><input type="checkbox" name="missing_content_item" value="1" @checked(request()->boolean('missing_content_item'))> Missing content item</label>
        <button class="rounded-xl bg-slate-800 px-4 py-2 font-bold text-white">Apply filters</button>
    </form>

    <form method="POST" action="{{ route('superadmin.creator-intelligence.metadata-suggestions.bulk', request()->query()) }}" class="mt-5">@csrf
        <div class="mb-3 flex flex-wrap items-center gap-2">
            <select name="action" required class="{{ $control }}"><option value="">Bulk action</option><option value="approve">Approve selected</option><option value="approve_high">Approve all high-confidence in current filter</option><option value="reject">Reject selected</option><option value="not_applicable">No specific content item</option></select>
            <label class="text-sm"><input type="checkbox" name="replace_primary" value="1"> Explicitly replace current primary</label>
            <label class="text-sm"><input type="checkbox" name="confirmed" value="1" required> Confirm exact filtered/selected action</label>
            <button class="rounded-xl bg-emerald-600 px-4 py-2 font-bold text-white">Apply</button>
        </div>
        <div class="overflow-x-auto rounded-2xl border border-slate-700"><table class="min-w-[1200px] w-full text-sm"><thead class="bg-slate-950 text-left text-slate-200"><tr><th class="p-3">Select</th><th>Video</th><th>Suggestion</th><th>Confidence</th><th>Evidence</th><th>Current</th><th>Status</th></tr></thead><tbody class="divide-y divide-slate-700 bg-slate-900 text-slate-100">
            @foreach($suggestions as $suggestion)<tr><td class="p-3"><input type="checkbox" name="suggestion_ids[]" value="{{ $suggestion->id }}" aria-label="Select suggestion for {{ $suggestion->video->title }}"></td><td class="p-3"><div class="flex min-w-96 gap-3"><div class="relative aspect-video w-24 shrink-0 bg-slate-800">@if($suggestion->video->thumbnail_url)<img src="{{ $suggestion->video->thumbnail_url }}" alt="" loading="lazy" class="h-full w-full object-cover">@endif</div><span>{{ $suggestion->video->title }}<small class="mt-1 block text-slate-400">{{ $suggestion->video->channel->channel_name }}</small></span></div></td><td class="p-3">@if($suggestion->suggestion_type==='subject' && $suggestion->status==='pending')<select name="suggested_subject_ids[{{ $suggestion->id }}]" aria-label="Correct suggested subject" class="{{ $control }}">@foreach($subjectsByChannel->get($suggestion->video->creator_channel_id, collect()) as $subject)<option value="{{ $subject->id }}" @selected($subject->id===$suggestion->suggested_subject_id)>{{ $subject->name }}</option>@endforeach</select>@elseif($suggestion->suggestion_type==='content_item' && !$suggestion->suggested_content_item_id && $suggestion->status==='pending')<input name="suggestion_values[{{ $suggestion->id }}]" value="{{ $suggestion->suggested_display_value }}" aria-label="Edit suggested content item" class="{{ $control }}">@else<strong>{{ $suggestion->suggested_display_value }}</strong>@endif<small class="block text-slate-400">{{ str($suggestion->suggestion_type)->replace('_',' ')->title() }}</small></td><td class="p-3"><span class="rounded-full bg-indigo-950 px-2 py-1 font-bold">{{ ucfirst($suggestion->confidence) }} {{ $suggestion->confidence_score }}</span></td><td class="max-w-xs p-3 text-xs text-slate-300">{{ $suggestion->rule_name }}: {{ collect($suggestion->evidence)->filter()->implode(', ') }}</td><td class="p-3">{{ $suggestion->suggestion_type==='subject' ? ($suggestion->video->primarySubject->first()?->name ?? 'Missing') : ($suggestion->video->content_item_not_applicable ? 'Not applicable' : ($suggestion->video->primaryContentItem->first()?->name ?? 'Missing')) }}</td><td class="p-3">{{ ucfirst($suggestion->status) }}</td></tr>@endforeach
        </tbody></table></div>
    </form>
    <div class="mt-5">{{ $suggestions->links() }}</div>
</x-creator-intelligence-layout>
