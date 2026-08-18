<x-app-layout>
    <x-slot name="header">@include('creators.partials.header', ['section' => 'Possible Duplicates'])</x-slot>
    <div class="mx-auto max-w-6xl space-y-5 px-4 py-8">@include('creators.partials.navigation')
        @if(session('success'))<div class="rounded-xl bg-emerald-50 p-4 font-semibold text-emerald-800">{{ session('success') }}</div>@endif
        @forelse($cases as $case)
            <article class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900">
                <h2 class="font-bold">Possible duplicate</h2><p class="text-sm text-slate-500">Reported by {{ $case->reports_count }} {{ Str::plural('Guide', $case->reports_count) }}</p>
                <div class="mt-5 grid gap-4 md:grid-cols-2">
                    @foreach([$case->requestLow,$case->requestHigh] as $item)
                        <section class="rounded-xl border border-slate-200 p-4 dark:border-slate-700"><h3 class="font-bold">{{ $item->displayTitle() }}</h3><p class="mt-2 text-sm">{{ $item->totalVotes() }} unique supporters · {{ $item->statusLabel() }}</p><p class="text-sm text-slate-500">Requested by {{ $item->submittedBy?->publicName() ?: 'Creator' }} · {{ $item->created_at->format('M j, Y') }}</p>@if($item->canonicalMediaUrl())<a class="mt-2 block break-all text-sm text-indigo-600" href="{{ $item->canonicalMediaUrl() }}" target="_blank" rel="noopener">{{ parse_url($item->canonicalMediaUrl(), PHP_URL_HOST) }}</a>@endif</section>
                    @endforeach
                </div>
                <div class="mt-5 grid gap-3 md:grid-cols-3">
                    <form method="POST" action="{{ route('creators.duplicates.resolve',[$creator,$case]) }}">@csrf<input type="hidden" name="resolution" value="not_duplicate"><button class="w-full rounded-xl border px-4 py-3 font-bold">Not a duplicate</button></form>
                    @foreach([['keep_a',$case->requestLow,$case->requestHigh],['keep_b',$case->requestHigh,$case->requestLow]] as [$resolution,$keep,$merge])
                        <form method="POST" action="{{ route('creators.duplicates.resolve',[$creator,$case]) }}" onsubmit="return confirm(@js('Merge duplicate Requests? Keep “'.$keep->displayTitle().'” and merge “'.$merge->displayTitle().'” into it. Unique supporters will be combined and overlaps count once.'))">@csrf<input type="hidden" name="resolution" value="{{ $resolution }}"><input type="hidden" name="confirm" value="1"><button class="w-full rounded-xl bg-indigo-600 px-4 py-3 font-bold text-white">Keep “{{ Str::limit($keep->displayTitle(),35) }}”</button></form>
                    @endforeach
                </div>
            </article>
        @empty<div class="rounded-2xl border border-dashed p-10 text-center"><h2 class="font-bold">No possible duplicates awaiting review</h2></div>@endforelse
        {{ $cases->links() }}
    </div>
</x-app-layout>
