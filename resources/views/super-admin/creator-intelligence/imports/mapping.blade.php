<x-creator-intelligence-layout title="Column Mapping">
    <h2 class="text-2xl font-extrabold">Column mapping</h2>
    <p class="mt-2 text-slate-500">Map every source column once or ignore it. Title is required. YouTube Studio may identify videos using either Content or Video.</p>
    @if (! in_array('platform_video_id', $batch->column_mapping ?? [], true))
        <div class="ci-alert-warning mt-4" role="status">No YouTube Video ID (Content or Video) is mapped. Matching will rely on title and published date.</div>
    @endif
    <form method="POST" action="{{ route('superadmin.creator-intelligence.imports.mapping.update', $batch) }}" class="ci-table-container mt-6">
        @csrf
        @method('PUT')
        <table class="min-w-full text-sm">
            <thead><tr class="text-left text-xs uppercase"><th scope="col" class="p-4">Source column</th><th scope="col" class="p-4">Sample values</th><th scope="col" class="p-4">Canonical field</th></tr></thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($batch->detected_columns ?? [] as $column)
                    <tr>
                        <td class="p-4 font-bold">{{ $column }}</td>
                        <td class="max-w-md p-4 text-slate-500">{{ collect($batch->preview_rows)->pluck($column)->filter()->take(3)->implode(' · ') }}</td>
                        <td class="p-4"><select name="mapping[{{ $column }}]" class="w-full rounded-xl border-slate-300 dark:bg-slate-950"><option value="">Ignore</option>@foreach ($fields as $field)<option value="{{ $field }}" @selected(old('mapping.'.$column, ($batch->column_mapping ?? [])[$column] ?? '') === $field)>{{ $field === 'platform_video_id' ? 'YouTube Video ID (Content or Video)' : str($field)->replace('_', ' ')->title() }}</option>@endforeach</select></td>
                    </tr>
                @endforeach
            </tbody>
        </table>
        <div class="p-4"><button class="rounded-xl bg-indigo-600 px-5 py-3 font-bold text-white">Save mapping</button></div>
    </form>
</x-creator-intelligence-layout>
