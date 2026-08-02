<x-creator-intelligence-layout title="Upload Import">
    <form method="POST" enctype="multipart/form-data" action="{{ route('superadmin.creator-intelligence.imports.store') }}" class="max-w-3xl rounded-2xl border border-slate-200 bg-white p-6 dark:border-slate-800 dark:bg-slate-900">@csrf
        <h2 class="text-2xl font-extrabold">Upload analytics export</h2><p class="mt-2 text-sm text-slate-500">Files remain private. CSV and YouTube Studio ZIP exports up to {{ number_format(config('creator_intelligence.max_upload_kilobytes')/1024) }} MB are supported.</p>
        <div class="mt-6 grid gap-5 sm:grid-cols-2"><label class="font-bold">Creator channel<select name="creator_channel_id" required class="mt-2 w-full rounded-xl border-slate-300 dark:bg-slate-950"><option value="">Select channel</option>@foreach($channels as $channel)<option value="{{ $channel->id }}" @selected(old('creator_channel_id')==$channel->id)>{{ $channel->channel_name }}</option>@endforeach</select></label>
        <label class="font-bold">Import source<select name="source" class="mt-2 w-full rounded-xl border-slate-300 dark:bg-slate-950">@foreach($sources as $source)<option value="{{ $source->value }}" @selected(old('source','youtube_studio')===$source->value)>{{ str($source->value)->replace('_',' ')->title() }}</option>@endforeach</select></label>
        <label class="font-bold">Snapshot date<input type="date" name="snapshot_date" value="{{ old('snapshot_date', now()->toDateString()) }}" required class="mt-2 w-full rounded-xl border-slate-300 dark:bg-slate-950"></label>
        <label class="font-bold">CSV or ZIP file<input type="file" name="file" accept=".csv,.zip,text/csv,application/zip" required class="mt-2 block w-full text-sm"></label></div>
        <button class="mt-6 rounded-xl bg-indigo-600 px-5 py-3 font-bold text-white">Upload and inspect</button>
    </form>
</x-creator-intelligence-layout>
