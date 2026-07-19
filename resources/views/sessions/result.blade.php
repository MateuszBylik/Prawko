@php
    $isExam = $session->isExam();
    $percent = $session->scorePercent();
    $tone = $isExam
        ? ($session->passed ? 'emerald' : 'rose')
        : ($percent >= 90 ? 'emerald' : ($percent >= 60 ? 'amber' : 'rose'));
    $toneText = [
        'emerald' => 'text-emerald-600 dark:text-emerald-400',
        'amber' => 'text-amber-600 dark:text-amber-400',
        'rose' => 'text-rose-500 dark:text-rose-400',
    ][$tone];
    $toneRing = [
        'emerald' => 'bg-emerald-50 dark:bg-emerald-500/10',
        'amber' => 'bg-amber-50 dark:bg-amber-500/10',
        'rose' => 'bg-rose-50 dark:bg-rose-500/10',
    ][$tone];
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('session.result.title_short', ['mode' => $session->mode->label()]) }}</x-slot>
    <x-slot name="header">
        <h1 class="font-display text-xl font-bold tracking-tight text-slate-900 dark:text-white">
            {{ __('session.result.heading', ['mode' => $session->mode->label(), 'code' => $session->category->code ?? '']) }}
        </h1>
    </x-slot>

    <div class="mx-auto max-w-3xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

        {{-- Podsumowanie --}}
        <div class="card p-6 sm:p-8">
            <div class="flex flex-col items-center gap-6 sm:flex-row sm:gap-8">
                <div class="grid h-28 w-28 shrink-0 place-items-center rounded-full {{ $toneRing }}">
                    <span class="font-display text-3xl font-bold tnum {{ $toneText }}">{{ $percent }}%</span>
                </div>

                <div class="flex-1 text-center sm:text-left">
                    @if ($isExam)
                        <span class="badge {{ $session->passed ? 'badge-emerald' : 'badge-rose' }}">
                            {{ $session->passed ? __('session.result.exam_passed') : __('session.result.exam_failed') }}
                        </span>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400">{{ __('session.result.threshold', ['n' => config('prawko.exam.pass_threshold'), 'max' => $session->max_score]) }}</p>
                    @endif
                    <div class="mt-2 font-display text-2xl font-bold tnum text-slate-900 dark:text-white">
                        {{ __('session.result.score', ['score' => $session->score, 'max' => $session->max_score]) }}
                    </div>
                    <div class="mt-1 text-sm text-slate-500 dark:text-slate-400">
                        {!! __('session.result.correct_count', [
                            'correct' => '<span class="tnum">'.$session->correct_count.'</span>',
                            'total' => '<span class="tnum">'.$session->total_questions.'</span>',
                        ]) !!}
                    </div>
                </div>

                <div class="flex w-full flex-col gap-2 sm:w-auto">
                    <a href="{{ route('dashboard') }}" class="btn btn-brand">{{ __('session.result.back') }}</a>
                    <form method="POST" action="{{ route('sessions.store') }}">
                        @csrf
                        <input type="hidden" name="mode" value="{{ $session->mode->value }}">
                        <button type="submit" class="btn btn-outline w-full">{{ __('session.result.again') }}</button>
                    </form>
                </div>
            </div>
        </div>

        {{-- Przegląd odpowiedzi --}}
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('session.result.review') }}</h2>
            </div>
            <x-answer-review :session="$session" />
        </div>
    </div>
</x-app-layout>
