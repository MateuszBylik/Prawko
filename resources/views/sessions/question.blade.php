@use('App\Enums\Confidence')

@php
    $instantFeedback = $session->mode->givesInstantFeedback();
    $collectsConfidence = $session->mode->collectsConfidence();
    // Budżet czasu na pytanie wg typu (egzaminacyjny model czasowy) — miękka wskazówka.
    $limitSeconds = $question->type->value === 'basic'
        ? (int) config('prawko.timing.basic_seconds')
        : (int) config('prawko.timing.specialist_seconds');
    // Odliczanie całej sesji pokazujemy tylko, gdy tryb ma limit czasu (egzamin).
    $remaining = null;
    if ($session->time_limit_seconds) {
        $elapsed = (int) $session->started_at->diffInSeconds(now());
        $remaining = max(0, $session->time_limit_seconds - $elapsed);
    }
    $answeredTime = $showFeedback && $current->time_spent_ms ? (int) round($current->time_spent_ms / 1000) : null;
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('session.title', ['mode' => $session->mode->label(), 'n' => $position]) }}</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2">
                <span class="badge {{ $session->mode->isDiagnosis() ? 'badge-brand' : ($session->isExam() ? 'badge-amber' : 'badge-slate') }}">{{ $session->mode->label() }}</span>
                <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('session.category', ['code' => $session->category->code ?? '']) }}</span>
            </div>
            <span class="text-sm font-medium tabular-nums text-slate-500 dark:text-slate-400">
                {{ $position }} / {{ $total }}
            </span>
        </div>
    </x-slot>

    <div class="mx-auto max-w-5xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">

        {{-- Pasek postępu --}}
        <div class="h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
            <div class="h-full rounded-full bg-brand-600 transition-all dark:bg-brand-500"
                 style="width: {{ $total ? round($position / $total * 100) : 0 }}%"></div>
        </div>

        {{-- Odliczanie sesji (egzamin) --}}
        @if ($remaining !== null)
            <div class="card flex items-center justify-between gap-3 px-4 py-3"
                 x-data="{
                    remaining: {{ $remaining }},
                    get display() {
                        const m = Math.floor(this.remaining / 60).toString().padStart(2, '0');
                        const s = (this.remaining % 60).toString().padStart(2, '0');
                        return m + ':' + s;
                    },
                    init() {
                        const t = setInterval(() => {
                            if (this.remaining <= 0) { clearInterval(t); document.getElementById('finish-form')?.submit(); return; }
                            this.remaining--;
                        }, 1000);
                    }
                 }">
                <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('session.time_left') }}</span>
                <span class="font-display text-2xl font-bold tnum" x-text="display"
                      :class="remaining < 60 ? 'text-rose-500 dark:text-rose-400' : 'text-slate-900 dark:text-white'">--:--</span>
            </div>
        @endif

        <div class="grid gap-5 lg:grid-cols-4">

            {{-- Główna kolumna --}}
            <div class="space-y-5 lg:col-span-3">
                <div class="card overflow-hidden"
                     x-data="{
                        selected: {{ $current->selected_answer_id ?? 'null' }},
                        t0: Date.now(),
                        elapsed: 0,
                        limit: {{ $limitSeconds }},
                        feedback: {{ $showFeedback ? 'true' : 'false' }},
                        init() { if (!this.feedback) { this._i = setInterval(() => this.elapsed = Math.floor((Date.now() - this.t0) / 1000), 250); } },
                        get mmss() { const s = this.elapsed % 60; return Math.floor(this.elapsed / 60) + ':' + String(s).padStart(2, '0'); }
                     }">

                    {{-- Materiał (wspólny komponent) --}}
                    <x-question-media :question="$question" />

                    <div class="p-5 sm:p-6">
                        {{-- Plakietki + licznik pytania --}}
                        <div class="flex items-center justify-between gap-3">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="badge {{ $question->type->value === 'basic' ? 'badge-brand' : 'badge-amber' }}">{{ $question->type->label() }}</span>
                                <span class="badge badge-slate">{{ __('session.review.points', ['n' => $question->points]) }}</span>
                                @if (! empty($isNew))
                                    <span class="badge badge-emerald gap-1">
                                        <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24"><path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.717 5.287a1 1 0 0 0 .95.69h5.562c.969 0 1.371 1.24.588 1.81l-4.5 3.27a1 1 0 0 0-.363 1.118l1.718 5.287c.3.921-.755 1.688-1.539 1.118l-4.5-3.27a1 1 0 0 0-1.176 0l-4.5 3.27c-.783.57-1.838-.197-1.539-1.118l1.718-5.287a1 1 0 0 0-.363-1.118l-4.5-3.27c-.783-.57-.38-1.81.588-1.81h5.563a1 1 0 0 0 .95-.69l1.717-5.287Z"/></svg>
                                        {{ __('session.new_badge') }}
                                    </span>
                                @endif
                            </div>
                            @unless ($showFeedback)
                                <span class="inline-flex items-center gap-1.5 text-sm font-semibold tnum"
                                      :class="elapsed > limit ? 'text-rose-500 dark:text-rose-400' : (elapsed > limit * 0.7 ? 'text-amber-500 dark:text-amber-400' : 'text-slate-400 dark:text-slate-500')">
                                    <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z"/></svg>
                                    <span x-text="mmss">0:00</span>
                                    <span class="text-slate-300 dark:text-slate-600">/ {{ $limitSeconds }}s</span>
                                </span>
                            @endunless
                        </div>

                        {{-- Treść pytania --}}
                        <p class="mt-4 text-lg font-medium leading-snug text-slate-900 dark:text-white">{{ $question->localizedText() }}</p>

                        {{-- ===== FEEDBACK (nauka po odpowiedzi) ===== --}}
                        @if ($showFeedback)
                            <div class="mt-4 flex items-center gap-2 rounded-xl px-4 py-3 text-sm font-semibold
                                {{ $current->is_correct
                                    ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-300'
                                    : 'bg-rose-50 text-rose-700 dark:bg-rose-500/10 dark:text-rose-300' }}">
                                @if ($current->is_correct)
                                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                                    {{ __('session.feedback.correct') }}
                                @else
                                    <svg class="h-5 w-5 shrink-0" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12"/></svg>
                                    {{ __('session.feedback.wrong') }}
                                @endif

                                {{-- Plakietkę dostają TYLKO przypadki rozjazdu deklaracji z wynikiem —
                                     bo tylko one niosą informację. „Wiedziałem i trafiłem" to norma. --}}
                                @if ($current->confidence === Confidence::Sure && ! $current->is_correct)
                                    <span class="badge badge-rose">{{ __('session.feedback.sure_mistake') }}</span>
                                @elseif ($current->confidence === Confidence::Guess && $current->is_correct)
                                    <span class="badge badge-amber">{{ __('session.feedback.lucky_guess') }}</span>
                                @endif

                                @if ($answeredTime !== null)
                                    <span class="ml-auto font-normal text-slate-400 dark:text-slate-500 tnum">{{ __('session.feedback.time', ['n' => $answeredTime]) }}</span>
                                @endif
                            </div>

                            <div class="mt-4 space-y-2.5">
                                @foreach ($question->answers as $a)
                                    @php $isChosen = $current->selected_answer_id === $a->id; @endphp
                                    <div class="flex items-start gap-3 rounded-xl border px-4 py-3
                                        {{ $a->is_correct
                                            ? 'border-emerald-300 bg-emerald-50 dark:border-emerald-500/40 dark:bg-emerald-500/10'
                                            : ($isChosen ? 'border-rose-300 bg-rose-50 dark:border-rose-500/40 dark:bg-rose-500/10' : 'border-slate-200 dark:border-slate-800') }}">
                                        @if ($question->type->value === 'specialist')
                                            <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-bold
                                                {{ $a->is_correct ? 'bg-emerald-600 text-white' : ($isChosen ? 'bg-rose-500 text-white' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300') }}">{{ $a->label }}</span>
                                        @endif
                                        <span class="flex-1 text-sm {{ $a->is_correct ? 'font-semibold text-emerald-800 dark:text-emerald-200' : 'text-slate-700 dark:text-slate-200' }}">{{ $question->localizedAnswerText($a) }}</span>
                                        @if ($a->is_correct)
                                            <span class="badge badge-emerald shrink-0">{{ __('session.feedback.correct_badge') }}</span>
                                        @elseif ($isChosen)
                                            <span class="badge badge-rose shrink-0">{{ __('session.feedback.your_choice') }}</span>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            {{-- Dopiero PO odpowiedzi: tabliczka podaje literę odpowiedzi
                                 katalogowej, więc przed sprawdzeniem byłaby ściągą. --}}
                            <x-disputed-answer :question="$question" class="mt-4" />

                            @if (filled($question->explanation))
                                <div class="mt-4 rounded-xl border border-brand-200 bg-brand-50 p-4 text-sm text-slate-700 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-slate-200">
                                    <p class="mb-1 font-semibold text-brand-700 dark:text-brand-300">{{ __('session.explanation.title') }}</p>
                                    {{ $question->explanation }}
                                </div>
                            @endif

                            <div class="mt-6">
                                @if ($next)
                                    <a href="{{ route('sessions.question', [$session, $next]) }}" class="btn btn-brand">{{ __('session.feedback.next') }}</a>
                                @else
                                    {{-- Ostatnie pytanie: główny przycisk kończy sesję. --}}
                                    <button type="submit" form="finish-form" class="btn btn-brand">{{ __('session.actions.finish') }}</button>
                                @endif
                            </div>

                        {{-- ===== FORMULARZ ODPOWIEDZI ===== --}}
                        @else
                            <form method="POST" action="{{ route('sessions.answer', [$session, $position]) }}"
                                  @submit="$refs.spent.value = Date.now() - t0" class="mt-5">
                                @csrf
                                <input type="hidden" name="time_spent_ms" x-ref="spent" value="0">

                                <div class="space-y-2.5">
                                    @foreach ($question->answers as $a)
                                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-3 transition"
                                               :class="selected === {{ $a->id }}
                                                   ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-200 dark:border-brand-400 dark:bg-brand-500/10 dark:ring-brand-500/30'
                                                   : 'border-slate-200 hover:border-brand-300 dark:border-slate-800 dark:hover:border-brand-500/40'">
                                            <input type="radio" name="answer_id" value="{{ $a->id }}" x-model.number="selected" class="sr-only">
                                            @if ($question->type->value === 'specialist')
                                                <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-bold transition"
                                                      :class="selected === {{ $a->id }} ? 'bg-brand-600 text-white' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'">{{ $a->label }}</span>
                                            @endif
                                            <span class="flex-1 text-sm text-slate-800 dark:text-slate-100">{{ $question->localizedAnswerText($a) }}</span>
                                        </label>
                                    @endforeach
                                </div>

                                @if ($collectsConfidence)
                                    {{-- Ocena pewności ZAMIAST zwykłego „Sprawdź" — nie obok niego.
                                         Dwa przyciski to nadal jeden tap, więc nauka nic nie kosztuje
                                         więcej, a silnik dostaje sygnał, którego z samej poprawności
                                         odtworzyć się nie da: czy wiedziałeś, czy trafiłeś. --}}
                                    <div class="mt-6">
                                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">
                                            {{ __('session.confidence.prompt') }}
                                        </p>
                                        <div class="mt-2.5 flex flex-col gap-2 sm:flex-row">
                                            <button type="submit" name="confidence" value="{{ Confidence::Sure->value }}"
                                                    :disabled="selected === null"
                                                    class="btn btn-brand w-full disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto">
                                                {{ __('enums.confidence.sure') }}
                                            </button>
                                            <button type="submit" name="confidence" value="{{ Confidence::Guess->value }}"
                                                    :disabled="selected === null"
                                                    class="btn btn-outline w-full disabled:cursor-not-allowed disabled:opacity-40 sm:w-auto">
                                                {{ __('enums.confidence.guess') }}
                                            </button>
                                        </div>
                                        <p class="mt-2.5 text-xs text-slate-400 dark:text-slate-500">
                                            {{ __('session.confidence.hint') }}
                                        </p>
                                    </div>
                                @else
                                    <div class="mt-6 flex items-center gap-4">
                                        {{-- Bez natychmiastowego feedbacku (diagnoza) ostatnie pytanie od razu
                                             kończy sesję — kontroler domyka ją po zapisaniu odpowiedzi. --}}
                                        <button type="submit" :disabled="selected === null" class="btn btn-brand disabled:cursor-not-allowed disabled:opacity-40">
                                            @if ($instantFeedback)
                                                {{ __('session.actions.check') }}
                                            @else
                                                {{ $next ? __('session.actions.save_next') : __('session.actions.finish') }}
                                            @endif
                                        </button>
                                        @if (! $instantFeedback && $next)
                                            <a href="{{ route('sessions.question', [$session, $next]) }}" class="text-sm font-medium text-slate-500 hover:text-slate-800 dark:text-slate-400 dark:hover:text-slate-200">{{ __('session.actions.skip') }}</a>
                                        @endif
                                    </div>
                                @endif
                            </form>
                        @endif
                    </div>
                </div>

                {{-- Dolna nawigacja --}}
                <div class="flex items-center justify-between gap-3">
                    <div>
                        @if ($prev)
                            <a href="{{ route('sessions.question', [$session, $prev]) }}" class="btn btn-outline btn-sm">{{ __('session.actions.prev') }}</a>
                        @endif
                    </div>
                    {{-- Formularz musi istnieć zawsze — na ostatnim pytaniu korzysta z niego
                         główny przycisk (form="finish-form"). Osobny, dyskretny przycisk
                         pokazujemy tylko WCZEŚNIEJ, gdy chcesz przerwać sesję przed końcem. --}}
                    <form method="POST" action="{{ route('sessions.complete', $session) }}" id="finish-form">
                        @csrf
                        @if ($next)
                            <button type="submit" onclick="return confirm('{{ __('session.actions.confirm_finish') }}')"
                                    class="btn btn-ghost btn-sm text-slate-500 dark:text-slate-400">
                                {{ __('session.actions.finish') }}
                            </button>
                        @endif
                    </form>
                </div>
            </div>

            {{-- Nawigator pytań --}}
            <aside class="lg:col-span-1">
                <div class="card p-4">
                    <div class="mb-3 flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('session.navigator.title') }}</span>
                        <span class="text-xs text-slate-400 tnum">{{ $answeredCount }}/{{ $total }}</span>
                    </div>
                    <div class="grid grid-cols-8 gap-1.5 sm:grid-cols-10 lg:grid-cols-5">
                        @foreach ($navigator as $item)
                            @php
                                $answered = $item->answered_at !== null;
                                $isCurrent = $item->position === $position;
                            @endphp
                            <a href="{{ route('sessions.question', [$session, $item->position]) }}"
                               class="flex h-8 items-center justify-center rounded-lg text-xs font-semibold tnum transition
                                   {{ $isCurrent
                                       ? 'bg-brand-600 text-white'
                                       : ($answered
                                           ? 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/15 dark:text-emerald-300'
                                           : 'bg-slate-100 text-slate-500 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:hover:bg-slate-700') }}">
                                {{ $item->position }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </aside>
        </div>
    </div>
</x-app-layout>
