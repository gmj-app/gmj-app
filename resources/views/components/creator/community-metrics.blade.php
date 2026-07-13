@props(['metrics'])

<dl data-creator-community-metrics class="grid grid-cols-2 divide-x divide-y divide-white/15 overflow-hidden rounded-2xl border border-white/20 bg-slate-950/35 text-white shadow-lg backdrop-blur-md sm:grid-cols-4 sm:divide-y-0">
    @foreach ($metrics as $metric)
        <div class="min-w-0 px-3 py-3 text-center sm:px-5">
            <dd class="text-xl font-black tabular-nums sm:text-2xl">{{ number_format($metric['value']) }}</dd>
            <dt class="mt-0.5 text-[11px] font-bold uppercase tracking-[0.12em] text-white/70">{{ $metric['label'] }}</dt>
        </div>
    @endforeach
</dl>
