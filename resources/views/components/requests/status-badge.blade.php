@props(['request', 'variant' => 'standard'])
@php
    $status = \App\Presenters\RequestStatusPresenter::for($request->status);
    $shouldRender = $status['public'] && ($variant !== 'compact' || $status['show_compact']);
@endphp

@if ($shouldRender)
    <span
        data-request-status="{{ $request->status }}"
        data-status-style="{{ $status['style_key'] }}"
        data-status-variant="{{ $variant }}"
        aria-label="{{ $status['description'] }}"
        {{ $attributes->class([
            'inline-flex shrink-0 items-center rounded-full font-semibold '.$status['classes'],
            'px-2 py-0.5 text-[11px] leading-5' => $variant === 'compact',
            'px-3 py-1.5 text-sm' => $variant !== 'compact',
        ]) }}
    >{{ $status['label'] }}</span>
@endif
