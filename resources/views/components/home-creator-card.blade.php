@props([
    'creator' => null,
    'advertisement' => null,
    'heightClasses',
])

@php
    $isSponsored = $advertisement !== null;
    $isCreatorCard = $creator !== null;
    $name = ! $isCreatorCard
        ? ($advertisement->advertiser_name ?: $advertisement->alt_text)
        : $creator->display_name;
    $description = ! $isCreatorCard
        ? $advertisement->alt_text
        : $creator->full_card_description;
    $href = $isSponsored
        ? route('ads.click', $advertisement)
        : route('creator.queue', $creator);
    $bannerUrl = $isSponsored ? $advertisement->imageUrl() : $creator->hero_url;
    $sponsoredInitials = collect(preg_split('/\s+/', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY))
        ->take(2)
        ->map(fn (string $word): string => Str::upper(Str::substr($word, 0, 1)))
        ->implode('') ?: 'AD';
@endphp

<a
    href="{{ $href }}"
    @if ($isSponsored) target="_blank" rel="noopener noreferrer sponsored" @endif
    aria-label="{{ $isSponsored ? 'Sponsored: '.$name : 'View '.$name }}"
    data-home-grid-tile
    data-home-compact-card
    @if ($isSponsored) data-sponsored-card @else data-creator-card @endif
    class="group relative flex min-w-0 cursor-pointer flex-col overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm transition duration-200 hover:-translate-y-1 hover:border-indigo-300 hover:shadow-xl focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-slate-800 dark:bg-slate-900 dark:hover:border-indigo-500/60 dark:focus-visible:ring-offset-slate-950 {{ $heightClasses }}"
>
    <div data-home-card-banner class="relative h-24 shrink-0 overflow-hidden bg-gradient-to-br from-indigo-600 via-sky-600 to-violet-600 2xl:h-20">
        @if (filled($bannerUrl))
            <img
                src="{{ $bannerUrl }}"
                alt=""
                width="640"
                height="192"
                loading="lazy"
                class="absolute inset-0 h-full w-full object-cover object-center transition duration-300 group-hover:scale-105"
                onerror="this.remove()"
            >
        @endif

        <div class="absolute inset-0 bg-gradient-to-t from-slate-950/65 via-slate-950/20 to-transparent"></div>
        <div class="absolute inset-0 bg-gradient-to-r from-slate-950/45 via-slate-950/10 to-transparent"></div>

        @if ($isSponsored)
            <span class="absolute bottom-4 right-4 rounded-full bg-indigo-600 px-3 py-1 text-[11px] font-semibold uppercase tracking-wide text-white shadow-sm ring-1 ring-white/20">Sponsored</span>
        @endif
    </div>

    <div data-home-card-body class="relative flex flex-1 flex-col p-4 pt-3 2xl:p-3 2xl:pt-3">
        <div data-home-card-identity class="relative min-h-14 min-w-0 pl-[4.75rem] 2xl:pl-[4.25rem]">
            <div data-home-card-avatar class="absolute -top-8 left-0 z-10">
                @if (! $isCreatorCard)
                    <span class="relative inline-flex h-16 w-16 shrink-0 items-center justify-center overflow-hidden rounded-full bg-gradient-to-br from-indigo-500 to-violet-600 text-xl font-extrabold text-white shadow-sm ring-4 ring-white dark:ring-slate-900 2xl:h-14 2xl:w-14 2xl:text-lg">
                        <span aria-hidden="true">{{ $sponsoredInitials }}</span>
                        @if (filled($bannerUrl))
                            <img src="{{ $bannerUrl }}" alt="" width="64" height="64" loading="lazy" class="absolute inset-0 block h-full w-full object-cover" onerror="this.remove()">
                        @endif
                    </span>
                @else
                    <x-creator-avatar :creator="$creator" size="home" shape="circle" class="ring-4 ring-white dark:ring-slate-900" />
                @endif
            </div>

            <div class="flex min-h-14 min-w-0 items-start gap-2">
                <h3 data-home-card-name title="{{ $name }}" class="line-clamp-2 min-h-14 min-w-0 flex-1 break-words text-lg font-bold leading-7 text-slate-950 dark:text-white">{{ $name }}</h3>
                @if ($isCreatorCard && $creator->verification_status === 'verified')
                    <span class="mt-1 shrink-0 rounded-full bg-emerald-100 px-2 py-0.5 text-[11px] font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Verified</span>
                @endif
            </div>
        </div>

        <p data-home-card-bio class="mb-3 mt-2 min-h-[3.75rem] line-clamp-3 text-sm leading-5 text-slate-600 dark:text-slate-400">
            {{ $description }}
        </p>

        <div data-home-card-footer class="mt-auto border-t border-slate-200/80 pt-4 dark:border-slate-800 2xl:pt-3">
            @if (! $isCreatorCard)
                <div class="flex min-h-8 min-w-0 items-center justify-between gap-3 text-sm leading-5">
                    <span class="truncate text-slate-500 dark:text-slate-400">Sponsored</span>
                    <span class="shrink-0 font-bold text-indigo-600 transition group-hover:text-indigo-500 dark:text-indigo-300">{{ $advertisement->cta_label ?: 'Learn more' }} →</span>
                </div>
            @else
                <div class="flex min-h-8 flex-wrap items-center justify-center gap-x-2 gap-y-1 text-center text-xs tabular-nums text-slate-500 dark:text-slate-400 sm:text-sm 2xl:grid 2xl:grid-cols-3 2xl:gap-0 2xl:text-xs">
                    <span class="min-w-0 2xl:flex 2xl:flex-col"><strong class="text-slate-950 dark:text-white">{{ number_format((int) $creator->followers_count) }}</strong> <span class="truncate">{{ Str::plural('follower', (int) $creator->followers_count) }}</span></span>
                    <span aria-hidden="true" class="text-slate-300 dark:text-slate-700 2xl:hidden">|</span>
                    <span class="min-w-0 2xl:flex 2xl:flex-col"><strong class="text-slate-950 dark:text-white">{{ (int) $creator->visible_recommendations_count }}</strong> <span class="truncate">{{ Str::plural('request', (int) $creator->visible_recommendations_count) }}</span></span>
                    <span aria-hidden="true" class="text-slate-300 dark:text-slate-700 2xl:hidden">|</span>
                    <span class="min-w-0 2xl:flex 2xl:flex-col"><strong class="text-slate-950 dark:text-white">{{ (int) ($creator->published_recommendations_count ?? 0) }}</strong> <span class="truncate">published</span></span>
                </div>
            @endif
        </div>
    </div>
</a>
