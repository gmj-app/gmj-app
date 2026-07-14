@props(['recommendation'])

@php
    $thumbnail = $recommendation->displayThumbnailUrl();
    $closedAt = $recommendation->resolved_at ?? $recommendation->updated_at ?? $recommendation->created_at;
    $support = (int) ($recommendation->historical_support_count ?? 0);
@endphp

<article id="recommendation-{{ $recommendation->id }}" class="scroll-mt-28 overflow-hidden rounded-2xl border border-slate-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
    <div class="flex min-w-0 gap-4 p-4 sm:p-5">
        @if ($thumbnail)
            <img src="{{ $thumbnail }}" alt="" class="h-20 w-28 shrink-0 rounded-xl bg-slate-100 object-cover dark:bg-slate-800 sm:h-24 sm:w-36" loading="lazy">
        @else
            <div class="flex h-20 w-28 shrink-0 items-center justify-center rounded-xl bg-slate-100 text-xs font-bold uppercase tracking-wide text-slate-400 dark:bg-slate-800 dark:text-slate-500 sm:h-24 sm:w-36" aria-hidden="true">{{ $recommendation->mediaTypeLabel() }}</div>
        @endif

        <div class="min-w-0 flex-1">
            <div class="flex flex-wrap items-start justify-between gap-2">
                <div class="min-w-0">
                    <h2 class="break-words text-base font-extrabold text-slate-950 dark:text-white sm:text-lg">
                        @if ($recommendation->canonicalMediaUrl())
                            <a href="{{ $recommendation->canonicalMediaUrl() }}" target="_blank" rel="noopener noreferrer nofollow ugc" class="hover:text-indigo-600 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-indigo-500 dark:hover:text-indigo-300">{{ $recommendation->displayTitle() }}<span class="sr-only"> (opens source in a new tab)</span></a>
                        @else
                            {{ $recommendation->displayTitle() }}
                        @endif
                    </h2>
                    @if ($recommendation->displaySourceChannel())
                        <p class="mt-1 truncate text-sm text-slate-600 dark:text-slate-300">{{ $recommendation->displaySourceChannel() }}</p>
                    @endif
                </div>
                <x-requests.status-badge :request="$recommendation" />
            </div>

            <dl class="mt-3 flex flex-wrap gap-x-4 gap-y-1 text-xs font-medium text-slate-500 dark:text-slate-400">
                <div><dt class="sr-only">Requester</dt><dd>{{ $recommendation->submittedBy ? 'Requested by '.$recommendation->submittedBy->displayName() : 'Creator request' }}</dd></div>
                <div><dt class="sr-only">Historical support</dt><dd>{{ $support }} historical {{ Str::plural('vote', $support) }}</dd></div>
                <div><dt class="sr-only">Closed date</dt><dd>Closed {{ $closedAt?->format('M j, Y') }}</dd></div>
            </dl>

            @if ($recommendation->public_resolution_note)
                <p class="mt-3 text-sm leading-6 text-slate-600 dark:text-slate-300">{{ $recommendation->public_resolution_note }}</p>
            @endif

            @if ($recommendation->status === 'already_seen' && $recommendation->prior_coverage_url)
                <a href="{{ $recommendation->prior_coverage_url }}" target="_blank" rel="noopener noreferrer" class="mt-3 inline-flex text-sm font-bold text-indigo-600 hover:text-indigo-500 focus:outline-none focus-visible:rounded focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-indigo-400">
                    {{ $recommendation->prior_coverage_title ?: 'View prior coverage' }}
                    <span class="sr-only"> (opens in a new tab)</span>
                </a>
            @endif
        </div>
    </div>
</article>
