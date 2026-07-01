@props(['creator'])

<div
    {{ $attributes->class([
        'relative overflow-hidden bg-gradient-to-r from-indigo-600 via-violet-600 to-fuchsia-500',
    ]) }}
>
    @if (filled($creator->hero_url))
        <img
            src="{{ $creator->hero_url }}"
            alt=""
            class="absolute inset-0 h-full w-full object-cover object-center"
            onerror="this.remove()"
        >
    @endif

    <div class="absolute inset-0 bg-gradient-to-t from-slate-950/85 via-slate-950/40 to-transparent"></div>
    <div class="absolute inset-0 bg-gradient-to-r from-slate-950/80 via-slate-950/30 to-transparent"></div>

    {{ $slot }}
</div>
