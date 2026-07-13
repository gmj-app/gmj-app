@props(['item', 'compact' => false])
<article @class(['relative flex gap-3', 'p-3' => $compact, 'rounded-2xl border p-4 sm:p-5' => !$compact, 'border-indigo-200 bg-indigo-50/70 dark:border-indigo-800 dark:bg-indigo-950/30' => !$item->isRead() && !$compact, 'border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900' => $item->isRead() && !$compact])>
    <x-notifications.icon :name="$item->icon()" :severity="$item->severity()" />
    <div class="min-w-0 flex-1">
        <div class="flex items-start justify-between gap-2"><h3 class="font-extrabold text-slate-950 dark:text-white">{{ $item->title() }}</h3>@unless($item->isRead())<span class="mt-1 size-2 shrink-0 rounded-full bg-indigo-600" aria-label="Unread"></span>@endunless</div>
        <p class="mt-1 text-sm leading-5 text-slate-600 dark:text-slate-300 {{ $compact ? 'line-clamp-2' : '' }}">{{ $item->message() }}</p>
        <div class="mt-2 flex flex-wrap items-center gap-2 text-xs text-slate-500 dark:text-slate-400"><span>{{ $item->categoryLabel() }}</span><span aria-hidden="true">&middot;</span><time datetime="{{ $item->createdAt()?->toIso8601String() }}">{{ $item->relativeTime() }}</time></div>
        @unless($compact)<div class="mt-3 flex flex-wrap gap-3"><a href="{{ route('notifications.open', $item->id()) }}" class="font-bold text-indigo-600 dark:text-indigo-300">{{ $item->actionLabel() ?: 'Open notification' }}</a><form method="POST" action="{{ route($item->isRead() ? 'notifications.unread' : 'notifications.read', $item->id()) }}">@csrf<button class="text-sm font-bold text-slate-500">Mark {{ $item->isRead() ? 'unread' : 'read' }}</button></form></div>@endunless
    </div>
</article>
