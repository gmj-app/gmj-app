@props(['creator', 'header'])

<div class="flex min-w-0 flex-col gap-4 sm:flex-row sm:items-center">
    <x-creator-avatar
        :creator="$creator"
        size="xl"
        class="size-20 shrink-0 border-2 border-white/70 shadow-2xl ring-4 ring-slate-950/30 sm:size-24"
    />

    <div class="min-w-0">
        <h1 class="line-clamp-2 max-w-3xl break-words text-[clamp(2rem,5vw,3.5rem)] font-black leading-[0.98] tracking-tight text-white">
            {{ $header['identity']['name'] }}
        </h1>
        <p class="mt-1.5 truncate text-sm font-semibold text-white/70 sm:text-base">{{ $header['identity']['handle'] }}</p>
        @if ($header['identity']['bio'])
            <p class="mt-2 line-clamp-2 max-w-2xl text-sm font-medium leading-5 text-white/85 sm:text-base sm:leading-6">{{ $header['identity']['bio'] }}</p>
        @endif
        <x-creator.featured-accolades :creator="$creator" :accolades="$header['featured_accolades']" />
    </div>
</div>
