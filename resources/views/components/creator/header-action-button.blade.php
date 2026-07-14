@props([
    'as' => 'a',
    'variant' => 'secondary',
])

@php
    $variantClasses = match ($variant) {
        'primary' => 'border-transparent bg-indigo-600 text-white shadow-lg shadow-indigo-950/25 hover:bg-indigo-500',
        'primary-disabled' => 'pointer-events-none border-transparent bg-slate-500 text-white shadow-none',
        'selected' => 'border-indigo-400/70 bg-indigo-500/20 text-white shadow-[0_0_0_1px_rgba(129,140,248,0.08),0_8px_24px_rgba(79,70,229,0.16)] hover:border-indigo-300 hover:bg-indigo-500/30',
        'channel' => 'border-white/25 bg-white/15 text-white shadow-sm hover:border-indigo-300/70 hover:bg-white/25',
        'unavailable' => 'cursor-not-allowed border-white/10 bg-slate-900/60 text-slate-400 opacity-80',
        default => 'border-white/25 bg-slate-950/65 text-white shadow-sm hover:border-indigo-300/70 hover:bg-indigo-500/15',
    };
@endphp

<{{ $as }}
    data-creator-header-action
    {{ $attributes->class([
        'inline-flex min-h-16 w-full min-w-0 items-center justify-center gap-2.5 rounded-xl border px-5 py-3 text-center font-bold leading-tight backdrop-blur transition duration-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-300 focus-visible:ring-offset-2 focus-visible:ring-offset-slate-900 sm:w-auto sm:min-w-36',
        $variantClasses,
    ]) }}
>{{ $slot }}</{{ $as }}>
