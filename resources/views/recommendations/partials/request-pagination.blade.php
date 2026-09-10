<section aria-label="Request pagination, {{ $position }}" data-request-pagination-controls class="flex min-w-0 flex-col gap-3 pt-2 sm:flex-row sm:flex-wrap sm:items-center sm:justify-between">
    <p class="text-sm text-slate-600 dark:text-slate-300" aria-live="polite">
        @if ($recommendations->total() > 0)
            Showing <span class="font-medium">{{ $recommendations->firstItem() }}</span> to <span class="font-medium">{{ $recommendations->lastItem() }}</span> of <span class="font-medium">{{ $recommendations->total() }}</span> results
        @else
            Showing 0 results
        @endif
    </p>

    <form method="GET" action="{{ route('creator.queue', $creator) }}" class="flex min-w-0 items-center gap-2" data-request-per-page-form>
        @if($duplicateSource)<input type="hidden" name="duplicate_source" value="{{ $duplicateSource->id }}">@endif
        @foreach (['q', 'status', 'category', 'tag', 'sort'] as $parameter)
            @if ($filters[$parameter] !== '' && ! ($parameter === 'sort' && $filters[$parameter] === 'votes'))
                <input type="hidden" name="{{ $parameter }}" value="{{ $filters[$parameter] }}">
            @endif
        @endforeach

        <label for="requests-per-page-{{ $position }}" class="shrink-0 text-sm font-medium text-slate-700 dark:text-slate-200">Show</label>
        <select
            id="requests-per-page-{{ $position }}"
            name="per_page"
            aria-label="Requests per page, {{ $position }}"
            class="min-h-11 rounded-xl border-slate-300 bg-white py-2 pl-3 pr-8 text-base text-slate-950 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-slate-700 dark:bg-slate-950 dark:text-white sm:text-sm"
            onchange="this.form.requestSubmit()"
        >
            @foreach ($perPageOptions as $option)
                <option value="{{ $option }}" @selected($perPage === $option)>{{ $option }}</option>
            @endforeach
        </select>
        <span class="shrink-0 text-sm text-slate-600 dark:text-slate-300">per page</span>
        <noscript>
            <button type="submit" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-500">Apply</button>
        </noscript>
    </form>

    @if ($recommendations->hasPages())
        <div class="min-w-0 sm:ml-auto [&>nav>div:last-child>div:first-child]:hidden [&>nav>div:last-child]:justify-end">
            {{ $recommendations->links() }}
        </div>
    @endif
</section>
