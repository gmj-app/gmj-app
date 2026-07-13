@props(['creator', 'header'])

<x-creator-hero-background :creator="$creator" class="min-h-[18rem]">
    <div class="absolute right-4 top-4 z-20" x-on:click.outside="creatorMenuOpen = false">
        <button type="button" x-on:click="creatorMenuOpen = ! creatorMenuOpen" aria-label="Open creator actions" aria-haspopup="menu" aria-expanded="false" x-bind:aria-expanded="creatorMenuOpen.toString()" class="inline-flex size-11 items-center justify-center rounded-full bg-black/50 text-white ring-1 ring-white/25 transition hover:bg-black/70 focus:outline-none focus-visible:ring-2 focus-visible:ring-white">
            <svg class="size-5" viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><circle cx="5" cy="12" r="1.8"/><circle cx="12" cy="12" r="1.8"/><circle cx="19" cy="12" r="1.8"/></svg>
        </button>
        <div x-show="creatorMenuOpen" x-cloak x-transition role="menu" class="absolute right-0 mt-2 w-56 overflow-hidden rounded-xl border border-white/10 bg-slate-950/95 py-1.5 text-sm font-semibold text-white shadow-2xl backdrop-blur">
            <button type="button" role="menuitem" x-on:click="biographyOpen = true; creatorMenuOpen = false" class="flex w-full px-4 py-3 text-left hover:bg-white/10 focus:bg-white/10 focus:outline-none">Biography</button>
            <button type="button" role="menuitem" x-on:click="submissionGuidanceOpen = true; creatorMenuOpen = false" class="flex w-full px-4 py-3 text-left hover:bg-white/10 focus:bg-white/10 focus:outline-none">Request guidance</button>
        </div>
    </div>

    <div class="relative z-10 flex min-h-[18rem] flex-col justify-end gap-6 px-4 pb-5 pt-16 sm:px-6 sm:pb-6 lg:px-8">
        <div class="grid min-w-0 gap-6 lg:grid-cols-[minmax(0,1fr)_auto] lg:items-end">
            <x-creator.identity :creator="$creator" :header="$header" />
            <x-creator.public-actions :creator="$creator" :header="$header" />
        </div>
        <x-creator.community-metrics :metrics="$header['metrics']" />
    </div>
</x-creator-hero-background>
