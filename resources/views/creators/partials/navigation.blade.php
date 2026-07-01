<nav class="flex flex-wrap gap-2" aria-label="Creator dashboard">
    <a href="{{ route('creators.dashboard', $creator) }}" class="rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('creators.dashboard') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-800 dark:hover:text-white' }}">
        Overview
    </a>
    <a href="{{ route('creators.recommendations.index', $creator) }}" class="rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('creators.recommendations.*') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-800 dark:hover:text-white' }}">
        Recommendations
    </a>
    <a href="{{ route('creators.starter-suggestions.create', $creator) }}" class="rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('creators.starter-suggestions.*') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-800 dark:hover:text-white' }}">
        Add suggestions
    </a>
    <a href="{{ route('creators.followers', $creator) }}" class="rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('creators.followers') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-800 dark:hover:text-white' }}">
        Audience
    </a>
    <a href="{{ route('creators.settings.edit', $creator) }}" class="rounded-md px-3 py-2 text-sm font-semibold {{ request()->routeIs('creators.settings.*') ? 'bg-indigo-600 text-white' : 'bg-white text-gray-700 ring-1 ring-gray-200 hover:bg-gray-50 dark:bg-slate-900 dark:text-slate-200 dark:ring-slate-700 dark:hover:bg-slate-800 dark:hover:text-white' }}">
        Settings
    </a>
</nav>
