@props([
    'text',
    'label',
    'limit' => 250,
])

@php
    $body = trim((string) $text);
    $limit = (int) $limit;
    $isLong = mb_strlen($body) > $limit;
    $preview = Str::limit($body, $limit);
    $sectionId = 'plain-expandable-text-'.uniqid();
@endphp

@if ($body !== '')
    <section {{ $attributes->merge(['class' => 'mt-4']) }} x-data="{ expanded: false }">
        <x-subsection-label as="h4">{{ $label }}</x-subsection-label>

        @if ($isLong)
            <p
                id="{{ $sectionId }}"
                x-show="! expanded"
                class="mt-2 whitespace-pre-line break-words text-base leading-7 text-slate-600 [overflow-wrap:anywhere] dark:text-slate-300"
            >{{ $preview }}</p>
            <p
                x-show="expanded"
                x-cloak
                class="mt-2 whitespace-pre-line break-words text-base leading-7 text-slate-600 [overflow-wrap:anywhere] dark:text-slate-300"
            >{{ $body }}</p>

            <button
                type="button"
                x-on:click="expanded = ! expanded"
                x-bind:aria-expanded="expanded.toString()"
                aria-controls="{{ $sectionId }}"
                class="mt-2 inline-flex text-sm font-semibold text-indigo-600 hover:text-indigo-500 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-indigo-400 dark:hover:text-indigo-300"
            >
                <span x-text="expanded ? 'Show less' : 'Read more'">Read more</span>
            </button>
        @else
            <p class="mt-2 whitespace-pre-line break-words text-base leading-7 text-slate-600 [overflow-wrap:anywhere] dark:text-slate-300">{{ $body }}</p>
        @endif
    </section>
@endif
