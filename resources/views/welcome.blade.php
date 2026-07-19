<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        <title>{{ __('landing.meta.title') }}</title>
        <meta name="description" content="{{ __('landing.meta.description') }}">

        @include('partials.theme-head')
        @include('partials.pwa-head')

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans">
        @php
            $questionCount = \App\Models\Question::active()->count();
            $videoCount = \App\Models\Question::active()->where('media_type', 'video')->count();

            // Zasady egzaminu bierzemy z configu, a nie z pamięci — inaczej strona
            // zaczęłaby obiecywać co innego, niż robi symulacja.
            $threshold = (int) config('prawko.exam.pass_threshold');
            $timeLimit = (int) round(config('prawko.exam.time_limit_seconds') / 60);
            $composition = collect(config('prawko.exam.composition'));
            $maxScore = $composition->sum(fn ($l) => $l[1] * $l[2]);
            $sheetCount = $composition->sum(fn ($l) => $l[2]);
            $basicCount = $composition->filter(fn ($l) => $l[0]->value === 'basic')->sum(fn ($l) => $l[2]);
            $specialistCount = $sheetCount - $basicCount;
            $thresholdPercent = (int) round($threshold / $maxScore * 100);
        @endphp

        {{-- ===== Górny pasek ===== --}}
        {{-- `flex-wrap`: na wąskim telefonie logo + język + motyw + przycisk nie mieszczą
             się w jednym rzędzie (~410 px przy 360 px ekranu), więc nawigacja schodzi
             do drugiej linii zamiast rozpychać stronę w poziomie. --}}
        <header class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-3 px-4 py-5 sm:px-6 lg:px-8">
            <div class="flex items-center gap-2.5">
                <span class="grid h-9 w-9 place-items-center rounded-lg bg-brand-600 text-white shadow-brand">
                    <x-application-logo class="h-5 w-5" />
                </span>
                <span class="font-display text-lg font-bold tracking-tight text-slate-900 dark:text-white">Prawko</span>
            </div>
            {{-- Motyw i język: strona główna nie ma menu profilu, więc do tej pory
                 nie dało się tu zmienić ani jednego, ani drugiego. --}}
            <nav class="flex items-center gap-2 sm:gap-3">
                <x-locale-switcher />
                <x-theme-switcher :compact="true" />
                @auth
                    <a href="{{ route('dashboard') }}" class="btn btn-brand btn-sm sm:px-4 sm:py-2.5 sm:text-sm">{{ __('landing.nav.dashboard') }}</a>
                @else
                    <a href="{{ route('login') }}" class="btn btn-brand btn-sm sm:px-4 sm:py-2.5 sm:text-sm">{{ __('landing.nav.login') }}</a>
                @endauth
            </nav>
        </header>

        <main>
            {{-- ===== Hero ===== --}}
            <section class="mx-auto max-w-6xl px-4 pt-8 pb-14 sm:px-6 sm:pt-12 lg:px-8">
                <div class="grid items-center gap-10 lg:grid-cols-[1.05fr_0.95fr] lg:gap-16">
                    <div>
                        <span class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-600 dark:text-brand-400">
                            {{ __('landing.hero.eyebrow') }}
                        </span>
                        <h1 class="mt-4 font-display text-4xl font-extrabold leading-[1.05] tracking-tight text-slate-900 dark:text-white sm:text-5xl"
                            style="text-wrap: balance;">
                            {{ __('landing.hero.title') }}
                        </h1>
                        <p class="mt-5 max-w-lg text-base leading-relaxed text-slate-600 dark:text-slate-300 sm:text-lg">
                            {{ __('landing.hero.lead') }}
                        </p>

                        <div class="mt-8 flex flex-col gap-3 sm:flex-row sm:items-center">
                            @auth
                                <a href="{{ route('dashboard') }}" class="btn btn-brand">{{ __('landing.hero.cta_dashboard') }}</a>
                            @else
                                <a href="{{ route('login') }}" class="btn btn-brand">{{ __('landing.hero.cta_primary') }}</a>
                            @endauth
                            <a href="#silnik" class="btn btn-ghost">{{ __('landing.hero.cta_secondary') }}</a>
                        </div>

                        <p class="mt-6 text-sm text-slate-500 dark:text-slate-400 tnum">
                            {{ __('landing.hero.stats', [
                                'questions' => number_format($questionCount, 0, ',', ' '),
                                'videos' => number_format($videoCount, 0, ',', ' '),
                                'threshold' => $threshold,
                                'max' => $maxScore,
                            ]) }}
                        </p>
                    </div>

                    {{-- Próbka pytania (ilustracja produktu) --}}
                    <div class="relative">
                        <div class="card overflow-hidden">
                            <div class="flex items-center gap-2 border-b border-slate-100 px-4 py-3 dark:border-slate-800">
                                <span class="badge badge-brand">{{ __('landing.type.basic') }}</span>
                                <span class="badge badge-slate">{{ __('landing.rules.points_short', ['n' => 3]) }}</span>
                                <span class="ml-auto font-mono text-xs text-slate-400">{{ __('landing.hero.sample_badge') }}</span>
                            </div>
                            <div class="aspect-[16/9] w-full bg-gradient-to-br from-slate-100 to-slate-200 dark:from-slate-800 dark:to-slate-900">
                                <div class="flex h-full items-center justify-center text-slate-400 dark:text-slate-600">
                                    <svg class="h-10 w-10" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
                                </div>
                            </div>
                            <div class="p-4 sm:p-5">
                                <p class="text-sm font-semibold text-slate-900 dark:text-white">
                                    {{ __('landing.hero.sample_question') }}
                                </p>
                                <div class="mt-3 grid grid-cols-2 gap-2">
                                    <div class="flex items-center justify-between rounded-lg border border-emerald-300 bg-emerald-50 px-3 py-2 text-sm font-semibold text-emerald-800 dark:border-emerald-500/40 dark:bg-emerald-500/10 dark:text-emerald-200">
                                        {{ __('landing.hero.yes') }}
                                        <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    </div>
                                    <div class="rounded-lg border border-slate-200 px-3 py-2 text-sm text-slate-500 dark:border-slate-800 dark:text-slate-400">{{ __('landing.hero.no') }}</div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===== Silnik: szansa zdania ===== --}}
            <section id="silnik" class="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
                    <div class="grid gap-10 lg:grid-cols-[0.95fr_1.05fr] lg:gap-16">
                        <div>
                            <span class="text-xs font-semibold uppercase tracking-[0.14em] text-brand-600 dark:text-brand-400">{{ __('landing.engine.eyebrow') }}</span>
                            <h2 class="mt-3 font-display text-3xl font-bold tracking-tight text-slate-900 dark:text-white" style="text-wrap: balance;">
                                {{ __('landing.engine.title') }}
                            </h2>
                            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                                {{ __('landing.engine.p1', ['count' => $sheetCount, 'threshold' => $threshold, 'max' => $maxScore, 'percent' => $thresholdPercent]) }}
                            </p>
                            <p class="mt-4 text-base leading-relaxed text-slate-600 dark:text-slate-300">
                                {!! __('landing.engine.p2', ['simulations' => '<strong class="text-slate-800 dark:text-slate-100">'.number_format(\App\Services\ExamForecastService::SIMULATIONS, 0, ',', ' ').'</strong>']) !!}
                            </p>

                            <ul class="mt-6 space-y-3 text-sm text-slate-600 dark:text-slate-300">
                                @foreach ([
                                    [__('landing.engine.memory_title'), __('landing.engine.memory_desc')],
                                    [__('landing.engine.difficulty_title'), __('landing.engine.difficulty_desc')],
                                    [__('landing.engine.time_title'), __('landing.engine.time_desc')],
                                ] as [$title, $desc])
                                    <li class="flex gap-3">
                                        <span class="mt-1.5 h-1.5 w-1.5 shrink-0 rounded-full bg-brand-600 dark:bg-brand-400"></span>
                                        <span><strong class="text-slate-800 dark:text-slate-100">{{ $title }}.</strong> {{ $desc }}</span>
                                    </li>
                                @endforeach
                            </ul>
                        </div>

                        {{-- Ilustracja: karta prognozy w tej samej formie co na pulpicie --}}
                        <div>
                            <div class="card p-6">
                                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('landing.engine.card_chance') }}</div>
                                <div class="mt-1 font-display text-6xl font-bold leading-none text-brand-600 dark:text-brand-400">
                                    74<span class="text-3xl">%</span>
                                </div>
                                <span class="badge badge-brand mt-3">{{ __('landing.engine.card_readiness') }}</span>

                                <div class="mt-8">
                                    <div class="flex items-baseline justify-between gap-3">
                                        <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('landing.engine.card_forecast') }}</span>
                                        <span class="font-display text-sm font-bold tnum text-slate-900 dark:text-white">
                                            69<span class="text-slate-400">/{{ __('landing.rules.points_short', ['n' => $maxScore]) }}</span>
                                        </span>
                                    </div>
                                    {{-- Pasek: 90% przedział, mediana i próg zdania — jak w aplikacji. --}}
                                    <div class="relative mt-3 h-3 rounded-full bg-slate-100 dark:bg-slate-800">
                                        <div class="absolute inset-y-0 rounded-full bg-brand-500/20" style="left: 78%; width: 17%"></div>
                                        <div class="absolute inset-y-0 w-0.5 bg-brand-500" style="left: calc(93% - 1px)"></div>
                                        <div class="absolute -inset-y-1 w-px border-l border-dashed border-slate-400 dark:border-slate-500"
                                             style="left: {{ round($threshold / $maxScore * 100, 2) }}%"></div>
                                    </div>
                                    <div class="mt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 tnum">
                                        <span>{{ __('landing.engine.card_range') }}</span>
                                        <span>{{ __('landing.engine.card_threshold', ['threshold' => $threshold]) }}</span>
                                    </div>
                                </div>

                                <p class="mt-6 border-t border-slate-100 pt-4 text-xs text-slate-400 dark:border-slate-800 dark:text-slate-500">
                                    {{ __('landing.engine.card_note') }}
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===== Jak to działa ===== --}}
            <section id="jak-to-dziala" class="border-t border-slate-200 dark:border-slate-800">
                <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
                    <h2 class="font-display text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('landing.how.title') }}</h2>
                    <p class="mt-2 max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                        {{ __('landing.how.lead') }}
                    </p>

                    <div class="mt-8 grid gap-px overflow-hidden rounded-xl border border-slate-200 bg-slate-200 dark:border-slate-800 dark:bg-slate-800 sm:grid-cols-2 lg:grid-cols-3">
                        @php
                            $features = [
                                [__('landing.how.diagnosis_title'), __('landing.how.diagnosis_desc'),
                                 'M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z'],
                                [__('landing.how.base_title'), __('landing.how.base_desc'),
                                 'M12 6.75A2.25 2.25 0 1 1 12 2.25a2.25 2.25 0 0 1 0 4.5ZM4.5 21v-1.5a4.5 4.5 0 0 1 4.5-4.5M9 12.75h6M9 15.75h4.5'],
                                [__('landing.how.exam_title'), __('landing.how.exam_desc', ['count' => $sheetCount, 'threshold' => $threshold]),
                                 'M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z'],
                                [__('landing.how.reviews_title'), __('landing.how.reviews_desc'),
                                 'M3 12a9 9 0 1 0 3-6.7L3 8m0-4.5V8h4.5'],
                                [__('landing.how.losses_title'), __('landing.how.losses_desc'),
                                 'M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z'],
                                [__('landing.how.plan_title'), __('landing.how.plan_desc'),
                                 'M6.75 3v2.25M17.25 3v2.25M3 18.75V7.5a2.25 2.25 0 0 1 2.25-2.25h13.5A2.25 2.25 0 0 1 21 7.5v11.25m-18 0A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75m-18 0v-7.5A2.25 2.25 0 0 1 5.25 9h13.5A2.25 2.25 0 0 1 21 11.25v7.5'],
                            ];
                        @endphp
                        @foreach ($features as [$title, $desc, $icon])
                            <div class="bg-white p-6 dark:bg-slate-900">
                                <span class="text-brand-600 dark:text-brand-400">
                                    <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                                        <path d="{{ $icon }}" />
                                    </svg>
                                </span>
                                <h3 class="mt-4 text-base font-semibold text-slate-900 dark:text-white">{{ $title }}</h3>
                                <p class="mt-2 text-sm leading-relaxed text-slate-600 dark:text-slate-400">{{ $desc }}</p>
                            </div>
                        @endforeach
                    </div>
                </div>
            </section>

            {{-- ===== Zasady egzaminu ===== --}}
            <section class="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
                <div class="mx-auto max-w-6xl px-4 py-14 sm:px-6 lg:px-8">
                    <h2 class="font-display text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('landing.rules.title') }}</h2>
                    <p class="mt-2 max-w-2xl text-sm text-slate-500 dark:text-slate-400">
                        {{ __('landing.rules.lead') }}
                    </p>

                    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        @foreach ([
                            [$sheetCount, __('landing.rules.questions_label'), __('landing.rules.questions_note', ['basic' => $basicCount, 'specialist' => $specialistCount])],
                            [$maxScore, __('landing.rules.points_label'), __('landing.rules.points_note')],
                            [$threshold, __('landing.rules.threshold_label'), __('landing.rules.threshold_note', ['percent' => $thresholdPercent])],
                            [$timeLimit, __('landing.rules.time_label'), __('landing.rules.time_note')],
                        ] as [$value, $label, $note])
                            <div class="card p-5">
                                <div class="font-display text-4xl font-bold leading-none tnum text-slate-900 dark:text-white">{{ $value }}</div>
                                <div class="mt-1.5 text-sm font-medium text-slate-700 dark:text-slate-200">{{ $label }}</div>
                                <p class="mt-1 text-xs leading-relaxed text-slate-500 dark:text-slate-400">{{ $note }}</p>
                            </div>
                        @endforeach
                    </div>

                    {{-- Skład arkusza prosto z konfiguracji symulacji. --}}
                    <div class="card mt-4 overflow-hidden">
                        <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                            <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('landing.rules.sheet_title') }}</h3>
                            <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                                {{ __('landing.rules.sheet_lead') }}
                            </p>
                        </div>
                        {{-- Tabela przewija się we własnym kontenerze, żeby na telefonie
                             nie rozjeżdżała całej strony w poziomie. --}}
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-sm">
                                <thead class="border-b border-slate-100 text-xs uppercase tracking-wide text-slate-500 dark:border-slate-800 dark:text-slate-400">
                                    <tr>
                                        <th class="px-5 py-2.5 font-semibold">{{ __('landing.rules.col_kind') }}</th>
                                        <th class="px-5 py-2.5 text-right font-semibold">{{ __('landing.rules.col_weight') }}</th>
                                        <th class="px-5 py-2.5 text-right font-semibold">{{ __('landing.rules.col_count') }}</th>
                                        <th class="px-5 py-2.5 text-right font-semibold">{{ __('landing.rules.col_points') }}</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                                    @foreach ($composition as [$type, $points, $count])
                                        <tr>
                                            <td class="px-5 py-2.5">
                                                <span class="badge {{ $type->value === 'basic' ? 'badge-brand' : 'badge-amber' }}">{{ __('landing.type.'.$type->value) }}</span>
                                            </td>
                                            <td class="px-5 py-2.5 text-right tnum text-slate-600 dark:text-slate-300">{{ __('landing.rules.points_short', ['n' => $points]) }}</td>
                                            <td class="px-5 py-2.5 text-right tnum text-slate-600 dark:text-slate-300">{{ $count }}</td>
                                            <td class="px-5 py-2.5 text-right tnum font-semibold text-slate-800 dark:text-slate-100">{{ $points * $count }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="border-t border-slate-200 dark:border-slate-700">
                                    <tr class="font-semibold text-slate-900 dark:text-white">
                                        <td class="px-5 py-2.5">{{ __('landing.rules.total') }}</td>
                                        <td class="px-5 py-2.5"></td>
                                        <td class="px-5 py-2.5 text-right tnum">{{ $sheetCount }}</td>
                                        <td class="px-5 py-2.5 text-right tnum">{{ $maxScore }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </section>

            {{-- ===== Wejście ===== --}}
            @guest
                <section class="border-t border-slate-200 dark:border-slate-800">
                    <div class="mx-auto max-w-6xl px-4 py-14 text-center sm:px-6 lg:px-8">
                        <h2 class="font-display text-2xl font-bold tracking-tight text-slate-900 dark:text-white" style="text-wrap: balance;">
                            {{ __('landing.cta.title') }}
                        </h2>
                        {{-- Rejestracja jest wyłączona (konta zakłada admin) — strona nie może
                             obiecywać „załóż konto", bo nie ma gdzie tego zrobić. --}}
                        <p class="mx-auto mt-3 max-w-xl text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                            {!! __('landing.cta.lead', [
                                'email' => '<a href="mailto:'.config('company.email').'" class="font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400">'.config('company.email').'</a>',
                            ]) !!}
                        </p>
                        <a href="{{ route('login') }}" class="btn btn-brand mt-6">{{ __('landing.cta.button') }}</a>
                    </div>
                </section>
            @endguest
        </main>

        @include('partials.company-footer')
    </body>
</html>
