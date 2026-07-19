@php
    $isSpecialist = $question->type->value === 'specialist';
    $videoUrl = $question->videoUrl();
    $imageUrl = $question->imageUrl();
    $hasVideo = ! $isSpecialist && $videoUrl;
    $readSeconds = (int) config('prawko.timing.basic_read_seconds');
    $answerSeconds = $isSpecialist
        ? (int) config('prawko.timing.specialist_seconds')
        : (int) config('prawko.timing.basic_answer_seconds');
    $isLast = $position >= $total;
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('session.title', ['mode' => __('enums.exam_mode.exam'), 'n' => $position]) }}</x-slot>
    <x-slot name="header">
        <div class="flex items-center justify-between gap-3">
            <span class="badge badge-amber">{{ __('session.exam.badge') }}</span>
            <span class="text-sm font-medium tabular-nums text-slate-500 dark:text-slate-400">{{ $position }} / {{ $total }}</span>
        </div>
    </x-slot>

    <style>[x-cloak]{display:none!important}</style>

    <div class="mx-auto max-w-3xl space-y-5 px-4 py-6 sm:px-6 lg:px-8">

        {{-- Postęp (bez nawigatora — egzamin nie pozwala cofać) --}}
        <div class="flex items-center gap-3">
            <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
                <div class="h-full rounded-full bg-brand-600 transition-all dark:bg-brand-500"
                     style="width: {{ $total ? round($position / $total * 100) : 0 }}%"></div>
            </div>
            <span class="shrink-0 text-xs text-slate-400 tnum">{{ __('session.exam.answered', ['done' => $answeredCount, 'total' => $total]) }}</span>
        </div>

        <div class="card overflow-hidden"
             x-data="{
                phase: @js($isSpecialist ? 'answering' : 'reading'),
                hasVideo: {{ $hasVideo ? 'true' : 'false' }},
                readLeft: {{ $readSeconds }},
                answerLeft: {{ $answerSeconds }},
                selected: null,
                loadedAt: Date.now(),
                pausedMs: 0,
                _playStart: 0,
                _tick: null,
                _submitted: false,
                _lastTime: -1,
                _stalledTicks: 0,
                init() { this._tick = setInterval(() => this.onTick(), 1000); },
                onTick() {
                    if (this.phase === 'reading') { if (--this.readLeft <= 0) this.start(); }
                    else if (this.phase === 'playing') { this.watchFilm(); }
                    else if (this.phase === 'answering') { if (--this.answerLeft <= 0) this.submit(); }
                },
                /**
                 * Nadzorca filmu. Faza 'playing' miała DOKŁADNIE JEDNO wyjście —
                 * zdarzenie `ended`. Gdy ono nie dochodzi (zacięcie bufora, karta
                 * w tle, błąd dekodowania, film bez metadanych długości), pole
                 * odpowiedzi nie pojawiało się już nigdy i egzamin był stracony.
                 * Tu zawsze wychodzimy w stronę odpowiedzi: gorzej odpowiedzieć
                 * bez filmu niż utknąć na zawsze.
                 */
                watchFilm() {
                    const v = this.$refs.film;
                    if (!v) { this.filmEnded(); return; }

                    const dur = Number.isFinite(v.duration) && v.duration > 0 ? v.duration : null;

                    // 1) Film doszedł do końca, ale zdarzenie nie przyszło.
                    if (v.ended || (dur !== null && v.currentTime >= dur - 0.3)) { this.filmEnded(); return; }

                    // 2) Obraz stoi w miejscu mimo trwającego odtwarzania (bufor/zacięcie).
                    //    4 s zamiast 3, żeby nie ucinać wolnego startu na słabym łączu.
                    if (v.currentTime === this._lastTime && ! v.paused) {
                        if (++this._stalledTicks >= 4) { this.filmEnded(); return; }
                    } else {
                        this._stalledTicks = 0;
                        this._lastTime = v.currentTime;
                    }

                    // 3) Twardy limit — nawet gdy długość filmu jest nieznana.
                    const elapsed = (Date.now() - this._playStart) / 1000;
                    if (elapsed > (dur !== null ? dur + 5 : 45)) { this.filmEnded(); }
                },
                start() {
                    if (this.phase !== 'reading') return;
                    if (this.hasVideo) {
                        this.phase = 'playing';
                        this._playStart = Date.now();
                        this.$nextTick(() => {
                            const v = this.$refs.film;
                            if (!v) { this.filmEnded(); return; }
                            const p = v.play();
                            if (p && p.catch) p.catch(() => this.filmEnded());
                        });
                    } else {
                        this.phase = 'answering';
                    }
                },
                filmEnded() {
                    if (this.phase !== 'playing') return;
                    this.pausedMs += Date.now() - this._playStart;
                    this.phase = 'answering';
                },
                submit() {
                    if (this._submitted) return;
                    this._submitted = true;
                    clearInterval(this._tick);
                    this.$refs.spent.value = Math.max(0, Math.round((Date.now() - this.loadedAt) - this.pausedMs));
                    this.$refs.form.submit();
                },
                get timerLeft() { return this.phase === 'reading' ? this.readLeft : this.answerLeft; },
                get phaseLabel() { return this.phase === 'reading' ? @js(__('session.exam.phase_reading')) : (this.phase === 'playing' ? @js(__('session.exam.phase_film')) : @js(__('session.exam.phase_answer'))); }
             }">

            {{-- ===== Materiał ===== --}}
            @if ($hasVideo)
                <div x-show="phase === 'reading'" x-cloak class="flex flex-col items-center justify-center gap-2 bg-slate-900 px-6 py-14 text-center">
                    <svg class="h-10 w-10 text-slate-500" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24"><path d="M10 8.25 15.5 12 10 15.75V8.25ZM3.75 5.25h12.5v13.5H3.75zM19.5 7.5l1.35-.78v10.56L19.5 16.5"/></svg>
                    <p class="text-sm text-slate-300">{!! __('session.exam.film_hint') !!}</p>
                    <p class="text-xs text-slate-500">{{ __('session.exam.film_once') }}</p>
                </div>
                {{-- Film gra w fazie „playing", a po zakończeniu zostaje jako stop-klatka
                     (widoczny przez cały czas odpowiedzi) — bez controls, więc nie da się go powtórzyć. --}}
                {{-- `ended` to nadal droga podstawowa, ale nie jedyna: `error`/`abort` łapią
                     awarie w połowie strumienia (samo `play().catch()` wyłapuje tylko brak
                     STARTU), a nadzorca w onTick() domyka resztę przypadków. --}}
                <video x-show="phase === 'playing' || phase === 'answering'" x-cloak x-ref="film"
                       x-on:ended="filmEnded()" x-on:error="filmEnded()" x-on:abort="filmEnded()"
                       playsinline preload="auto" controlslist="nodownload nofullscreen noremoteplayback" disablepictureinpicture
                       :class="phase === 'answering' ? 'max-h-56' : 'max-h-[26rem]'"
                       class="mx-auto w-full bg-black object-contain">
                    <source src="{{ $videoUrl }}" type="video/mp4">
                </video>
            @elseif ($imageUrl)
                <img src="{{ $imageUrl }}" alt="" loading="eager"
                     onerror="this.style.display='none';"
                     class="mx-auto max-h-[26rem] w-full bg-slate-100 object-contain dark:bg-slate-800">
            @endif

            <div class="p-5 sm:p-6">
                {{-- Plakietki + licznik fazy --}}
                <div class="flex items-start justify-between gap-3">
                    <div class="flex flex-wrap items-center gap-1.5">
                        <span class="badge {{ $isSpecialist ? 'badge-amber' : 'badge-brand' }}">{{ $question->type->label() }}</span>
                        <span class="badge badge-slate">{{ __('session.review.points', ['n' => $question->points]) }}</span>
                    </div>
                    <div class="shrink-0 text-right">
                        <div x-show="phase !== 'playing'" class="flex items-center justify-end gap-1.5">
                            <svg class="h-4 w-4 text-slate-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z"/></svg>
                            <span class="font-display text-2xl font-bold tnum"
                                  :class="timerLeft <= 5 ? 'text-rose-500 dark:text-rose-400' : (phase === 'reading' ? 'text-slate-700 dark:text-slate-200' : 'text-brand-600 dark:text-brand-400')"
                                  x-text="timerLeft + 's'">–</span>
                        </div>
                        <div x-show="phase === 'playing'" x-cloak class="inline-flex items-center gap-1.5 text-sm font-semibold text-slate-500 dark:text-slate-400">
                            <span class="h-2 w-2 animate-pulse rounded-full bg-rose-500"></span> {{ __('session.exam.playing') }}
                        </div>
                        <div class="mt-0.5 text-[11px] font-medium uppercase tracking-wide text-slate-400" x-text="phaseLabel"></div>
                    </div>
                </div>

                {{-- Treść pytania --}}
                <p class="mt-4 text-lg font-medium leading-snug text-slate-900 dark:text-white">{{ $question->localizedText() }}</p>

                {{-- Faza: czytanie --}}
                <div x-show="phase === 'reading'" x-cloak class="mt-5">
                    <p class="text-sm text-slate-500 dark:text-slate-400">
                        {{ $hasVideo
                            ? __('session.exam.read_with_film', ['n' => $answerSeconds])
                            : __('session.exam.read_no_film', ['n' => $answerSeconds]) }}
                    </p>
                    <button type="button" x-on:click="start()" class="btn btn-brand mt-3">
                        {{ $hasVideo ? __('session.exam.start_film') : __('session.exam.go_answer') }}
                    </button>
                </div>

                {{-- Faza: film --}}
                <div x-show="phase === 'playing'" x-cloak class="mt-5 text-sm text-slate-500 dark:text-slate-400">
                    {{ __('session.exam.film_playing') }}
                </div>

                {{-- Faza: odpowiedź --}}
                <form x-show="phase === 'answering'" x-cloak x-ref="form" method="POST"
                      action="{{ route('sessions.answer', [$session, $position]) }}" class="mt-5">
                    @csrf
                    <input type="hidden" name="time_spent_ms" x-ref="spent" value="0">

                    <div class="space-y-2.5">
                        @foreach ($question->answers as $a)
                            <label class="flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-3 transition"
                                   :class="selected === {{ $a->id }}
                                       ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-200 dark:border-brand-400 dark:bg-brand-500/10 dark:ring-brand-500/30'
                                       : 'border-slate-200 hover:border-brand-300 dark:border-slate-800 dark:hover:border-brand-500/40'">
                                <input type="radio" name="answer_id" value="{{ $a->id }}" x-model.number="selected" class="sr-only">
                                @if ($isSpecialist)
                                    <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-bold transition"
                                          :class="selected === {{ $a->id }} ? 'bg-brand-600 text-white' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'">{{ $a->label }}</span>
                                @endif
                                <span class="flex-1 text-sm text-slate-800 dark:text-slate-100">{{ $question->localizedAnswerText($a) }}</span>
                            </label>
                        @endforeach
                    </div>

                    {{-- Przycisk zawsze aktywny: można przejść dalej także bez odpowiedzi (pominięcie).
                         Po przejściu nie ma powrotu ani zmiany odpowiedzi. --}}
                    <div class="mt-6 flex items-center justify-between gap-3">
                        <button type="button" x-on:click="submit()"
                                class="btn {{ $isLast ? 'btn-dark' : 'btn-brand' }}">
                            {{ $isLast ? __('session.exam.finish') : __('session.exam.next') }}
                        </button>
                        <span class="text-xs text-slate-400 dark:text-slate-500">{{ __('session.exam.no_back') }}</span>
                    </div>
                </form>
            </div>
        </div>
    </div>
</x-app-layout>
