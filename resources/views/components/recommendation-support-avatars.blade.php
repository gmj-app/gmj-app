@props([
    'recommendation',
    'limit' => 10,
    'size' => 'sm',
    'includeRequester' => true,
    'includeUpvoters' => true,
    'skipRequesterUpvote' => false,
    'showEmpty' => false,
])

@php
    $requester = $recommendation->submittedBy;
    $upvoters = $recommendation->relationLoaded('userPicks')
        ? $recommendation->userPicks->pluck('user')->filter()
        : collect();

    $supporters = collect();

    if ($includeRequester && $requester) {
        $contextLine = "Requested by {$requester->publicName()}";
        $accoladeLines = $requester->guideAccoladeTooltipLines();
        $ariaAccoladeLine = $requester->guideAccoladeAriaLine();

        $supporters->push([
            'user' => $requester,
            'isRequester' => true,
            'titleLines' => [
                $contextLine,
                ...$accoladeLines,
            ],
            'ariaLabel' => $ariaAccoladeLine
                ? "{$contextLine}. {$ariaAccoladeLine}."
                : $contextLine,
        ]);
    }

    foreach ($includeUpvoters ? $upvoters : collect() as $upvoter) {
        if ($skipRequesterUpvote && $requester && $upvoter->id === $requester->id) {
            continue;
        }

        if ($supporters->contains(fn (array $supporter) => $supporter['user']->id === $upvoter->id)) {
            continue;
        }

        $contextLine = "Supported by {$upvoter->publicName()}";
        $accoladeLines = $upvoter->guideAccoladeTooltipLines();
        $ariaAccoladeLine = $upvoter->guideAccoladeAriaLine();

        $supporters->push([
            'user' => $upvoter,
            'isRequester' => false,
            'titleLines' => [
                $contextLine,
                ...$accoladeLines,
            ],
            'ariaLabel' => $ariaAccoladeLine
                ? "{$contextLine}. {$ariaAccoladeLine}."
                : $contextLine,
        ]);
    }

    $visibleSupporters = $supporters->take($limit);
    $hiddenSupportersCount = max(0, $supporters->count() - $visibleSupporters->count());
    $avatarSizeClasses = $size === 'md' ? 'size-8 text-xs' : 'size-6 text-[10px]';
    $starSizeClasses = $size === 'md' ? 'size-3.5 -bottom-0.5 -right-0.5' : 'size-3 -bottom-0.5 -right-0.5';
    $stackSpacing = $size === 'md' ? '-ml-2' : '-ml-1.5';
    $morePillClasses = $size === 'md' ? 'h-8 px-2 text-xs' : 'h-6 px-1.5 text-[10px]';
@endphp

@if ($visibleSupporters->isNotEmpty())
    <span {{ $attributes->merge(['class' => 'flex min-w-0 items-center']) }}>
        @foreach ($visibleSupporters as $supporter)
            @php
                $user = $supporter['user'];
                $initials = $user->initialsForAvatar();
                $titleLines = $supporter['titleLines'];
                $avatarRingClass = $user->guideAvatarRingClass();
            @endphp

            <span
                class="relative inline-flex shrink-0 overflow-visible rounded-full ring-2 ring-white first:ml-0 dark:ring-slate-900 {{ ! $loop->first ? $stackSpacing : '' }}"
                title="{!! collect($titleLines)->map(fn (string $line): string => e($line))->implode('&#10;') !!}"
                aria-label="{{ $supporter['ariaLabel'] }}"
            >
                @if (filled($user->avatar_url))
                    <img
                        src="{{ $user->avatar_url }}"
                        alt=""
                        loading="lazy"
                        class="{{ $avatarSizeClasses }} {{ $avatarRingClass }} rounded-full object-cover"
                        onerror="this.hidden = true"
                    >
                @else
                    <span class="{{ $avatarSizeClasses }} {{ $avatarRingClass }} inline-flex items-center justify-center rounded-full bg-slate-200 font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-100">
                        {{ $initials }}
                    </span>
                @endif

                @if ($supporter['isRequester'])
                    <span class="absolute inline-flex items-center justify-center rounded-full bg-amber-400 text-amber-950 ring-1 ring-white dark:ring-slate-900 {{ $starSizeClasses }}" aria-hidden="true">
                        <svg class="size-2" viewBox="0 0 24 24" fill="currentColor">
                            <path d="m12 2.75 2.78 5.63 6.22.9-4.5 4.39 1.06 6.19L12 16.93l-5.56 2.93 1.06-6.19L3 9.28l6.22-.9L12 2.75Z" />
                        </svg>
                    </span>
                @endif
            </span>
        @endforeach

        @if ($hiddenSupportersCount > 0)
            <span
                class="{{ $morePillClasses }} {{ $stackSpacing }} inline-flex shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-100 font-semibold text-slate-600 ring-2 ring-white dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-900"
                title="{{ $hiddenSupportersCount }} more supporters"
                aria-label="{{ $hiddenSupportersCount }} more supporters"
            >
                +{{ $hiddenSupportersCount }}
            </span>
        @endif
    </span>
@elseif ($showEmpty)
    <span {{ $attributes->merge(['class' => 'text-sm font-normal text-slate-500 dark:text-slate-400']) }}>
        No votes yet.
    </span>
@endif
