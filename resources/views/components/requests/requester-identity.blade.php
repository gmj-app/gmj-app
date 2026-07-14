@props(['requester'])

@if ($requester)
    @php($profileUrl = $requester->publicGuideProfileUrl())
    <div data-requester-identity class="mt-3 flex min-w-0 items-center gap-3">
        @if ($profileUrl)
            <a href="{{ $profileUrl }}" class="shrink-0 rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" aria-label="View {{ $requester->publicName() }}'s Guide profile">
                <x-guide-avatar :user="$requester" size="requester" />
            </a>
        @else
            <x-guide-avatar :user="$requester" size="requester" />
        @endif
        <div class="min-w-0">
            @if ($profileUrl)
                <a href="{{ $profileUrl }}" class="block truncate text-sm font-bold text-slate-800 hover:text-indigo-600 dark:text-slate-100 dark:hover:text-indigo-300">{{ $requester->publicName() }}</a>
            @else
                <p class="truncate text-sm font-bold text-slate-800 dark:text-slate-100">{{ $requester->publicName() }}</p>
            @endif
            @if ($requester->guideNumberLabel())
                <p class="mt-1 text-xs font-semibold text-slate-500 dark:text-slate-400">Guide {{ $requester->guideNumberLabel() }}</p>
            @endif
        </div>
    </div>
@else
    <p data-requester-identity class="mt-3 text-sm font-normal text-slate-500 dark:text-slate-400">Unknown Guide</p>
@endif
