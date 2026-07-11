@props([
    'user',
    'size' => 'md',
])

@php
    $sizeClasses = match ($size) {
        'xs' => 'size-6 text-[10px]',
        'sm' => 'size-8 text-xs',
        'lg' => 'size-16 text-xl',
        'xl' => 'size-20 text-2xl sm:size-24 sm:text-3xl',
        default => 'size-9 text-sm sm:size-10',
    };
    $accolade = $user->guideAvatarAccolade();
    $numberLabel = $accolade['plate_text'] ?? null;
    $ringClass = match ($accolade['css_variant'] ?? null) {
        'gold' => 'ring-[3px] ring-yellow-400',
        'silver' => 'ring-[3px] ring-slate-300 shadow-[0_0_0_1px_rgba(255,255,255,0.22)]',
        default => $user->guideAvatarRingClass(),
    };
    $plateClass = match ($accolade['css_variant'] ?? null) {
        'silver' => 'border-slate-200/80 bg-gradient-to-br from-slate-500 via-slate-700 to-slate-950 text-white shadow-[0_1px_4px_rgba(148,163,184,0.45)]',
        default => 'border-yellow-400/70 bg-slate-950/95 text-yellow-300 shadow-sm',
    };
@endphp

<span {{ $attributes->class(['relative inline-flex shrink-0 overflow-visible rounded-full']) }}>
    @if (filled($user->avatar_url))
        <img
            src="{{ $user->avatar_url }}"
            alt=""
            loading="lazy"
            class="{{ $sizeClasses }} {{ $ringClass }} rounded-full bg-slate-200 object-cover dark:bg-slate-700"
            onerror="this.hidden = true; this.nextElementSibling.classList.remove('hidden')"
        >
        <span class="hidden {{ $sizeClasses }} {{ $ringClass }} items-center justify-center rounded-full bg-slate-200 font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-100">{{ $user->initialsForAvatar() }}</span>
    @else
        <span class="{{ $sizeClasses }} {{ $ringClass }} inline-flex items-center justify-center rounded-full bg-slate-200 font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-100">{{ $user->initialsForAvatar() }}</span>
    @endif

    @if ($numberLabel)
        <span class="absolute bottom-0 left-1/2 z-10 -translate-x-1/2 translate-y-1/3 rounded-md border px-1 py-0.5 text-[8px] font-bold leading-none {{ $plateClass }}" aria-hidden="true">{{ $numberLabel }}</span>
    @endif
</span>
