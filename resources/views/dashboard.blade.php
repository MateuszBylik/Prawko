<x-app-layout>
    <x-slot name="title">{{ __('dashboard.title') }}</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-4">
            <div>
                <h1 class="font-display text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                    {{ __('dashboard.greeting', ['name' => Str::of(Auth::user()->name)->before(' ') ?: Auth::user()->name]) }}
                </h1>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ __('dashboard.subtitle') }}</p>
            </div>
        </div>
    </x-slot>

    {{-- Dzień egzaminu: „jak poszło?" — nakładka nad całym pulpitem. --}}
    <x-exam-day-prompt />

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

        {{-- Onboarding: Diagnoza (dopóki użytkownik jej nie ukończył) --}}
        @if ($needsDiagnosis && $examCategory)
            <div class="card overflow-hidden border-brand-200 bg-brand-50/40 p-6 dark:border-brand-500/30 dark:bg-brand-500/5 sm:p-8">
                <span class="badge badge-brand">{{ __('dashboard.diagnosis.badge') }}</span>
                <h2 class="mt-3 font-display text-2xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('dashboard.diagnosis.title') }}</h2>
                <p class="mt-2 max-w-xl text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {!! __('dashboard.diagnosis.lead') !!}
                </p>
                <ul class="mt-4 flex flex-col gap-2 text-sm text-slate-600 dark:text-slate-300 sm:flex-row sm:gap-6">
                    <li class="flex items-center gap-2"><span class="text-brand-600 dark:text-brand-400">●</span> {{ __('dashboard.diagnosis.point_level') }}</li>
                    <li class="flex items-center gap-2"><span class="text-brand-600 dark:text-brand-400">●</span> {{ __('dashboard.diagnosis.point_chance') }}</li>
                    <li class="flex items-center gap-2"><span class="text-brand-600 dark:text-brand-400">●</span> {{ __('dashboard.diagnosis.point_plan') }}</li>
                </ul>
                <form method="POST" action="{{ route('sessions.store') }}" class="mt-5">
                    @csrf
                    <input type="hidden" name="mode" value="diagnosis">
                    <button type="submit" class="btn btn-brand">{{ __('dashboard.diagnosis.start') }}</button>
                </form>
            </div>
        @endif

        {{-- Szansa zdania (silnik Monte Carlo) --}}
        @if ($forecast && ! $needsDiagnosis)
            @php
                $r = $forecast->readiness();
                $toneText = [
                    'emerald' => 'text-emerald-600 dark:text-emerald-400',
                    'brand' => 'text-brand-600 dark:text-brand-400',
                    'amber' => 'text-amber-600 dark:text-amber-400',
                    'rose' => 'text-rose-500 dark:text-rose-400',
                ][$r['tone']];
            @endphp
            <div class="card p-6">
                <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:gap-8">
                    {{-- Jedna liczba --}}
                    <div class="shrink-0">
                        <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('dashboard.forecast.chance') }}</div>
                        @if ($forecast->hasSignal())
                            <div class="mt-1 font-display text-6xl font-bold leading-none tnum {{ $toneText }}">
                                {{ $forecast->passPercent() }}<span class="text-3xl">%</span>
                            </div>
                            <span class="badge badge-{{ $r['tone'] }} mt-3">{{ $r['label'] }}</span>
                        @else
                            <div class="mt-1 font-display text-6xl font-bold leading-none tnum text-slate-300 dark:text-slate-600">—</div>
                            <span class="badge badge-slate mt-3">{{ __('dashboard.forecast.no_data') }}</span>
                        @endif
                    </div>

                    {{-- Prognoza wyniku --}}
                    <div class="min-w-0 flex-1">
                        @if ($forecast->hasSignal())
                            @php
                                $pMed = $forecast->pctOf($forecast->medianScore);
                                $pLow = $forecast->pctOf($forecast->ciLow);
                                $pHigh = $forecast->pctOf($forecast->ciHigh);
                                $pThr = $forecast->pctOf($forecast->threshold);
                                $bandTone = $forecast->medianScore >= $forecast->threshold ? 'bg-emerald-500/25' : 'bg-brand-500/20';
                                $medTone = $forecast->medianScore >= $forecast->threshold ? 'bg-emerald-500' : 'bg-brand-500';
                            @endphp
                            <div class="flex items-baseline justify-between gap-3">
                                <span class="text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('dashboard.forecast.predicted') }}</span>
                                <span class="font-display text-sm font-bold tnum text-slate-900 dark:text-white">
                                    {{ $forecast->medianScore }}<span class="text-slate-400">/{{ __('dashboard.forecast.points_short', ['n' => $forecast->maxScore]) }}</span>
                                </span>
                            </div>
                            {{-- Pasek: 90% przedział (p5–p95), mediana i próg zdania (68) --}}
                            <div class="relative mt-3 h-3 rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="absolute inset-y-0 rounded-full {{ $bandTone }}"
                                     style="left: {{ $pLow }}%; width: {{ max(1.5, $pHigh - $pLow) }}%"></div>
                                <div class="absolute inset-y-0 w-0.5 {{ $medTone }}" style="left: calc({{ $pMed }}% - 1px)"></div>
                                <div class="absolute -inset-y-1 w-px border-l border-dashed border-slate-400 dark:border-slate-500"
                                     style="left: {{ $pThr }}%"></div>
                            </div>
                            <div class="mt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 tnum">
                                <span>{{ __('dashboard.forecast.range', ['low' => $forecast->ciLow, 'high' => $forecast->ciHigh]) }}</span>
                                <span>{{ __('dashboard.forecast.threshold', ['n' => $forecast->threshold]) }}</span>
                            </div>
                            <div class="mt-3 flex flex-wrap items-center justify-between gap-2">
                                <p class="text-xs text-slate-400 dark:text-slate-500">
                                    {{ __('dashboard.forecast.meta', [
                                        'samples' => number_format($forecast->samples, 0, ',', ' '),
                                        'coverage' => $forecast->coveragePercent(),
                                    ]) }}
                                </p>
                                {{-- Wejście w rozbicie tej liczby na czynniki — ekran strat
                                     jest rozwinięciem prognozy, więc mieszka przy niej. --}}
                                <a href="{{ route('losses.index') }}"
                                   class="inline-flex items-center gap-1 text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
                                    {{ __('dashboard.forecast.losses_link') }}
                                    <svg class="h-3.5 w-3.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                                </a>
                            </div>
                        @else
                            <p class="text-sm text-slate-600 dark:text-slate-300">
                                {!! __('dashboard.forecast.not_enough', ['answered' => $forecast->answered]) !!}
                            </p>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        {{-- Wznowienie sesji — tuż pod szansą zdania, bo to rzecz do zrobienia TERAZ.
             Poza blokiem `@if ($forecast...)`, żeby pokazało się także w trakcie
             onboardingu (przerwana diagnoza to najczęstszy przypadek). --}}
        @if ($inProgress)
            <div class="card flex flex-wrap items-center justify-between gap-3 border-amber-200 bg-amber-50 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
                <p class="text-sm font-medium text-amber-800 dark:text-amber-200">
                    {{ $resumeLocked ? __('dashboard.resume.exam_over') : __('dashboard.resume.text', ['mode' => $inProgress->mode->label()]) }}
                </p>
                <a href="{{ route('sessions.show', $inProgress) }}" class="btn btn-brand btn-sm">{{ $resumeLocked ? __('dashboard.resume.see_result') : __('dashboard.resume.button') }}</a>
            </div>
        @endif

        {{-- Plan pod datę egzaminu --}}
        @if ($plan && ! $needsDiagnosis)
            @php $sm = $plan->statusMeta(); @endphp
            <div class="card p-6">
                <div class="flex flex-wrap items-start justify-between gap-3">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('dashboard.plan.title') }}</h2>
                        <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ $sm['note'] }}</p>
                    </div>
                    @if ($plan->hasDate() && $plan->status !== 'past')
                        <span class="badge badge-{{ $sm['tone'] }}">{{ $sm['label'] }}</span>
                    @endif
                </div>

                @if (! $plan->hasDate() || $plan->status === 'past')
                    <form method="POST" action="{{ route('plan.update') }}" class="mt-4 flex flex-col gap-2 sm:flex-row sm:items-end">
                        @csrf
                        <div class="flex-1">
                            <label for="exam_date" class="label">{{ __('dashboard.plan.exam_date') }}</label>
                            <input type="date" id="exam_date" name="exam_date" required class="field mt-1"
                                   min="{{ now()->addDay()->format('Y-m-d') }}"
                                   value="{{ old('exam_date') }}">
                            @error('exam_date')
                                <p class="mt-1 text-xs text-rose-500">{{ $message }}</p>
                            @enderror
                        </div>
                        <button type="submit" class="btn btn-brand">{{ __('dashboard.plan.set_date') }}</button>
                    </form>
                @else
                    {{-- Kluczowe liczby --}}
                    <div class="mt-5 grid grid-cols-3 gap-4">
                        <div>
                            <div class="font-display text-3xl font-bold tnum text-slate-900 dark:text-white">
                                {{ $plan->daysLeft }}<span class="ml-1 text-base font-medium text-slate-400">{{ __('dashboard.plan.days') }}</span>
                            </div>
                            <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.plan.until', ['date' => $plan->examDate->format('d.m.Y')]) }}</div>
                        </div>
                        <div>
                            <div class="font-display text-3xl font-bold tnum text-brand-600 dark:text-brand-400">{{ $plan->passPercent() }}%</div>
                            <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.plan.chance', ['target' => $plan->targetPercent()]) }}</div>
                        </div>
                        <div>
                            <div class="font-display text-3xl font-bold tnum text-slate-900 dark:text-white">{{ $plan->coveragePercent() }}%</div>
                            <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 tnum">{{ __('dashboard.plan.coverage', ['seen' => $plan->seen, 'total' => $plan->total]) }}</div>
                        </div>
                    </div>

                    {{-- Dzisiejsza dawka: liczba mówi ILE ZOSTAŁO, pasek pokazuje zrobione.
                         Cel jest ustalony na początek dnia, więc każde pytanie zdejmuje z niego 1. --}}
                    <div class="mt-5 rounded-xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-800 dark:bg-slate-800/40">
                        {{-- Przełącznik trybu ekspresowego — tylko w ostatnim tygodniu przed
                             egzaminem. ON = główny przycisk i liczby kafelka stają się ekspresowe,
                             a „Nowe pytania" znikają (są podzbiorem ekspresu). --}}
                        @if ($expressAvailable)
                            <div class="mb-4 flex items-center justify-between gap-3 border-b border-slate-200 pb-3 dark:border-slate-700">
                                <div class="flex items-start gap-2.5">
                                    <svg class="mt-0.5 h-5 w-5 shrink-0 {{ $expressOn ? 'text-brand-600 dark:text-brand-400' : 'text-slate-400 dark:text-slate-500' }}" fill="currentColor" viewBox="0 0 24 24"><path d="M13 2 3 14h7l-1 8 10-12h-7l1-8Z"/></svg>
                                    <div>
                                        <p class="text-sm font-semibold text-slate-800 dark:text-slate-100">{{ __('dashboard.express.title') }}</p>
                                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.express.lead') }}</p>
                                    </div>
                                </div>
                                <form method="POST" action="{{ route('plan.express') }}" class="shrink-0">
                                    @csrf
                                    <input type="hidden" name="express_mode" value="{{ $expressOn ? 0 : 1 }}">
                                    <button type="submit" role="switch" aria-checked="{{ $expressOn ? 'true' : 'false' }}"
                                            aria-label="{{ __('dashboard.express.title') }}"
                                            class="relative inline-flex h-6 w-11 shrink-0 items-center rounded-full transition {{ $expressOn ? 'bg-brand-600' : 'bg-slate-300 dark:bg-slate-600' }}">
                                        <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition {{ $expressOn ? 'translate-x-6' : 'translate-x-1' }}"></span>
                                    </button>
                                </form>
                            </div>
                        @endif

                        @if ($expressOn)
                            {{-- Tryb ekspresowy włączony: dawka = pytania jeszcze niespełnione. --}}
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="text-sm">
                                    <div class="font-semibold text-slate-800 dark:text-slate-100 tnum">
                                        {{ $express['unsatisfied'] > 0
                                            ? __('dashboard.express.remaining', ['n' => $express['unsatisfied'], 'perday' => $express['per_day']])
                                            : __('dashboard.express.done') }}
                                    </div>
                                    <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 tnum">
                                        {{ __('dashboard.express.progress', ['satisfied' => $express['satisfied'], 'total' => $express['total']]) }}
                                    </div>
                                </div>
                                @if ($examCategory && $express['unsatisfied'] > 0)
                                    <form method="POST" action="{{ route('sessions.store') }}" class="shrink-0">
                                        @csrf
                                        <input type="hidden" name="mode" value="practice">
                                        <input type="hidden" name="scope" value="express">
                                        <input type="hidden" name="size" value="{{ $express['size'] }}">
                                        <button type="submit" class="btn btn-brand btn-sm w-full sm:w-auto">
                                            {{ __('dashboard.express.start', ['n' => $express['size']]) }}
                                        </button>
                                    </form>
                                @endif
                            </div>

                            <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div class="h-full rounded-full bg-brand-600 transition-all dark:bg-brand-500"
                                     style="width: {{ $express['total'] ? round($express['satisfied'] / $express['total'] * 100) : 100 }}%"></div>
                            </div>
                        @else
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <div class="text-sm">
                                    <div class="font-semibold text-slate-800 dark:text-slate-100 tnum">
                                        {{ $plan->doseDone()
                                            ? __('dashboard.plan.dose_done')
                                            : __('dashboard.plan.dose_left', ['n' => $plan->dailyDose]) }}
                                    </div>
                                    <div class="mt-0.5 text-xs text-slate-500 dark:text-slate-400 tnum">
                                        {{ __('dashboard.plan.dose_detail', ['done' => $plan->newDoneToday, 'target' => $plan->newPerDay]) }}
                                        @if ($plan->dueToday > 0)
                                            · {{ __('dashboard.plan.dose_reviews', ['n' => $plan->dueToday]) }}
                                        @endif
                                        · {{ __('dashboard.plan.pace', ['pace' => $plan->recentPace]) }}
                                        <span class="text-slate-400 dark:text-slate-500">{{ __('dashboard.plan.pace_days', ['n' => $plan->paceDays]) }}</span>
                                    </div>
                                </div>
                                @if ($examCategory)
                                    <div class="flex shrink-0 flex-col gap-2 sm:flex-row sm:items-center">
                                        <form method="POST" action="{{ route('sessions.store') }}">
                                            @csrf
                                            <input type="hidden" name="mode" value="practice">
                                            <input type="hidden" name="size" value="{{ $plan->sessionSize }}">
                                            <button type="submit" class="btn {{ $plan->doseDone() ? 'btn-outline' : 'btn-brand' }} btn-sm w-full sm:w-auto">
                                                {{ $plan->doseDone() ? __('dashboard.plan.keep_going') : __('dashboard.plan.start_dose') }} ({{ $plan->sessionSize }})
                                            </button>
                                        </form>
                                        {{-- Nauka tylko nowych — domyślna nauka dobiera wg ryzyka i powtórek,
                                             więc świadome domykanie pokrycia dostaje własny przycisk. --}}
                                        @if (! empty($newCount))
                                            <form method="POST" action="{{ route('sessions.store') }}">
                                                @csrf
                                                <input type="hidden" name="mode" value="practice">
                                                <input type="hidden" name="scope" value="new">
                                                <input type="hidden" name="size" value="{{ $examSize }}">
                                                <button type="submit" class="btn btn-outline btn-sm w-full sm:w-auto">
                                                    {{ __('dashboard.start.only_new', ['n' => min($newCount, $examSize)]) }}
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                @endif
                            </div>

                            <div class="mt-3 h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                                <div class="h-full rounded-full transition-all {{ $plan->doseDone() ? 'bg-emerald-500' : 'bg-brand-600 dark:bg-brand-500' }}"
                                     style="width: {{ $plan->newProgressPercent() }}%"></div>
                            </div>

                            {{-- Karta pokazuje obok „cel 95%", ale dawka liczy się z CZEGO INNEGO:
                                 z przerobienia całej puli do dnia egzaminu. Dwa różne cele obok
                                 siebie bez wyjaśnienia to zagadka — mówimy wprost, skąd liczba. --}}
                            <p class="mt-2.5 text-xs leading-relaxed text-slate-500 dark:text-slate-400">
                                {!! __('dashboard.plan.goal_note', [
                                    'unseen' => $plan->unseen,
                                    'total' => $plan->total,
                                    'days' => trans_choice('dashboard.plan.days_choice', $plan->daysLeft, ['n' => $plan->daysLeft]),
                                    'perday' => $plan->newPerDay,
                                    'target' => $plan->targetPercent(),
                                ]) !!}
                            </p>
                        @endif
                    </div>

                    <details class="group mt-3">
                        <summary class="inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                            <svg class="h-3.5 w-3.5 transition group-open:rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            {{ __('dashboard.plan.change_date') }}
                        </summary>
                        <div class="mt-2 flex flex-wrap items-end gap-2">
                            <form method="POST" action="{{ route('plan.update') }}" class="flex items-end gap-2">
                                @csrf
                                <input type="date" name="exam_date" class="field" min="{{ now()->addDay()->format('Y-m-d') }}"
                                       value="{{ $plan->examDate->format('Y-m-d') }}">
                                <button type="submit" class="btn btn-outline btn-sm">{{ __('dashboard.plan.save') }}</button>
                            </form>
                            <form method="POST" action="{{ route('plan.update') }}">
                                @csrf
                                {{-- brak pola exam_date → null → usuwa datę --}}
                                <button type="submit" class="btn btn-ghost btn-sm text-slate-400">{{ __('dashboard.plan.remove') }}</button>
                            </form>
                        </div>
                    </details>
                @endif
            </div>
        @endif

        {{-- Historia szansy zdania --}}
        @if ($forecastHistory->isNotEmpty() && ! $needsDiagnosis)
            <x-forecast-chart :history="$forecastHistory" :target="config('prawko.plan.target_pass')" />
        @endif

        {{-- Szybkie akcje --}}
        <div class="grid gap-4 sm:grid-cols-2">
            {{-- Start ćwiczenia / egzaminu --}}
            <div class="card p-6">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('dashboard.start.title') }}</h2>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                    {!! __('dashboard.start.lead', ['count' => collect(config('prawko.exam.composition'))->sum(fn ($l) => $l[2])]) !!}
                </p>
                <form method="POST" action="{{ route('sessions.store') }}" class="mt-4 space-y-3">
                    @csrf
                    {{-- Kategoria nie jest już wyborem przy każdej sesji — to cecha konta.
                         Pokazujemy ją, żeby było wiadomo, czego dotyczy nauka, i kierujemy
                         do profilu, gdzie się ją zmienia. --}}
                    @if ($examCategory)
                        <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-slate-200 px-3 py-2 dark:border-slate-800">
                            <span class="text-sm text-slate-600 dark:text-slate-300">
                                {!! __('dashboard.start.category', ['code' => '<strong class="text-slate-900 dark:text-white">'.e($examCategory->code).'</strong>']) !!}
                                <span class="text-slate-400 dark:text-slate-500">— {{ $examCategory->localizedName() }}</span>
                            </span>
                            <a href="{{ route('profile.edit') }}"
                               class="text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
                                {{ __('dashboard.start.change') }}
                            </a>
                        </div>
                    @endif
                    <button type="submit" name="mode" value="exam" class="btn btn-dark w-full">{{ __('dashboard.start.exam') }}</button>
                </form>
            </div>

            {{-- Baza pytań --}}
            <a href="{{ route('questions.index') }}"
               class="card group flex flex-col justify-between overflow-hidden p-6 transition hover:border-brand-300 hover:shadow-md dark:hover:border-brand-500/40">
                <div>
                    <span class="grid h-11 w-11 place-items-center rounded-xl bg-brand-50 text-brand-600 dark:bg-brand-500/10 dark:text-brand-400">
                        <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                            <path d="M12 6.75A2.25 2.25 0 1 1 12 2.25a2.25 2.25 0 0 1 0 4.5ZM4.5 21v-1.5a4.5 4.5 0 0 1 4.5-4.5M9 12.75h6M9 15.75h4.5" />
                        </svg>
                    </span>
                    <h2 class="mt-4 text-base font-semibold text-slate-900 dark:text-white">{{ __('dashboard.browse.title') }}</h2>
                    <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {{ __('dashboard.browse.lead') }}
                    </p>
                </div>
                <span class="mt-4 inline-flex items-center gap-1 text-sm font-semibold text-brand-600 dark:text-brand-400">
                    {{ __('dashboard.browse.open') }}
                    <svg class="h-4 w-4 transition group-hover:translate-x-0.5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                </span>
            </a>
        </div>

        {{-- Co ćwiczyć najpierw (kolejka ryzyka) --}}
        @if ($riskQuestions->isNotEmpty() && ! $needsDiagnosis)
            <div class="card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('dashboard.risk.title') }}</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('dashboard.risk.lead') }}</p>
                    </div>
                    @if ($examCategory)
                        <form method="POST" action="{{ route('sessions.store') }}" class="shrink-0">
                            @csrf
                            <input type="hidden" name="mode" value="practice">
                            <button type="submit" class="btn btn-brand btn-sm">{{ __('dashboard.risk.practice_weakest') }}</button>
                        </form>
                    @endif
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($riskQuestions as $q)
                        @php
                            $pp = (int) round($q->p_correct * 100);
                            $pTone = $pp < 50 ? 'text-rose-500 dark:text-rose-400' : ($pp < 75 ? 'text-amber-600 dark:text-amber-400' : 'text-emerald-600 dark:text-emerald-400');
                        @endphp
                        <a href="{{ route('questions.show', $q) }}"
                           class="flex items-center gap-4 px-5 py-3.5 transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="badge badge-slate">{{ $q->type->label() }}</span>
                                    <span class="badge badge-brand">{{ __('session.review.points', ['n' => $q->points]) }}</span>
                                </div>
                                <p class="mt-1 truncate text-sm text-slate-700 dark:text-slate-200">{{ Str::limit($q->localizedText(), 90) }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="font-display text-sm font-bold tnum {{ $pTone }}">{{ $pp }}%</div>
                                <div class="text-[11px] text-slate-400">{{ __('dashboard.risk.chance') }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Kafelki statystyk --}}
        <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-6">
            @php
                $tiles = [
                    [__('dashboard.stats.sessions'), $stats['completed_sessions'], 'text-slate-900 dark:text-white'],
                    [__('dashboard.stats.avg'), $stats['avg_percent'] !== null ? $stats['avg_percent'].'%' : '—', 'text-brand-600 dark:text-brand-400'],
                    [__('dashboard.stats.exams'), $stats['exams_passed'].' / '.$stats['exams_taken'], 'text-emerald-600 dark:text-emerald-400'],
                    [__('dashboard.stats.mastered'), $stats['mastered'], 'text-emerald-600 dark:text-emerald-400'],
                    [__('dashboard.stats.due'), $stats['due'], 'text-amber-600 dark:text-amber-400'],
                    [__('dashboard.stats.answers'), $stats['answered_total'], 'text-slate-900 dark:text-white'],
                ];
            @endphp
            @foreach ($tiles as [$label, $value, $color])
                <div class="card p-4">
                    <div class="font-display text-2xl font-bold tnum {{ $color }}">{{ $value }}</div>
                    <div class="mt-1 text-xs font-medium text-slate-500 dark:text-slate-400">{{ $label }}</div>
                </div>
            @endforeach
        </div>

        {{-- Ostatnie sesje --}}
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('dashboard.recent.title') }}</h2>
            </div>

            @if ($recentSessions->isEmpty())
                <p class="px-5 py-10 text-center text-sm text-slate-500 dark:text-slate-400">
                    {{ __('dashboard.recent.empty') }}
                </p>
            @else
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($recentSessions as $session)
                        <a href="{{ route('sessions.result', $session) }}"
                           class="flex items-center justify-between gap-3 px-5 py-3.5 transition hover:bg-slate-50 dark:hover:bg-slate-800/50">
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="badge badge-slate">{{ $session->category->code }}</span>
                                    <span class="text-sm font-medium text-slate-800 dark:text-slate-200">{{ $session->mode->label() }}</span>
                                    @if ($session->mode->value === 'exam')
                                        <span class="badge {{ $session->passed ? 'badge-emerald' : 'badge-rose' }}">
                                            {{ $session->passed ? __('dashboard.recent.passed') : __('dashboard.recent.failed') }}
                                        </span>
                                    @endif
                                </div>
                                <div class="mt-0.5 text-xs text-slate-400">{{ $session->completed_at?->format('d.m.Y H:i') }}</div>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="font-display text-sm font-bold tnum text-slate-900 dark:text-white">{{ $session->score }}/{{ $session->max_score }}</div>
                                <div class="text-xs text-slate-400 tnum">{{ $session->scorePercent() }}%</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

    </div>
</x-app-layout>
