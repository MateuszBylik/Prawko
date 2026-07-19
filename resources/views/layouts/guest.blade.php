<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Prawko') }}</title>

        @include('partials.theme-head')
        @include('partials.pwa-head')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans">
        <div class="relative min-h-screen flex flex-col justify-center items-center px-4 py-10
                    bg-gradient-to-b from-brand-50 via-slate-50 to-slate-100
                    dark:from-slate-950 dark:via-slate-950 dark:to-slate-900">

            {{-- Delikatna poświata marki w tle --}}
            <div class="pointer-events-none absolute inset-x-0 top-0 h-64 bg-brand-500/10 blur-3xl"></div>

            <a href="/" class="relative flex items-center gap-3 mb-8">
                <span class="grid place-items-center h-12 w-12 rounded-2xl bg-brand-600 text-white shadow-brand">
                    <x-application-logo class="h-7 w-7" />
                </span>
                <span class="text-2xl font-bold tracking-tight text-slate-900 dark:text-white">
                    Prawko
                </span>
            </a>

            <div class="relative w-full sm:max-w-md">
                <div class="card p-6 sm:p-8">
                    {{ $slot }}
                </div>
            </div>

            <p class="relative mt-8 text-xs text-slate-400 dark:text-slate-600">
                Nauka teorii na prawo jazdy
            </p>
        </div>
    </body>
</html>
