@props([
    'advertisement',
    'heightClasses',
])

@php
    $name = $advertisement->advertiser_name ?: $advertisement->alt_text;
    $campaign = $advertisement->alt_text;
@endphp

<a
    href="{{ route('ads.click', $advertisement) }}"
    target="_blank"
    rel="noopener noreferrer sponsored"
    aria-label="Sponsored: {{ $name }} — {{ $campaign }}"
    data-home-grid-tile
    data-sponsored-tile
    class="group relative isolate flex min-w-0 cursor-pointer flex-col overflow-hidden rounded-3xl border border-slate-200 bg-gradient-to-br from-indigo-700 via-slate-800 to-slate-950 shadow-sm transition duration-200 hover:-translate-y-1 hover:border-indigo-300 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 motion-reduce:transform-none motion-reduce:transition-none dark:border-slate-800 dark:hover:border-indigo-500/60 dark:focus-visible:ring-offset-slate-950 {{ $heightClasses }}"
>
    @if (filled($advertisement->image_path))
        <img
            src="{{ $advertisement->imageUrl() }}"
            alt=""
            width="1200"
            height="1500"
            loading="lazy"
            data-sponsored-image
            class="absolute inset-0 h-full w-full object-cover object-center transition duration-300 group-hover:scale-[1.02] motion-reduce:transform-none motion-reduce:transition-none"
            onerror="this.remove()"
        >
    @endif

    <div data-sponsored-overlay aria-hidden="true" class="absolute inset-0 bg-gradient-to-b from-slate-950/15 via-slate-950/25 to-slate-950/95"></div>
    <div aria-hidden="true" class="absolute inset-0 bg-gradient-to-r from-slate-950/35 via-transparent to-slate-950/10"></div>

    <span class="absolute right-4 top-4 z-10 rounded-full bg-slate-950/80 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-white shadow-sm ring-1 ring-white/40">
        Sponsored
    </span>

    <div data-sponsored-content class="relative z-10 mt-auto min-w-0 p-5 text-white 2xl:p-4">
        <h3 data-sponsored-title title="{{ $name }}" class="line-clamp-2 text-xl font-extrabold leading-7 drop-shadow-sm">
            {{ $name }}
        </h3>
        <p data-sponsored-copy class="mt-1 line-clamp-2 text-sm font-medium leading-5 text-slate-100 drop-shadow-sm">
            {{ $campaign }}
        </p>
        <span data-sponsored-cta class="mt-4 inline-flex min-h-10 items-center rounded-xl bg-white/95 px-4 py-2 text-sm font-bold text-slate-950 shadow-sm ring-1 ring-white/30 transition group-hover:bg-white group-hover:text-indigo-700 group-hover:underline motion-reduce:transition-none">
            {{ $advertisement->cta_label ?: 'Learn more' }} <span aria-hidden="true" class="ml-1 transition-transform group-hover:translate-x-0.5 motion-reduce:transform-none motion-reduce:transition-none">→</span>
        </span>
    </div>
</a>
