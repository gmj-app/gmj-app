<x-super-admin-layout title="Accolades">
    <div class="space-y-6">
        <section class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                <div><h2 class="text-xl font-extrabold">Earned accolades</h2><p class="mt-1 text-sm text-slate-500">Inspect metric-derived awards and their immutable source context.</p></div>
                <form method="GET" class="grid gap-2 sm:grid-cols-3">
                    <input name="q" value="{{ $filters['q'] ?? '' }}" placeholder="User or email" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950">
                    <select name="track" class="rounded-xl border-slate-300 text-sm dark:border-slate-700 dark:bg-slate-950"><option value="">All tracks</option>@foreach(config('accolades.tracks') as $key => $track)<option value="{{ $key }}" @selected(($filters['track'] ?? '') === $key)>{{ $track['label'] }}</option>@endforeach</select>
                    <button class="rounded-xl bg-indigo-600 px-4 py-2 text-sm font-bold text-white">Filter</button>
                </form>
            </div>
        </section>

        <section class="overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-200 text-sm dark:divide-slate-800">
                    <thead class="bg-slate-50 text-left text-xs uppercase tracking-wide text-slate-500 dark:bg-slate-950"><tr><th class="px-4 py-3">Recipient</th><th class="px-4 py-3">Accolade</th><th class="px-4 py-3">Subject</th><th class="px-4 py-3">Awarded</th><th class="px-4 py-3">Source</th><th class="px-4 py-3">Tools</th></tr></thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @forelse($awards as $award)
                            @php($definition = $definitions->firstWhere('key', $award->accolade_key))
                            <tr>
                                <td class="px-4 py-3"><p class="font-bold">{{ $award->user->publicName() }}</p><p class="text-xs text-slate-500">{{ $award->user->email }}</p></td>
                                <td class="px-4 py-3">@if($definition)<x-accolade-badge :definition="$definition" size="sm" />@else<span class="font-mono text-xs">{{ $award->accolade_key }}</span>@endif<p class="mt-1 text-xs text-slate-500">{{ $award->progress_value_at_award }} / {{ $award->threshold_at_award }}</p></td>
                                <td class="px-4 py-3">{{ ucfirst($award->subject_type) }} #{{ $award->subject_id }}<p class="text-xs text-slate-500">{{ $award->track }}</p></td>
                                <td class="px-4 py-3">{{ $award->awarded_at->format('M j, Y g:i A') }}</td>
                                <td class="max-w-xs px-4 py-3"><p class="break-all text-xs">{{ $award->source_event_type ?: data_get($award->metadata, 'source', 'domain evaluation') }}</p></td>
                                <td class="px-4 py-3">
                                    <div class="flex flex-col gap-2">
                                        @if($award->subject_type === 'guide')
                                            <form method="POST" action="{{ route('super-admin.accolades.guides.evaluate', $award->subject_id) }}">@csrf<button class="font-bold text-indigo-600">Evaluate Guide</button></form>
                                        @else
                                            <form method="POST" action="{{ route('super-admin.accolades.creators.evaluate', $award->subject_id) }}">@csrf<button class="font-bold text-indigo-600">Evaluate Creator</button></form>
                                        @endif
                                        <form method="POST" action="{{ route('super-admin.accolades.rebuild') }}">@csrf<input type="hidden" name="subject_type" value="{{ $award->subject_type }}"><input type="hidden" name="subject_id" value="{{ $award->subject_id }}"><button class="font-bold text-slate-600 dark:text-slate-300">Rebuild progress</button></form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-6 py-12 text-center text-slate-500">No earned accolades match these filters.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="p-4">{{ $awards->links() }}</div>
        </section>
    </div>
</x-super-admin-layout>
