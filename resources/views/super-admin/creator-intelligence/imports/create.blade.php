<x-creator-intelligence-layout title="Upload Import">
    <form method="POST" enctype="multipart/form-data" action="{{ route('superadmin.creator-intelligence.imports.store') }}" class="ci-form-card max-w-3xl">
        @csrf
        <h2 class="text-2xl font-extrabold">Upload analytics export</h2>
        <p id="import-file-help" class="ci-help mt-2">Files remain private. CSV and YouTube Studio ZIP exports up to {{ number_format(config('creator_intelligence.max_upload_kilobytes') / 1024) }} MB are supported.</p>
        <div class="mt-6 grid gap-5 sm:grid-cols-2">
            <div><label for="creator-channel" class="ci-label">Creator channel</label><select id="creator-channel" name="creator_channel_id" required class="ci-control mt-2 w-full" aria-invalid="{{ $errors->has('creator_channel_id') ? 'true' : 'false' }}"><option value="">Select channel</option>@foreach($channels as $channel)<option value="{{ $channel->id }}" @selected(old('creator_channel_id') == $channel->id)>{{ $channel->channel_name }}</option>@endforeach</select>@error('creator_channel_id')<p class="ci-error">{{ $message }}</p>@enderror</div>
            <div><label for="import-source" class="ci-label">Import source</label><select id="import-source" name="source" class="ci-control mt-2 w-full" aria-invalid="{{ $errors->has('source') ? 'true' : 'false' }}">@foreach($sources as $source)<option value="{{ $source->value }}" @selected(old('source', 'youtube_studio') === $source->value)>{{ str($source->value)->replace('_', ' ')->title() }}</option>@endforeach</select>@error('source')<p class="ci-error">{{ $message }}</p>@enderror</div>
            <div><label for="snapshot-date" class="ci-label">Snapshot date</label><input id="snapshot-date" type="date" name="snapshot_date" value="{{ old('snapshot_date', now()->toDateString()) }}" required class="ci-control mt-2 w-full" aria-invalid="{{ $errors->has('snapshot_date') ? 'true' : 'false' }}">@error('snapshot_date')<p class="ci-error">{{ $message }}</p>@enderror</div>
            <div><label for="import-file" class="ci-label">CSV or ZIP file</label><input id="import-file" type="file" name="file" accept=".csv,.zip,text/csv,application/zip" required class="ci-control mt-2 block w-full text-sm" aria-describedby="import-file-help" aria-invalid="{{ $errors->has('file') ? 'true' : 'false' }}">@error('file')<p class="ci-error">{{ $message }}</p>@enderror</div>
        </div>
        <button class="ci-button-primary mt-6">Upload and inspect</button>
    </form>
</x-creator-intelligence-layout>
