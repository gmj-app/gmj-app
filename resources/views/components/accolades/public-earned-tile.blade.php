@props(['item'])

<div class="flex min-w-0 items-center gap-3 rounded-xl border p-3 {{ $item['theme']['border'] }} {{ $item['theme']['surface'] }}"
     aria-label="{{ $item['name'] }}, {{ $item['track_label'] }}. {{ $item['description'] }}{{ $item['awarded_date'] ? ' Earned '.$item['awarded_date'].'.' : '' }}">
    <x-accolade-badge :definition="$item['definition']" :show-label="false" size="sm" class="shrink-0" aria-hidden="true" />
    <span class="min-w-0">
        <span class="block truncate text-sm font-extrabold text-slate-950 dark:text-white">{{ $item['name'] }}</span>
        <span class="block truncate text-xs font-semibold {{ $item['theme']['text'] }}">{{ $item['track_label'] }}</span>
    </span>
</div>
