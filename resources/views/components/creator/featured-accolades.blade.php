@props(['creator', 'accolades'])

@if ($accolades->isNotEmpty())
    <button
        type="button"
        x-on:click="accoladeOpen = true"
        class="mt-3 flex max-w-full flex-wrap gap-1.5 text-left focus:outline-none focus-visible:rounded-lg focus-visible:ring-2 focus-visible:ring-white"
        aria-label="View {{ $creator->display_name }} accolades"
    >
        @foreach ($accolades as $item)
            <span data-featured-creator-accolade class="max-w-48 truncate" title="{{ $item['definition']['description'] }}">
                <x-accolade-badge :definition="$item['definition']" size="sm" />
            </span>
        @endforeach
    </button>
@endif
