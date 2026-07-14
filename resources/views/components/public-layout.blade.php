@props(['title' => config('app.name', 'Guide My Journey')])
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

        <title>{{ $title }}</title>
        <link rel="icon" href="{{ asset('favicon.svg') }}" type="image/svg+xml">

        @include('layouts.theme-script', ['accountTheme' => $accountTheme])

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="overflow-x-hidden bg-slate-50 font-sans text-slate-950 antialiased transition-colors dark:bg-slate-950 dark:text-slate-100">
        <div data-app-root class="min-h-screen min-w-0">
            @include('layouts.public-navigation')

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
