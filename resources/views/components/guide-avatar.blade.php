@props([
    'user',
    'size' => 'md',
    'plateVariant' => 'compact',
])

@php
    $sizeClasses = match ($size) {
        'xs' => 'size-6 text-[10px]',
        'sm' => 'size-8 text-xs',
        'supporter' => 'size-12 text-sm sm:size-14 sm:text-base',
        'requester' => 'size-14 text-base',
        'lg' => 'size-16 text-xl',
        'xl' => 'size-20 text-2xl sm:size-24 sm:text-3xl',
        default => 'size-9 text-sm sm:size-10',
    };
    $accolade = $user->guideAvatarAccolade();
    $numberLabel = $accolade['plate_text'] ?? null;
    $accoladeClass = $accolade['css_class'] ?? '';
    $numberPlateClasses = match ($plateVariant) {
        'profile' => 'min-w-7 h-[18px] px-1.5 text-[10px] font-extrabold border-2 sm:min-w-8 sm:h-5 sm:px-2 sm:text-[11px]',
        'standard' => 'min-w-6 h-4 px-1.5 text-[9px] font-bold',
        default => 'px-1 py-0.5 text-[clamp(7px,0.5rem,8px)] font-bold',
    };
@endphp

<span {{ $attributes->class(['guide-avatar relative inline-flex shrink-0 overflow-visible rounded-full', 'z-10' => $accolade !== null, $accoladeClass]) }}>
    @if (filled($user->avatar_url))
        <img
            src="{{ $user->avatar_url }}"
            alt=""
            loading="lazy"
            class="guide-avatar__image relative z-0 {{ $sizeClasses }} rounded-full bg-slate-200 object-cover dark:bg-slate-700"
            onerror="this.hidden = true; this.nextElementSibling.classList.remove('hidden')"
        >
        <span class="guide-avatar__image relative z-0 hidden {{ $sizeClasses }} items-center justify-center rounded-full bg-slate-200 font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-100">{{ $user->initialsForAvatar() }}</span>
    @else
        <span class="guide-avatar__image relative z-0 {{ $sizeClasses }} inline-flex items-center justify-center rounded-full bg-slate-200 font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-100">{{ $user->initialsForAvatar() }}</span>
    @endif

    @if ($accolade && $accolade['icon'])
        <span class="guide-accolade__icon absolute right-0 top-0 z-20" aria-hidden="true">{{ $accolade['icon'] }}</span>
    @endif

    @if ($accolade && $accolade['display_number_plate'] && $numberLabel)
        <span data-number-plate-variant="{{ $plateVariant }}" class="guide-accolade__number absolute bottom-0 left-1/2 z-30 inline-flex -translate-x-1/2 translate-y-1/3 items-center justify-center whitespace-nowrap rounded-md border leading-none {{ $numberPlateClasses }}" title="{{ $accolade['description'] }}" aria-label="{{ $accolade['name'] }} number {{ ltrim($numberLabel, '#') }}">{{ $numberLabel }}</span>
    @endif
</span>
