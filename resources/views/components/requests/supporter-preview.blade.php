@props(['recommendation'])

@php
    $preview = $recommendation->relationLoaded('userPicks')
        ? $recommendation->userPicks->take(6)
        : collect();
    $total = (int) ($recommendation->active_supporters_count ?? $preview->count());
    $remaining = max($total - $preview->count(), 0);
@endphp

<div
    data-supporter-preview
    data-supporter-preview-count="{{ $preview->count() }}"
    data-supporter-total="{{ $total }}"
    x-data="{
        open: false,
        loading: false,
        loaded: false,
        error: false,
        directoryHtml: '',
        nextPage: 1,
        returnFocus: null,
        async loadSupporters(reset = false) {
            if (this.loading || (! reset && this.nextPage === null)) return;
            this.loading = true;
            this.error = false;
            const page = reset ? 1 : this.nextPage;
            try {
                const response = await fetch(`${@js(route('requests.supporters', $recommendation))}?page=${page}`, {
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    credentials: 'same-origin',
                });
                if (! response.ok) throw new Error(`Request failed: ${response.status}`);
                const payload = await response.json();
                this.directoryHtml = reset ? payload.html : this.directoryHtml + payload.html;
                this.nextPage = payload.next_page;
                this.loaded = true;
            } catch (error) {
                this.error = true;
            } finally {
                this.loading = false;
            }
        },
        showDirectory() {
            this.returnFocus = this.$refs.overflow;
            this.open = true;
            if (! this.loaded) this.loadSupporters(true);
            this.$nextTick(() => this.$refs.close?.focus());
        },
        closeDirectory() {
            this.open = false;
            this.$nextTick(() => this.returnFocus?.focus());
        },
    }"
    x-on:keydown.escape.window="if (open) closeDirectory()"
>
    @if ($preview->isNotEmpty())
        <div class="mt-3 grid grid-cols-3 gap-x-3 gap-y-5 sm:grid-cols-4 lg:grid-cols-7" aria-label="Supporter preview">
            @foreach ($preview as $pick)
                @if ($pick->user)
                    <x-requests.supporter-identity :user="$pick->user" />
                @endif
            @endforeach

            @if ($remaining > 0)
                <button
                    x-ref="overflow"
                    type="button"
                    x-on:click="showDirectory()"
                    x-bind:disabled="loading"
                    aria-label="View {{ $remaining }} more {{ Str::plural('supporter', $remaining) }}"
                    class="group flex min-w-0 flex-col items-center text-center focus:outline-none disabled:cursor-wait disabled:opacity-70"
                >
                    <span class="inline-flex size-12 items-center justify-center rounded-full border border-indigo-300 bg-indigo-50 text-sm font-extrabold text-indigo-700 transition group-hover:border-indigo-400 group-hover:bg-indigo-100 group-focus-visible:ring-2 group-focus-visible:ring-indigo-500 group-focus-visible:ring-offset-2 dark:border-indigo-500/50 dark:bg-indigo-500/15 dark:text-indigo-200 dark:group-hover:bg-indigo-500/25 sm:size-14">
                        <span x-show="! loading">+{{ $remaining }}</span>
                        <svg x-show="loading" x-cloak class="size-5 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true"><circle cx="12" cy="12" r="9" stroke="currentColor" stroke-opacity=".25" stroke-width="3"/><path d="M21 12a9 9 0 0 0-9-9" stroke="currentColor" stroke-width="3" stroke-linecap="round"/></svg>
                    </span>
                    <span class="mt-3 block w-full truncate text-xs font-semibold text-indigo-700 dark:text-indigo-200" x-text="loading ? 'Loading supporters…' : 'View all'">View all</span>
                </button>
            @endif
        </div>
    @else
        <p class="mt-3 text-sm font-normal text-slate-500 dark:text-slate-400">No votes yet.</p>
    @endif

    <template x-teleport="body">
        <div x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center bg-slate-950/70 px-3 py-6 backdrop-blur-sm sm:px-6" role="dialog" aria-modal="true" aria-labelledby="supporter-directory-title-{{ $recommendation->id }}" x-on:click.self="closeDirectory()">
            <div class="flex max-h-[min(46rem,90vh)] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-2xl dark:border-slate-700 dark:bg-slate-900">
                <div class="flex items-start justify-between gap-4 border-b border-slate-200 px-5 py-4 dark:border-slate-800">
                    <div>
                        <h3 id="supporter-directory-title-{{ $recommendation->id }}" class="text-lg font-extrabold text-slate-950 dark:text-white">Community Support</h3>
                        <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ $total }} {{ Str::plural('Guide', $total) }} supported this request.</p>
                    </div>
                    <button x-ref="close" type="button" x-on:click="closeDirectory()" aria-label="Close Community Support" class="inline-flex size-11 shrink-0 items-center justify-center rounded-xl text-slate-500 hover:bg-slate-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-slate-300 dark:hover:bg-slate-800">
                        <svg class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path stroke-linecap="round" d="M6 6l12 12M18 6 6 18"/></svg>
                    </button>
                </div>
                <div class="min-h-40 overflow-y-auto p-5">
                    <div x-show="loading && ! loaded" class="flex min-h-32 items-center justify-center text-sm font-semibold text-slate-500" role="status">Loading supporters&hellip;</div>
                    <div x-show="error" role="alert" class="rounded-xl border border-red-200 bg-red-50 p-4 text-center text-sm font-semibold text-red-800 dark:border-red-900 dark:bg-red-950/30 dark:text-red-200">
                        Supporters could not be loaded.
                        <button type="button" x-on:click="loadSupporters(! loaded)" class="ml-1 underline focus:outline-none focus-visible:ring-2 focus-visible:ring-red-500">Try again.</button>
                    </div>
                    <div x-show="loaded" x-html="directoryHtml" class="grid grid-cols-1 gap-4 sm:grid-cols-2"></div>
                    <div class="mt-5 text-center" x-show="loaded && nextPage !== null">
                        <button type="button" x-on:click="loadSupporters()" x-bind:disabled="loading" class="inline-flex min-h-11 items-center justify-center rounded-xl bg-indigo-600 px-5 py-2.5 text-sm font-bold text-white hover:bg-indigo-500 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 disabled:opacity-60" x-text="loading ? 'Loading supporters…' : 'Load more supporters'">Load more supporters</button>
                    </div>
                </div>
            </div>
        </div>
    </template>
</div>
