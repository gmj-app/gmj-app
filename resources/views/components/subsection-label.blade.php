@props(['as' => 'p'])

<{{ $as }} {{ $attributes->class('text-xs font-semibold uppercase tracking-[0.08em] text-slate-500 dark:text-slate-400') }}>{{ $slot }}</{{ $as }}>
