@props([
    'variant' => 'full',
    'size' => 'md',
])

@php
    $sizes = [
        'sm' => [
            'icon' => 'h-8 w-8',
            'text' => 'text-sm sm:text-base',
            'gap' => 'gap-2',
        ],
        'md' => [
            'icon' => 'h-10 w-10',
            'text' => 'text-base sm:text-lg',
            'gap' => 'gap-2.5',
        ],
        'lg' => [
            'icon' => 'h-16 w-16',
            'text' => 'text-xl sm:text-2xl',
            'gap' => 'gap-3',
        ],
    ];

    $selectedSize = $sizes[$size] ?? $sizes['md'];
    $showWordmark = $variant !== 'icon';
@endphp

<span {{ $attributes->class(['inline-flex min-w-0 items-center', $selectedSize['gap']]) }}>
    <svg
        viewBox="0 0 160 160"
        class="{{ $selectedSize['icon'] }} shrink-0"
        xmlns="http://www.w3.org/2000/svg"
        @if ($showWordmark)
            aria-hidden="true"
        @else
            role="img"
            aria-label="Guide My Journey"
        @endif
    >
        <defs>
            <linearGradient id="gmj-journey-gradient" x1="16" y1="132" x2="138" y2="22" gradientUnits="userSpaceOnUse">
                <stop offset="0" stop-color="#1D35C9" />
                <stop offset="0.55" stop-color="#60A5FA" />
                <stop offset="1" stop-color="#8B5CF6" />
            </linearGradient>
        </defs>

        <g fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path d="M88 24C56 25 31 51 31 83C31 116 57 139 90 133C112 129 128 111 130 88" stroke="url(#gmj-journey-gradient)" stroke-width="11" />
            <path d="M74 80H126" stroke="url(#gmj-journey-gradient)" stroke-width="11" />
            <circle cx="31" cy="93" r="12" fill="#2337C8" />
            <circle cx="88" cy="24" r="12" fill="#8B5CF6" />
            <circle cx="126" cy="80" r="12" fill="#77B6F8" />
        </g>
    </svg>

    @if ($showWordmark)
        <span class="truncate font-bold tracking-tight text-slate-950 dark:text-white {{ $selectedSize['text'] }}">
            Guide My Journey
        </span>
    @endif
</span>
