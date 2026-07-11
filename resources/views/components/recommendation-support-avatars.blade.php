@props([
    'recommendation',
    'limit' => 10,
    'size' => 'sm',
    'includeRequester' => true,
    'includeUpvoters' => true,
    'skipRequesterUpvote' => false,
    'showEmpty' => false,
    'layout' => 'stack',
    'showNames' => false,
])

@php
    $requester = $recommendation->submittedBy;
    $upvoters = $recommendation->relationLoaded('userPicks')
        ? $recommendation->userPicks->pluck('user')->filter()
        : collect();

    $supporters = collect();

    if ($includeRequester && $requester) {
        $contextLine = "Suggested by {$requester->publicName()}";
        $accoladeLines = $requester->guideAccoladeTooltipLines();
        $ariaAccoladeLine = $requester->guideAccoladeAriaLine();

        $supporters->push([
            'user' => $requester,
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
    $isDetailLayout = $layout === 'detail';
    $isCompactDetailLayout = $isDetailLayout && $supporters->count() > 20;
    $showsSeparatedNames = $showNames && $isDetailLayout && ! $isCompactDetailLayout;
    $hasVisibleEarlyGuide = $visibleSupporters->contains(
        fn (array $supporter): bool => $supporter['user']->guideAvatarAccolade() !== null
    );
    $avatarSizeClasses = match (true) {
        $isDetailLayout && ! $isCompactDetailLayout => 'size-9 text-sm sm:size-10',
        $isCompactDetailLayout, $size === 'md' => 'size-8 text-xs',
        default => 'size-6 text-[10px]',
    };
    $stackSpacing = $size === 'md' || $isCompactDetailLayout ? '-ml-2' : '-ml-1.5';
    $morePillClasses = $isDetailLayout
        ? 'h-8 px-2 text-xs'
        : ($size === 'md' ? 'h-8 px-2 text-xs' : 'h-6 px-1.5 text-[10px]');
    $supporterRows = $isCompactDetailLayout
        ? $visibleSupporters->chunk(10)
        : collect([$visibleSupporters]);
@endphp

@if ($visibleSupporters->isNotEmpty())
    <span {{ $attributes->merge(['class' => 'min-w-0 overflow-visible'.($showsSeparatedNames ? ' grid w-full grid-cols-4 gap-x-3 gap-y-4 sm:grid-cols-5 lg:grid-cols-6 xl:grid-cols-8' : ' flex items-center').($isDetailLayout && ! $showsSeparatedNames ? ' flex-wrap gap-x-2 gap-y-3' : '').($hasVisibleEarlyGuide ? ' pb-1' : '')]) }}>
        @foreach ($supporterRows as $supporterRow)
            <span class="{{ $showsSeparatedNames ? 'contents' : 'inline-flex' }} items-center overflow-visible {{ $isCompactDetailLayout ? 'pb-1' : ($isDetailLayout && ! $showsSeparatedNames ? 'flex-wrap gap-2 pb-1' : '') }}">
            @foreach ($supporterRow as $supporter)
            @php
                $user = $supporter['user'];
                $initials = $user->initialsForAvatar();
                $titleLines = $supporter['titleLines'];
                $profileUrl = $user->publicGuideProfileUrl();
            @endphp

            <span class="{{ $showsSeparatedNames ? 'inline-flex min-w-0 flex-col items-center' : 'contents' }}">
            <{{ $profileUrl ? 'a' : 'span' }}
                @if ($profileUrl) href="{{ $profileUrl }}" @endif
                class="relative inline-flex shrink-0 overflow-visible rounded-full ring-2 ring-white first:ml-0 hover:z-20 focus-within:z-20 dark:ring-slate-900 {{ ! $loop->first && ! ($isDetailLayout && ! $isCompactDetailLayout) ? $stackSpacing : '' }}"
                @if (! $profileUrl) tabindex="0" @endif
                title="{!! collect($titleLines)->map(fn (string $line): string => e($line))->implode('&#10;') !!}"
                aria-label="{{ $profileUrl ? 'View '.$user->publicName().'\'s Guide profile' : $supporter['ariaLabel'] }}"
            >
                <x-guide-avatar :user="$user" :size="$isDetailLayout && ! $isCompactDetailLayout ? 'md' : ($size === 'md' || $isCompactDetailLayout ? 'sm' : 'xs')" />
            </{{ $profileUrl ? 'a' : 'span' }}>
            @if ($showsSeparatedNames)
                <span data-supporter-name class="mt-3 block w-full max-w-[88px] truncate text-center text-xs font-medium text-slate-600 dark:text-slate-300">
                    @if ($profileUrl)
                        <a href="{{ $profileUrl }}" class="hover:text-indigo-600 dark:hover:text-indigo-300">{{ $user->publicName() }}</a>
                    @else
                        {{ $user->publicName() }}
                    @endif
                </span>
            @endif
            </span>
            @endforeach
            </span>
        @endforeach

        @if ($hiddenSupportersCount > 0)
            <span
                class="{{ $morePillClasses }} {{ ! $isDetailLayout ? $stackSpacing : '' }} inline-flex shrink-0 items-center justify-center rounded-full border border-slate-200 bg-slate-100 font-semibold text-slate-600 ring-2 ring-white dark:border-slate-700 dark:bg-slate-800 dark:text-slate-300 dark:ring-slate-900"
                title="{{ $hiddenSupportersCount }} {{ $isDetailLayout ? 'additional' : 'more' }} supporters"
                aria-label="{{ $hiddenSupportersCount }} {{ $isDetailLayout ? 'additional' : 'more' }} supporters"
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
