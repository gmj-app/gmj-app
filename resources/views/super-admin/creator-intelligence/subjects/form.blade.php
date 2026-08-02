<x-creator-intelligence-layout :title="$subject->exists ? 'Edit Subject' : 'Add Subject'">
    <h2 class="mb-5 text-2xl font-extrabold">{{ $subject->exists ? 'Edit Subject' : 'Add Subject' }}</h2>
    <form method="POST" action="{{ $subject->exists ? route('superadmin.creator-intelligence.subjects.update', $subject) : route('superadmin.creator-intelligence.subjects.store') }}" class="max-w-2xl space-y-4 rounded-2xl border bg-white p-6">@csrf @if($subject->exists) @method('PUT') @endif
        <label class="block">Channel<select name="creator_channel_id" required class="mt-1 block w-full rounded-xl border-slate-300">@foreach($channels as $channel)<option value="{{ $channel->id }}" @selected(old('creator_channel_id', $subject->creator_channel_id) == $channel->id)>{{ $channel->channel_name }}</option>@endforeach</select></label>
        <label class="block">Name<input name="name" required maxlength="150" value="{{ old('name', $subject->name) }}" class="mt-1 block w-full rounded-xl border-slate-300"></label>
        <label class="block">Aliases (one per line)<textarea name="aliases" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('aliases', implode("\n", $subject->aliases ?? [])) }}</textarea></label>
        <label class="block">Notes<textarea name="notes" class="mt-1 block w-full rounded-xl border-slate-300">{{ old('notes', $subject->notes) }}</textarea></label>
        <button class="rounded-xl bg-indigo-600 px-5 py-2 font-bold text-white">Save</button>
    </form>
</x-creator-intelligence-layout>
