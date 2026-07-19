@props([
    'session',
    // Tylko błędne/pominięte — na długiej diagnozie (40 pytań) przegląd wszystkiego
    // pogrzebałby to, po co tu przychodzisz: co poszło źle.
    'onlyMistakes' => false,
    // Automatyczne generowanie wyjaśnień AI. UWAGA: przy wielu błędach naraz to tyleż
    // równoległych, kilkunastosekundowych żądań — dla diagnozy zostaw false (na żądanie).
    'autoExplain' => true,
])

@php
    $items = $session->answers->filter(
        fn ($a) => ! $onlyMistakes || ! $a->is_correct
    );
@endphp

<ul class="divide-y divide-slate-100 dark:divide-slate-800">
    @foreach ($items as $answer)
        @php
            $question = $answer->question;
            $correct = $question->answers->firstWhere('is_correct', true);
            $answered = $answer->answered_at !== null;
        @endphp
        <li class="px-5 py-4">
            <div class="flex items-start gap-3">
                <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-bold text-white tnum
                    {{ ! $answered ? 'bg-slate-400' : ($answer->is_correct ? 'bg-emerald-500' : 'bg-rose-500') }}">
                    {{ $answer->position }}
                </span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-medium text-slate-900 dark:text-slate-100">{{ $question->localizedText() }}</p>
                    <div class="mt-1 space-y-0.5 text-sm">
                        <p class="text-slate-600 dark:text-slate-400">
                            {{ __('session.review.your_answer') }}
                            <span class="font-semibold {{ $answer->is_correct ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-500 dark:text-rose-400' }}">
                                {{ $answer->selectedAnswer ? $question->localizedAnswerText($answer->selectedAnswer) : __('session.review.skipped') }}
                            </span>
                        </p>
                        @unless ($answer->is_correct)
                            <p class="text-slate-600 dark:text-slate-400">
                                {{ __('session.review.correct_answer') }} <span class="font-semibold text-emerald-600 dark:text-emerald-400">{{ $correct ? $question->localizedAnswerText($correct) : "" }}</span>
                            </p>
                        @endunless
                    </div>

                    {{-- Przegląd wyników: tu odpowiedzi i tak są odsłonięte, więc tabliczka
                         trafia do podsumowania KAŻDEJ sesji — nauki, egzaminu i diagnozy. --}}
                    <x-disputed-answer :question="$question" class="mt-2.5" />

                    {{-- Wyjaśnienie błędu (AI) --}}
                    @unless ($answer->is_correct)
                        <div class="mt-2">
                            <x-explanation :question="$question" :chosen-id="$answer->selected_answer_id" :auto="$autoExplain" />
                        </div>
                    @elseif (filled($question->explanation))
                        <p class="mt-1.5 text-xs text-slate-400 dark:text-slate-500">{{ $question->explanation }}</p>
                    @endunless

                    {{-- Podgląd pytania: film/zdjęcie + wszystkie odpowiedzi --}}
                    <details class="group mt-2">
                        <summary class="inline-flex cursor-pointer items-center gap-1.5 text-xs font-medium text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
                            <svg class="h-3.5 w-3.5 transition group-open:rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                            {{ __('session.review.preview') }}
                        </summary>
                        <div class="mt-3 space-y-3">
                            @if ($question->media_type->hasMedia())
                                <div class="overflow-hidden rounded-xl border border-slate-200 dark:border-slate-800">
                                    <x-question-media :question="$question" preload="none" />
                                </div>
                            @endif
                            <div class="space-y-1.5">
                                @foreach ($question->answers as $opt)
                                    @php $chosen = $answer->selected_answer_id === $opt->id; @endphp
                                    <div class="flex items-start gap-2 rounded-lg border px-3 py-2 text-sm
                                        {{ $opt->is_correct
                                            ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-500/40 dark:bg-emerald-500/10'
                                            : ($chosen ? 'border-rose-300 bg-rose-50 dark:border-rose-500/40 dark:bg-rose-500/10' : 'border-slate-200 dark:border-slate-800') }}">
                                        @if ($question->type->value === 'specialist')
                                            <span class="mt-0.5 grid h-5 w-5 shrink-0 place-items-center rounded-full text-[11px] font-bold
                                                {{ $opt->is_correct ? 'bg-emerald-600 text-white' : ($chosen ? 'bg-rose-500 text-white' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300') }}">{{ $opt->label }}</span>
                                        @endif
                                        <span class="flex-1 {{ $opt->is_correct ? 'font-semibold text-emerald-800 dark:text-emerald-200' : 'text-slate-700 dark:text-slate-200' }}">{{ $question->localizedAnswerText($opt) }}</span>
                                        @if ($opt->is_correct)
                                            <span class="badge badge-emerald shrink-0">{{ __('session.feedback.correct_badge') }}</span>
                                        @elseif ($chosen)
                                            <span class="badge badge-rose shrink-0">{{ __('session.feedback.your_choice') }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </details>
                </div>
                <span class="shrink-0 text-xs text-slate-400 tnum">{{ __('session.review.points', ['n' => $question->points]) }}</span>
            </div>
        </li>
    @endforeach
</ul>
