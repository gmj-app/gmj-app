@props(['value', 'maximum', 'percent', 'barClass' => 'bg-slate-500', 'label'])

<div role="progressbar" aria-label="{{ $label }}" aria-valuemin="0" aria-valuemax="{{ max(1, (int) $maximum) }}" aria-valuenow="{{ min(max(0, (int) $value), max(1, (int) $maximum)) }}" class="h-2 overflow-hidden rounded-full bg-slate-200/80 dark:bg-slate-800">
    <div class="h-full rounded-full transition-[width] duration-300 {{ $barClass }}" style="width: {{ min(100, max(0, (int) $percent)) }}%"></div>
</div>
