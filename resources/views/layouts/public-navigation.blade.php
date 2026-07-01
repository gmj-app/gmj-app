<nav
    x-data="{
        open: false,
        dark: document.documentElement.classList.contains('dark'),
        toggleTheme() {
            this.dark = ! this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        },
    }"
    class="sticky top-0 z-40 border-b border-white/70 bg-white/85 backdrop-blur-xl dark:border-slate-800/80 dark:bg-slate-950/85"
>
    <div class="mx-auto flex h-16 max-w-7xl min-w-0 items-center justify-between gap-2 px-4 sm:px-6 lg:px-8">
        <a href="{{ route('home') }}" class="min-w-0" aria-label="Guide My Journey home">
            <x-application-logo size="sm" />
        </a>

        <div class="hidden items-center gap-6 text-sm font-semibold text-slate-600 dark:text-slate-200 md:flex">
            <a href="{{ route('home') }}" class="transition hover:text-indigo-600 dark:hover:text-white">Explore</a>
            <a href="{{ route('about') }}" class="transition hover:text-indigo-600 dark:hover:text-white">How it Works</a>
            <a href="{{ route('faq') }}" class="transition hover:text-indigo-600 dark:hover:text-white">FAQ</a>
            @auth
                <a href="{{ route('dashboard') }}" class="font-bold text-indigo-600 transition hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200">My Hub</a>
            @endauth
        </div>

        <div class="flex shrink-0 items-center gap-2">
            <button
                type="button"
                @click="toggleTheme()"
                class="hidden size-9 items-center justify-center rounded-full text-slate-500 transition hover:bg-slate-100 hover:text-indigo-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 dark:text-slate-400 dark:hover:bg-slate-800 dark:hover:text-indigo-300 md:inline-flex"
                aria-label="Toggle light and dark mode"
                title="Toggle light and dark mode"
            >
                <svg x-show="! dark" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
                </svg>
                <svg x-show="dark" x-cloak class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="12" cy="12" r="4" />
                    <path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" />
                </svg>
            </button>

            <div class="hidden items-center gap-1 md:flex">
                @auth
                    <a href="{{ route('profile.edit') }}" class="rounded-full px-3 py-2 text-sm font-semibold text-slate-500 transition hover:text-indigo-600 dark:text-slate-400 dark:hover:text-white">
                        Profile
                    </a>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="rounded-full px-3 py-2 text-sm font-semibold text-slate-500 transition hover:text-indigo-600 dark:text-slate-400 dark:hover:text-white">
                            Log out
                        </button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-500">
                        Sign in
                    </a>
                @endauth
            </div>

            <button
                type="button"
                @click="open = ! open"
                class="inline-flex h-11 w-11 items-center justify-center rounded-full border border-slate-200 bg-white p-2 text-slate-600 shadow-sm dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 md:hidden"
                aria-label="Open navigation"
            >
                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path :class="{ 'hidden': open }" class="block" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16" />
                    <path :class="{ 'hidden': ! open }" class="hidden" stroke-linecap="round" d="M6 6l12 12M18 6L6 18" />
                </svg>
            </button>
        </div>
    </div>

    <div x-show="open" x-cloak class="border-t border-slate-200 bg-white px-4 py-4 dark:border-slate-800 dark:bg-slate-950 md:hidden">
        <div class="grid gap-2 text-sm font-bold text-slate-700 dark:text-slate-200">
            <a href="{{ route('home') }}" class="flex min-h-11 items-center rounded-xl px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-900">Explore</a>
            <a href="{{ route('about') }}" class="flex min-h-11 items-center rounded-xl px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-900">How it Works</a>
            <a href="{{ route('faq') }}" class="flex min-h-11 items-center rounded-xl px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-900">FAQ</a>
            <a href="{{ route('contact') }}" class="flex min-h-11 items-center rounded-xl px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-900">Contact</a>

            @auth
                <a href="{{ route('dashboard') }}" class="flex min-h-11 items-center rounded-xl px-3 py-2 text-indigo-600 hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-indigo-950/50">My Hub</a>
                <a href="{{ route('profile.edit') }}" class="flex min-h-11 items-center rounded-xl px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-900">Profile</a>
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="min-h-11 w-full rounded-xl px-3 py-2 text-left hover:bg-slate-100 dark:hover:bg-slate-900">Log out</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="flex min-h-11 items-center rounded-xl bg-indigo-600 px-3 py-2 text-white">Sign in</a>
            @endauth

            <button
                type="button"
                @click="toggleTheme()"
                class="mt-2 flex min-h-11 items-center gap-3 rounded-xl border-t border-slate-200 px-3 pt-3 text-left text-slate-600 hover:bg-slate-100 dark:border-slate-800 dark:text-slate-300 dark:hover:bg-slate-900"
                aria-label="Toggle light and dark mode"
            >
                <svg x-show="! dark" class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
                </svg>
                <svg x-show="dark" x-cloak class="size-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <circle cx="12" cy="12" r="4" />
                    <path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" />
                </svg>
                <span x-text="dark ? 'Use light theme' : 'Use dark theme'"></span>
            </button>
        </div>
    </div>
</nav>
