<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ isset($title) ? $title.' · Prawko' : config('app.name', 'Prawko') }}</title>

        @include('partials.theme-head')
        @include('partials.pwa-head')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans">
        <div class="min-h-screen">
            @include('partials.impersonation-bar')
            @include('layouts.navigation')

            {{-- Nagłówek strony (opcjonalny) --}}
            @isset($header)
                <header class="border-b border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                    <div class="mx-auto max-w-6xl px-4 py-5 sm:px-6 lg:px-8">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            {{-- Zwykły odstęp przed stopką. Miejsce pod przyklejoną mobilną nawigację
                 robi teraz stopka (pb-24) — gdyby robiły to oba, między treścią
                 a stopką ziałaby pusta przestrzeń na pół ekranu telefonu. --}}
            <main class="pb-10">
                {{-- Globalne komunikaty flash (jedyne miejsce — widoki ich nie powtarzają).
                     session('status') to klucz techniczny Breeze: pokazujemy tylko, gdy ma tłumaczenie. --}}
                @php
                    $flashOk = session('success')
                        ?? (session('status') && Lang::has('status.'.session('status')) ? __('status.'.session('status')) : null);
                @endphp
                @if ($flashOk || session('error'))
                    <div class="mx-auto max-w-6xl space-y-3 px-4 pt-4 sm:px-6 lg:px-8">
                        @if ($flashOk)
                            <div class="badge badge-emerald w-full justify-start rounded-xl px-4 py-3 text-sm">{{ $flashOk }}</div>
                        @endif
                        @if (session('error'))
                            <div class="badge badge-rose w-full justify-start rounded-xl px-4 py-3 text-sm">{{ session('error') }}</div>
                        @endif
                    </div>
                @endif

                {{ $slot }}
            </main>

            {{-- Stopka aplikacji. Poza <main>, ale wewnątrz kontenera — `main` ma już
                 dolny padding pod mobilną nawigację (`pb-24`), więc stopka nie wpada
                 pod przyklejone na dole zakładki. --}}
            <footer class="border-t border-slate-200 pb-24 dark:border-slate-800 sm:pb-0">
                <div class="mx-auto max-w-6xl px-4 py-6 text-center text-xs text-slate-400 dark:text-slate-600 sm:px-6 lg:px-8">
                    {{ __('app.footer_rights', ['year' => date('Y'), 'company' => config('company.name')]) }}
                    <span class="mx-1 opacity-60" aria-hidden="true">·</span>
                    <span class="tnum">v{{ config('app.version') }}</span>
                </div>
            </footer>
        </div>
    </body>
</html>
