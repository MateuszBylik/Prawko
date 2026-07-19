<x-app-layout>
    <x-slot name="title">{{ __('session.diagnosis.result_title') }}</x-slot>
    <x-slot name="header">
        <div>
            <h1 class="font-display text-xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('session.diagnosis.title') }}</h1>
            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ __('session.diagnosis.lead') }}</p>
        </div>
    </x-slot>

    @php
        $r = $forecast->readiness();
        $toneText = [
            'emerald' => 'text-emerald-600 dark:text-emerald-400',
            'brand' => 'text-brand-600 dark:text-brand-400',
            'amber' => 'text-amber-600 dark:text-amber-400',
            'rose' => 'text-rose-500 dark:text-rose-400',
        ][$r['tone']];
        // Ton pochodzi z ExamForecast::readiness() i jest kluczem do tłumaczenia —
        // dzięki temu progi gotowości siedzą w jednym miejscu (DTO), a nie w widoku.
        $interpretation = __('session.diagnosis.verdict_'.$r['tone']);
        $pMed = $forecast->pctOf($forecast->medianScore);
        $pLow = $forecast->pctOf($forecast->ciLow);
        $pHigh = $forecast->pctOf($forecast->ciHigh);
        $pThr = $forecast->pctOf($forecast->threshold);
        $bandTone = $forecast->medianScore >= $forecast->threshold ? 'bg-emerald-500/25' : 'bg-brand-500/20';
        $medTone = $forecast->medianScore >= $forecast->threshold ? 'bg-emerald-500' : 'bg-brand-500';
    @endphp

    <div class="mx-auto max-w-3xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

        {{-- Odsłona: jedna liczba --}}
        <div class="card p-6 sm:p-8">
            <div class="text-center">
                <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">Twoja szansa zdania</div>
                <div class="mt-1 font-display text-7xl font-bold leading-none tnum {{ $toneText }}">
                    {{ $forecast->passPercent() }}<span class="text-4xl">%</span>
                </div>
                <span class="badge badge-{{ $r['tone'] }} mt-4">{{ $r['label'] }}</span>
            </div>

            {{-- Pasek prognozy --}}
            <div class="mt-8">
                <div class="flex items-baseline justify-between gap-3">
                    <span class="text-sm font-medium text-slate-700 dark:text-slate-200">Prognozowany wynik</span>
                    <span class="font-display text-sm font-bold tnum text-slate-900 dark:text-white">
                        {{ $forecast->medianScore }}<span class="text-slate-400">/{{ $forecast->maxScore }} pkt</span>
                    </span>
                </div>
                <div class="relative mt-3 h-3 rounded-full bg-slate-100 dark:bg-slate-800">
                    <div class="absolute inset-y-0 rounded-full {{ $bandTone }}"
                         style="left: {{ $pLow }}%; width: {{ max(1.5, $pHigh - $pLow) }}%"></div>
                    <div class="absolute inset-y-0 w-0.5 {{ $medTone }}" style="left: calc({{ $pMed }}% - 1px)"></div>
                    <div class="absolute -inset-y-1 w-px border-l border-dashed border-slate-400 dark:border-slate-500"
                         style="left: {{ $pThr }}%"></div>
                </div>
                <div class="mt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 tnum">
                    <span>zakres {{ $forecast->ciLow }}–{{ $forecast->ciHigh }} pkt</span>
                    <span>{{ __('dashboard.forecast.threshold', ['n' => $forecast->threshold]) }}</span>
                </div>
            </div>

            <p class="mt-6 text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $interpretation }}</p>

            <div class="mt-4 flex items-center justify-center gap-6 border-t border-slate-100 pt-4 text-center dark:border-slate-800">
                <div>
                    <div class="font-display text-lg font-bold tnum text-slate-900 dark:text-white">{{ $session->correct_count }}/{{ $session->total_questions }}</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">poprawnych</div>
                </div>
                <div>
                    <div class="font-display text-lg font-bold tnum text-slate-900 dark:text-white">{{ $session->scorePercent() }}%</div>
                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('session.diagnosis.accuracy') }}</div>
                </div>
            </div>
        </div>

        {{-- Twoje błędy w tej diagnozie --}}
        @php $mistakes = $session->answers->where('is_correct', false); @endphp
        @if ($mistakes->isNotEmpty())
            <div x-data="{ all: false }" class="card overflow-hidden">
                <div class="flex flex-wrap items-center justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    <div>
                        <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('session.diagnosis.mistakes_title') }}</h2>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            {!! __('session.diagnosis.mistakes_lead', [
                                'wrong' => $mistakes->count(),
                                'total' => $session->total_questions,
                            ]) !!}
                        </p>
                    </div>
                    <button type="button" @click="all = !all" class="btn btn-outline btn-sm shrink-0">
                        <span x-show="!all">{{ __('session.diagnosis.show_all') }}</span>
                        <span x-show="all" x-cloak>{{ __('session.diagnosis.show_mistakes') }}</span>
                    </button>
                </div>

                {{-- Dwa warianty renderowane po stronie serwera; Alpine tylko przełącza widoczność.
                     Wyjaśnienia AI świadomie NA ŻĄDANIE — przy kilkunastu błędach automat
                     odpaliłby tyleż równoległych, kilkunastosekundowych żądań do modelu. --}}
                <div x-show="!all">
                    <x-answer-review :session="$session" :only-mistakes="true" :auto-explain="false" />
                </div>
                <div x-show="all" x-cloak>
                    <x-answer-review :session="$session" :auto-explain="false" />
                </div>
            </div>
        @endif

        {{-- Od czego zacząć --}}
        @if ($riskQuestions->isNotEmpty())
            <div class="card overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('session.diagnosis.start_title') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('session.diagnosis.start_lead') }}</p>
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
                                    <span class="badge badge-brand">{{ $q->points }} pkt</span>
                                </div>
                                <p class="mt-1 truncate text-sm text-slate-700 dark:text-slate-200">{{ Str::limit($q->localizedText(), 90) }}</p>
                            </div>
                            <div class="shrink-0 text-right">
                                <div class="font-display text-sm font-bold tnum {{ $pTone }}">{{ $pp }}%</div>
                                <div class="text-[11px] text-slate-400">szansa</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Akcje --}}
        <div class="flex flex-col gap-3 sm:flex-row">
            <form method="POST" action="{{ route('sessions.store') }}" class="sm:flex-1">
                @csrf
                <input type="hidden" name="mode" value="practice">
                <button type="submit" class="btn btn-brand w-full">{{ __('session.diagnosis.start_practice') }}</button>
            </form>
            <a href="{{ route('dashboard') }}" class="btn btn-outline sm:flex-1">{{ __('session.diagnosis.to_dashboard') }}</a>
        </div>
    </div>
</x-app-layout>
