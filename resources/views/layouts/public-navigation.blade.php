<nav
    x-data="{
        open: false,
        accountOpen: false,
        dark: document.documentElement.classList.contains('dark'),
        toggleTheme() {
            this.dark = ! this.dark;
            document.documentElement.classList.toggle('dark', this.dark);
            localStorage.setItem('theme', this.dark ? 'dark' : 'light');
        },
    }"
    @keydown.escape.window="open = false; accountOpen = false"
    class="sticky top-0 z-40 border-b border-white/70 bg-white/85 backdrop-blur-xl dark:border-slate-800/80 dark:bg-slate-950/85"
>
    @php
        $navLinkClass = fn (bool $active): string => $active
            ? 'font-bold text-indigo-600 transition hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200'
            : 'transition hover:text-indigo-600 dark:hover:text-white';
        $mobileNavLinkClass = fn (bool $active): string => $active
            ? 'flex min-h-11 items-center rounded-xl px-3 py-2 text-indigo-600 hover:bg-indigo-50 dark:text-indigo-300 dark:hover:bg-indigo-950/50'
            : 'flex min-h-11 items-center rounded-xl px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-900';
    @endphp

    <div class="mx-auto grid h-16 max-w-7xl min-w-0 grid-cols-[1fr_auto] items-center gap-2 px-4 sm:px-6 md:grid-cols-3 lg:px-8">
        <div class="flex min-w-0 justify-start">
            <a href="{{ route('home') }}" class="min-w-0" aria-label="Guide My Journey home">
                <x-application-logo size="sm" />
            </a>
        </div>

        <div class="hidden items-center justify-center gap-8 text-sm font-semibold text-slate-600 dark:text-slate-200 md:flex">
            <a href="{{ route('dashboard') }}" class="{{ $navLinkClass(request()->routeIs('dashboard')) }}">My Hub</a>
            <a href="{{ route('about') }}" class="{{ $navLinkClass(request()->routeIs('about')) }}">How it Works</a>
            <a href="{{ route('faq') }}" class="{{ $navLinkClass(request()->routeIs('faq')) }}">FAQ</a>
        </div>

        <div class="flex shrink-0 items-center justify-end gap-2">
            @auth
                @php($accountUser = auth()->user())
                <div class="relative" @click.outside="accountOpen = false">
                    <button
                        type="button"
                        @click="accountOpen = ! accountOpen"
                        class="inline-flex size-10 items-center justify-center overflow-hidden rounded-full border border-slate-200 bg-white text-sm font-extrabold text-indigo-700 shadow-sm transition hover:border-indigo-300 hover:bg-indigo-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-indigo-500 focus-visible:ring-offset-2 dark:border-slate-700 dark:bg-slate-900 dark:text-indigo-200 dark:hover:border-indigo-500 dark:hover:bg-slate-800 dark:focus-visible:ring-offset-slate-950"
                        aria-haspopup="menu"
                        :aria-expanded="accountOpen.toString()"
                        aria-label="Open account menu"
                    >
                        @if (filled($accountUser->avatar_url))
                            <img src="{{ $accountUser->avatar_url }}" alt="{{ $accountUser->publicName() }} avatar" class="h-full w-full object-cover" referrerpolicy="no-referrer">
                        @else
                            <span>{{ $accountUser->initialsForAvatar() }}</span>
                        @endif
                    </button>

                    <div
                        x-show="accountOpen"
                        x-cloak
                        x-transition.origin.top.right
                        role="menu"
                        class="absolute right-0 mt-3 w-64 overflow-hidden rounded-2xl border border-slate-200 bg-white py-2 text-sm font-semibold text-slate-700 shadow-xl shadow-slate-950/10 dark:border-slate-800 dark:bg-slate-900 dark:text-slate-200 dark:shadow-black/30"
                    >
                        <div class="border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                            <p class="truncate font-extrabold text-slate-950 dark:text-white">{{ $accountUser->publicName() }}</p>
                            <p class="mt-0.5 truncate text-xs font-medium text-slate-500 dark:text-slate-400">{{ $accountUser->email }}</p>
                        </div>

                        <a href="{{ route('profile.edit') }}" role="menuitem" class="flex min-h-11 items-center px-4 py-2 hover:bg-slate-100 focus:bg-slate-100 focus:outline-none dark:hover:bg-slate-800 dark:focus:bg-slate-800">
                            Profile
                        </a>

                        <button
                            type="button"
                            @click="toggleTheme()"
                            role="menuitem"
                            class="flex min-h-11 w-full items-center gap-3 px-4 py-2 text-left hover:bg-slate-100 focus:bg-slate-100 focus:outline-none dark:hover:bg-slate-800 dark:focus:bg-slate-800"
                            aria-label="Toggle light and dark mode"
                        >
                            <svg x-show="! dark" class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <path stroke-linecap="round" d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79Z" />
                            </svg>
                            <svg x-show="dark" x-cloak class="size-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                                <circle cx="12" cy="12" r="4" />
                                <path stroke-linecap="round" d="M12 2v2m0 16v2M4.93 4.93l1.42 1.42m11.3 11.3 1.42 1.42M2 12h2m16 0h2M4.93 19.07l1.42-1.42m11.3-11.3 1.42-1.42" />
                            </svg>
                            <span x-text="dark ? 'Use light theme' : 'Use dark theme'"></span>
                        </button>

                        <form method="POST" action="{{ route('logout') }}" class="border-t border-slate-100 pt-2 dark:border-slate-800">
                            @csrf
                            <button type="submit" role="menuitem" class="flex min-h-11 w-full items-center px-4 py-2 text-left text-slate-700 hover:bg-slate-100 focus:bg-slate-100 focus:outline-none dark:text-slate-200 dark:hover:bg-slate-800 dark:focus:bg-slate-800">
                                Log out
                            </button>
                        </form>
                    </div>
                </div>
            @else
                <a href="{{ route('login') }}" class="hidden rounded-full bg-indigo-600 px-4 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-indigo-500 md:inline-flex">
                    Sign in
                </a>
            @endauth

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
            <a href="{{ route('dashboard') }}" class="{{ $mobileNavLinkClass(request()->routeIs('dashboard')) }}">My Hub</a>
            <a href="{{ route('about') }}" class="{{ $mobileNavLinkClass(request()->routeIs('about')) }}">How it Works</a>
            <a href="{{ route('faq') }}" class="{{ $mobileNavLinkClass(request()->routeIs('faq')) }}">FAQ</a>

            @auth
                <div class="mt-2 border-t border-slate-200 pt-2 dark:border-slate-800">
                    <div class="flex items-center gap-3 px-3 py-2">
                        @if (filled($accountUser->avatar_url))
                            <img src="{{ $accountUser->avatar_url }}" alt="{{ $accountUser->publicName() }} avatar" class="size-9 rounded-full object-cover" referrerpolicy="no-referrer">
                        @else
                            <span class="inline-flex size-9 items-center justify-center rounded-full bg-indigo-50 text-sm font-extrabold text-indigo-700 dark:bg-indigo-500/10 dark:text-indigo-200">{{ $accountUser->initialsForAvatar() }}</span>
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-sm font-extrabold text-slate-950 dark:text-white">{{ $accountUser->publicName() }}</p>
                            <p class="truncate text-xs font-medium text-slate-500 dark:text-slate-400">{{ $accountUser->email }}</p>
                        </div>
                    </div>
                </div>
                <a href="{{ route('profile.edit') }}" class="flex min-h-11 items-center rounded-xl px-3 py-2 hover:bg-slate-100 dark:hover:bg-slate-900">Profile</a>
                <button
                    type="button"
                    @click="toggleTheme()"
                    class="flex min-h-11 items-center gap-3 rounded-xl px-3 py-2 text-left text-slate-600 hover:bg-slate-100 dark:text-slate-300 dark:hover:bg-slate-900"
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
                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="min-h-11 w-full rounded-xl px-3 py-2 text-left hover:bg-slate-100 dark:hover:bg-slate-900">Log out</button>
                </form>
            @else
                <a href="{{ route('login') }}" class="flex min-h-11 items-center rounded-xl bg-indigo-600 px-3 py-2 text-white">Sign in</a>
            @endauth
        </div>
    </div>
</nav>
