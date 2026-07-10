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
    $numberLabel = $user->foundingGuideNumberLabel();
@endphp

<span {{ $attributes->class(['relative inline-flex shrink-0 overflow-visible rounded-full']) }}>
    @if (filled($user->avatar_url))
        <img
            src="{{ $user->avatar_url }}"
            alt=""
            loading="lazy"
            class="{{ $sizeClasses }} {{ $user->guideAvatarRingClass() }} rounded-full bg-slate-200 object-cover dark:bg-slate-700"
            onerror="this.hidden = true; this.nextElementSibling.classList.remove('hidden')"
        >
        <span class="hidden {{ $sizeClasses }} {{ $user->guideAvatarRingClass() }} items-center justify-center rounded-full bg-slate-200 font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-100">{{ $user->initialsForAvatar() }}</span>
    @else
        <span class="{{ $sizeClasses }} {{ $user->guideAvatarRingClass() }} inline-flex items-center justify-center rounded-full bg-slate-200 font-semibold text-slate-700 dark:bg-slate-700 dark:text-slate-100">{{ $user->initialsForAvatar() }}</span>
    @endif

    @if ($numberLabel)
        <span class="absolute bottom-0 left-1/2 z-10 -translate-x-1/2 translate-y-1/3 rounded-md border border-yellow-400/70 bg-slate-950/95 px-1 py-0.5 text-[8px] font-bold leading-none text-yellow-300 shadow-sm" aria-hidden="true">{{ $numberLabel }}</span>
    @endif
</span>
