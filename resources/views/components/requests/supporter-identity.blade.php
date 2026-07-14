@props(['user', 'directory' => false])

@php($profileUrl = $user->publicGuideProfileUrl())
<div data-supporter-identity class="flex min-w-0 {{ $directory ? 'items-center gap-3 text-left' : 'flex-col items-center text-center' }}">
    @if ($profileUrl)
        <a href="{{ $profileUrl }}" class="shrink-0 rounded-full focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500" aria-label="View {{ $user->publicName() }}'s Guide profile">
            <x-guide-avatar :user="$user" size="supporter" />
        </a>
    @else
        <x-guide-avatar :user="$user" size="supporter" />
    @endif
    <div class="min-w-0 {{ $directory ? '' : 'mt-3 w-full' }}">
        @if ($profileUrl)
            <a href="{{ $profileUrl }}" class="block truncate text-xs font-semibold text-slate-700 hover:text-indigo-600 dark:text-slate-200 dark:hover:text-indigo-300">{{ $user->publicName() }}</a>
        @else
            <p class="truncate text-xs font-semibold text-slate-700 dark:text-slate-200">{{ $user->publicName() }}</p>
        @endif
        @if ($directory && $user->guideNumberLabel())
            <p class="mt-1 text-xs text-slate-500 dark:text-slate-400">Guide {{ $user->guideNumberLabel() }}</p>
        @endif
    </div>
</div>
