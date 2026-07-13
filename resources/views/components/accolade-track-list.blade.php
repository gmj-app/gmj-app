@props(['showcase'])
<div class="space-y-5">
    @foreach ($showcase['tracks'] as $track)
        <section>
            <h3 class="text-sm font-extrabold text-slate-900 dark:text-white">{{ $track['label'] }}</h3>
            <div class="mt-2 flex flex-wrap gap-2">
                @foreach ($track['earned'] as $item)
                    <div>
                        <x-accolade-badge :definition="$item['definition']" />
                        <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Earned {{ $item['award']->awarded_at->format('M j, Y') }}</p>
                    </div>
                @endforeach
            </div>
            @if ($track['next'])
                <p class="mt-3 text-sm text-slate-600 dark:text-slate-300"><span class="font-bold">{{ $track['effective_value'] }} / {{ $track['next']['threshold'] }}</span> toward {{ $track['next']['name'] }}</p>
            @else
                <p class="mt-3 text-sm font-semibold text-emerald-700 dark:text-emerald-300">Track complete</p>
            @endif
        </section>
    @endforeach
</div>
