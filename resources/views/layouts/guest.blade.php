<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Guide My Journey') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        <script>
            (() => {
                const storedTheme = localStorage.getItem('theme');
                const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                if (storedTheme === 'dark' || (!storedTheme && prefersDark)) {
                    document.documentElement.classList.add('dark');
                }
            })();
        </script>

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased dark:text-slate-100">
        <div class="min-h-screen flex flex-col sm:justify-center items-center pt-6 sm:pt-0 bg-gray-100 dark:bg-slate-950">
            <div class="px-4 text-center">
                <a href="{{ route('home') }}" class="inline-flex" aria-label="Guide My Journey home">
                    <x-application-logo size="lg" />
                </a>
            </div>

            <div class="mt-6 w-full overflow-hidden border-y border-gray-200 bg-white px-6 py-4 shadow-md dark:border-slate-800 dark:bg-slate-900 sm:max-w-md sm:rounded-lg sm:border">
                {{ $slot }}
            </div>
        </div>
    </body>
</html>
