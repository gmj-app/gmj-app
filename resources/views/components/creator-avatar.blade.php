@props([
    'creator',
    'size' => 'md',
    'shape' => 'rounded',
])

@php
    $sizeClasses = [
        'sm' => 'h-8 w-8 text-xs',
        'md' => 'h-12 w-12 text-sm',
        'lg' => 'h-16 w-16 text-xl',
        'home' => 'h-16 w-16 text-xl 2xl:h-14 2xl:w-14 2xl:text-lg',
        'xl' => 'h-20 w-20 text-2xl md:h-24 md:w-24 md:text-3xl',
    ];
    $sizePixels = [
        'sm' => 32,
        'md' => 48,
        'lg' => 64,
        'home' => 64,
        'xl' => 96,
    ];
@endphp

<span
    {{ $attributes->class([
        'relative inline-flex shrink-0 items-center justify-center overflow-hidden bg-gradient-to-br from-indigo-500 to-violet-600 font-extrabold text-white shadow-sm',
        'rounded-full' => $shape === 'circle',
        'rounded-2xl' => $shape !== 'circle',
        $sizeClasses[$size] ?? $sizeClasses['md'],
    ]) }}
>
    <span
        role="img"
        aria-label="{{ $creator->display_name }} avatar"
        @if (filled($creator->avatar_url)) aria-hidden="true" @endif
    >{{ $creator->initials }}</span>

    @if (filled($creator->avatar_url))
        <img
            src="{{ $creator->avatar_url }}"
            alt="{{ $creator->display_name }} avatar"
            width="{{ $sizePixels[$size] ?? $sizePixels['md'] }}"
            height="{{ $sizePixels[$size] ?? $sizePixels['md'] }}"
            loading="lazy"
            class="absolute inset-0 h-full w-full bg-slate-100 object-cover dark:bg-slate-800"
            onerror="this.previousElementSibling.removeAttribute('aria-hidden'); this.remove()"
        >
    @endif
</span>
