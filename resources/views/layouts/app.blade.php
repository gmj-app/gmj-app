@php
    $accountTheme = auth()->check() && in_array(auth()->user()->theme_preference, ['light', 'dark'], true) ? auth()->user()->theme_preference : null;
    $browserTheme = in_array(request()->cookie('theme'), ['light', 'dark'], true) ? request()->cookie('theme') : null;
    $serverTheme = $accountTheme ?? $browserTheme ?? 'dark';
@endphp
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="{{ $serverTheme === 'dark' ? 'dark' : '' }}" data-theme="{{ $serverTheme }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        @auth
            <meta name="theme-update-url" content="{{ route('profile.theme.update') }}">
        @endauth

        <title>{{ $title ?? config('app.name', 'Guide My Journey') }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        @include('layouts.theme-script', ['accountTheme' => $accountTheme])

        <!-- Fonts -->
        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        <!-- Scripts -->
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased dark:text-slate-100">
        <div class="min-h-screen bg-gray-100 dark:bg-slate-950">
            @include('layouts.public-navigation')

            <!-- Page Heading -->
            @isset($header)
                <header class="border-b border-gray-200 bg-white shadow-sm dark:border-slate-800 dark:bg-slate-900">
                    <div class="max-w-7xl mx-auto py-6 px-4 text-gray-900 dark:text-slate-50 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <!-- Page Content -->
            <main>
                {{ $slot }}
            </main>
        </div>

        @if (config('gmj.beta_feedback_enabled'))
            <x-beta-feedback />
        @endif

        <x-display-name-prompt-host />
    </body>
</html>
