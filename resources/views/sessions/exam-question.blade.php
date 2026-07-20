@php
    $isSpecialist = $question->type->value === 'specialist';
    $videoUrl = $question->videoUrl();
    $imageUrl = $question->imageUrl();
    $hasVideo = ! $isSpecialist && $videoUrl;
    $hasImage = ! $isSpecialist && ! $hasVideo && $imageUrl;
    $hasMedia = $hasVideo || $hasImage;
    // Model czasu jak na egzaminie PORD (pytania podstawowe): najpierw czas na
    // zapoznanie się z pytaniem, potem — po odtworzeniu materiału — czas na odpowiedź.
    // Odpowiedzi i przyciski są widoczne od razu; liczniki tylko wyznaczają rytm.
    $readSeconds = (int) config('prawko.timing.basic_read_seconds');   // 20 s
    $answerSeconds = $isSpecialist
        ? (int) config('prawko.timing.specialist_seconds')             // 50 s (jedna faza)
        : (int) config('prawko.timing.basic_answer_seconds');          // 15 s po materiale
    $isLast = $position >= $total;
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('session.title', ['mode' => __('enums.exam_mode.exam'), 'n' => $position]) }}</x-slot>

    <style>[x-cloak]{display:none!important}</style>

    <div class="mx-auto max-w-6xl px-4 py-4 sm:px-6 lg:px-8"
         x-data="{
            phase: @js($isSpecialist ? 'answering' : 'reading'),
            hasVideo: {{ $hasVideo ? 'true' : 'false' }},
            hasMedia: {{ $hasMedia ? 'true' : 'false' }},
            mediaShown: {{ $isSpecialist ? 'true' : 'false' }},
            readLeft: {{ $readSeconds }},
            readTotal: {{ $readSeconds }},
            answerLeft: {{ $answerSeconds }},
            answerTotal: {{ $answerSeconds }},
            totalLeft: {{ (int) $remaining }},
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
                // Globalny czas egzaminu leci zawsze — 0 kończy CAŁY egzamin.
                if (--this.totalLeft <= 0) { this.finishExam(); return; }
                if (this.phase === 'reading') { if (--this.readLeft <= 0) this.start(); }                          // przy 0 s material startuje sam
                else if (this.phase === 'playing') { this.watchFilm(); }                     // film gra: liczniki stoją
                else { if (--this.answerLeft <= 0) this.submit(); }                          // answering: 0 → dalej
            },
            /**
             * Nadzorca filmu. Odtwarzanie ma DOKŁADNIE JEDNO wyjście naturalne —
             * zdarzenie `ended`. Gdy ono nie dochodzi (zacięcie bufora, karta w
             * tle, błąd dekodowania, film bez metadanych długości), sami domykamy
             * fazę: gorzej odpowiadać bez filmu niż utknąć na zawsze z zegarem w miejscu.
             */
            watchFilm() {
                const v = this.$refs.film;
                if (!v) { this.filmEnded(); return; }
                const dur = Number.isFinite(v.duration) && v.duration > 0 ? v.duration : null;
                if (v.ended || (dur !== null && v.currentTime >= dur - 0.3)) { this.filmEnded(); return; }
                if (v.currentTime === this._lastTime && ! v.paused) {
                    if (++this._stalledTicks >= 4) { this.filmEnded(); return; }
                } else {
                    this._stalledTicks = 0;
                    this._lastTime = v.currentTime;
                }
                const elapsed = (Date.now() - this._playStart) / 1000;
                if (elapsed > (dur !== null ? dur + 5 : 45)) { this.filmEnded(); }
            },
            start() {
                if (this.phase !== 'reading') return;
                this.mediaShown = true;
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
                    // Zdjęcie (albo brak mediów): od razu 15 s na odpowiedź.
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
            finishExam() {
                if (this._submitted) return;
                this._submitted = true;
                clearInterval(this._tick);
                document.getElementById('finish-form')?.submit();
            },
            get totalDisplay() {
                const m = Math.floor(Math.max(0, this.totalLeft) / 60);
                const s = Math.max(0, this.totalLeft) % 60;
                return String(m).padStart(2, '0') + ':' + String(s).padStart(2, '0');
            },
            get phaseTimeLeft() { return this.phase === 'reading' ? this.readLeft : this.answerLeft; },
            get barPct() {
                if (this.phase === 'playing') return 100;
                if (this.phase === 'reading') return Math.max(0, this.readLeft / this.readTotal * 100);
                return Math.max(0, this.answerLeft / this.answerTotal * 100);
            },
            get barLabel() {
                if (this.phase === 'reading') return @js(__('session.exam.read_time_label'));
                if (this.phase === 'playing') return @js(__('session.exam.phase_film'));
                return @js(__('session.exam.answer_time_label'));
            }
         }">

        {{-- ===== Górny pasek: czas całego egzaminu · punkty · kategoria · ZAKOŃCZ ===== --}}
        <div class="mb-3 flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-x-5 gap-y-1.5 text-sm">
                <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                    {{ __('session.exam.total_time_label') }}
                    <span class="rounded-md px-2 py-0.5 font-bold tnum"
                          :class="totalLeft <= 60 ? 'bg-rose-100 text-rose-700 dark:bg-rose-500/15 dark:text-rose-300' : 'bg-brand-100 text-brand-700 dark:bg-brand-500/15 dark:text-brand-200'"
                          x-text="totalDisplay">25:00</span>
                </span>
                <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                    {{ __('session.exam.points_label') }}
                    <span class="badge badge-brand">{{ $question->points }}</span>
                </span>
                <span class="flex items-center gap-2 text-slate-500 dark:text-slate-400">
                    {{ __('session.exam.category_label') }}
                    <span class="badge badge-brand">{{ $session->category->code ?? '' }}</span>
                </span>
            </div>
            <button type="submit" form="finish-form"
                    onclick="return confirm(@js(__('session.exam.confirm_end')))"
                    class="inline-flex items-center gap-2 rounded-lg bg-rose-600 px-4 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-rose-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-rose-500">
                {{ __('session.exam.finish') }}
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9.75 9.75l4.5 4.5m0-4.5l-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z"/></svg>
            </button>
        </div>

        {{-- Postęp (bez nawigatora — egzamin nie pozwala cofać) --}}
        <div class="mb-3 h-1.5 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-800">
            <div class="h-full rounded-full bg-brand-600 dark:bg-brand-500"
                 style="width: {{ $total ? round($position / $total * 100) : 0 }}%"></div>
        </div>

        <div class="grid items-stretch gap-4 lg:grid-cols-3">

            {{-- ===== LEWA: zdjęcie/film (stała wysokość), pytanie, odpowiedzi ===== --}}
            <div class="lg:col-span-2">
                <div class="card overflow-hidden">

                    {{-- Materiał wypełnia CAŁĄ szerokość (bez czarnych pasów po bokach).
                         Film jest 16:9 (aspect-video). Zdjęcia w naturalnych proporcjach.
                         Gdy pytanie nie ma mediów — przekreślony aparat w ramce 16:9. --}}
                    @if ($hasVideo)
                        <div class="relative aspect-video w-full bg-slate-900">
                            <button type="button" x-show="!mediaShown" x-on:click="start()"
                                    class="absolute inset-0 flex flex-col items-center justify-center gap-2 text-center text-slate-300 transition hover:bg-slate-800">
                                <svg class="h-12 w-12" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M21 7.5V6.75A2.25 2.25 0 0 0 18.75 4.5H5.25A2.25 2.25 0 0 0 3 6.75v10.5A2.25 2.25 0 0 0 5.25 19.5h13.5A2.25 2.25 0 0 0 21 17.25V16.5m-10.5-6 5 3-5 3v-6Z"/></svg>
                                <span class="text-sm font-medium">{{ __('session.exam.show_video') }}</span>
                            </button>
                            <video x-show="mediaShown" x-cloak x-ref="film"
                                   x-on:ended="filmEnded()" x-on:error="filmEnded()" x-on:abort="filmEnded()"
                                   playsinline preload="auto" controlslist="nodownload nofullscreen noremoteplayback" disablepictureinpicture
                                   class="absolute inset-0 h-full w-full object-cover">
                                <source src="{{ $videoUrl }}" type="video/mp4">
                            </video>
                        </div>
                    @elseif ($hasImage)
                        <button type="button" x-show="!mediaShown" x-on:click="start()"
                                class="flex aspect-[4/3] w-full flex-col items-center justify-center gap-2 bg-slate-100 text-center text-slate-400 transition hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-500 dark:hover:bg-slate-700">
                            <svg class="h-12 w-12" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m2.25 15.75 5.159-5.159a2.25 2.25 0 0 1 3.182 0l5.159 5.159m-1.5-1.5 1.409-1.409a2.25 2.25 0 0 1 3.182 0l2.909 2.909M3.75 3.75h16.5v16.5H3.75z"/></svg>
                            <span class="text-sm font-medium">{{ __('session.exam.show_image') }}</span>
                        </button>
                        <img x-show="mediaShown" x-cloak src="{{ $imageUrl }}" alt="" loading="eager"
                             onerror="this.style.display='none';"
                             class="block w-full">
                    @else
                        {{-- Brak mediów: przekreślony aparat w ramce 16:9. --}}
                        <div class="flex aspect-video w-full items-center justify-center bg-slate-100 text-slate-300 dark:bg-slate-800 dark:text-slate-600">
                            <svg class="h-14 w-14" fill="none" stroke="currentColor" stroke-width="1.4" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="m15.75 10.5 4.72-4.72a.75.75 0 0 1 1.28.53v11.38a.75.75 0 0 1-1.28.53l-4.72-4.72M12 18.75H4.5a2.25 2.25 0 0 1-2.25-2.25V9m12.841 9.091L16.5 19.5m-1.409-1.409c.407-.407.659-.97.659-1.591v-9a2.25 2.25 0 0 0-2.25-2.25h-9c-.621 0-1.184.252-1.591.659m12.182 12.182L2.909 5.909M1.5 4.5l1.409 1.409"/></svg>
                        </div>
                    @endif

                    <div class="p-4 sm:p-5">
                        {{-- Typ pytania + pozycja --}}
                        <div class="flex items-center gap-2">
                            <span class="badge {{ $isSpecialist ? 'badge-amber' : 'badge-brand' }}">{{ $question->type->label() }}</span>
                            <span class="text-xs text-slate-400 tnum">{{ $position }} / {{ $total }}</span>
                        </div>

                        {{-- Treść pytania --}}
                        <p class="mt-2.5 text-base font-medium leading-snug text-slate-900 dark:text-white sm:text-lg">{{ $question->localizedText() }}</p>

                        {{-- Odpowiedzi — widoczne OD RAZU. Zaznaczenie nie przechodzi dalej;
                             robi to „Następne pytanie" albo koniec czasu (auto-wybór zaznaczonej). --}}
                        <form x-ref="form" method="POST" action="{{ route('sessions.answer', [$session, $position]) }}" class="mt-4">
                            @csrf
                            <input type="hidden" name="time_spent_ms" x-ref="spent" value="0">

                            <div class="{{ $isSpecialist ? 'space-y-2' : 'grid grid-cols-2 gap-3' }}">
                                @foreach ($question->answers as $a)
                                    @if ($isSpecialist)
                                        <label class="flex cursor-pointer items-start gap-3 rounded-xl border px-4 py-2.5 transition"
                                               :class="selected === {{ $a->id }}
                                                   ? 'border-brand-500 bg-brand-50 ring-2 ring-brand-200 dark:border-brand-400 dark:bg-brand-500/10 dark:ring-brand-500/30'
                                                   : 'border-slate-200 hover:border-brand-300 dark:border-slate-800 dark:hover:border-brand-500/40'">
                                            <input type="radio" name="answer_id" value="{{ $a->id }}" x-model.number="selected" class="sr-only">
                                            <span class="mt-0.5 grid h-6 w-6 shrink-0 place-items-center rounded-full text-xs font-bold transition"
                                                  :class="selected === {{ $a->id }} ? 'bg-brand-600 text-white' : 'bg-slate-200 text-slate-600 dark:bg-slate-700 dark:text-slate-300'">{{ $a->label }}</span>
                                            <span class="flex-1 text-sm text-slate-800 dark:text-slate-100">{{ $question->localizedAnswerText($a) }}</span>
                                        </label>
                                    @else
                                        <label class="flex cursor-pointer items-center justify-center rounded-xl border px-4 py-3.5 text-center text-lg font-bold transition"
                                               :class="selected === {{ $a->id }}
                                                   ? 'border-brand-600 bg-brand-600 text-white ring-2 ring-brand-300 dark:ring-brand-500/40'
                                                   : 'border-brand-200 bg-brand-50 text-brand-700 hover:bg-brand-100 dark:border-brand-500/30 dark:bg-brand-500/10 dark:text-brand-200 dark:hover:bg-brand-500/20'">
                                            <input type="radio" name="answer_id" value="{{ $a->id }}" x-model.number="selected" class="sr-only">
                                            {{ $question->localizedAnswerText($a) }}
                                        </label>
                                    @endif
                                @endforeach
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            {{-- ===== PRAWA: liczniki + czas u góry, przyciski NA WYSOKOŚCI pytania/odpowiedzi ===== --}}
            <aside class="flex h-full flex-col gap-3 lg:col-span-1">

                {{-- Liczniki podstawowe / specjalistyczne --}}
                <div class="card order-3 p-4 lg:order-1">
                    <div class="grid grid-cols-2 gap-3 text-center">
                        <div>
                            <p class="text-xs font-medium leading-tight text-slate-500 dark:text-slate-400">{{ __('session.exam.basic_counter') }}</p>
                            <p class="mt-1 font-display text-2xl font-bold tnum text-brand-600 dark:text-brand-400">{{ $basicAnswered }}/{{ $basicTotal }}</p>
                        </div>
                        <div>
                            <p class="text-xs font-medium leading-tight text-slate-500 dark:text-slate-400">{{ __('session.exam.specialist_counter') }}</p>
                            <p class="mt-1 font-display text-2xl font-bold tnum text-brand-600 dark:text-brand-400">{{ $specialistAnswered }}/{{ $specialistTotal }}</p>
                        </div>
                    </div>
                </div>

                {{-- Czas bieżącej fazy (zapoznanie → film → odpowiedź) --}}
                <div class="card order-1 p-4 lg:order-2">
                    <div class="flex items-center justify-between gap-2">
                        <p class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400" x-text="barLabel">{{ __('session.exam.answer_time_label') }}</p>
                        <span class="inline-flex items-center gap-1 text-sm font-semibold tnum"
                              :class="phase !== 'playing' && phaseTimeLeft <= 5 ? 'text-rose-500 dark:text-rose-400' : 'text-slate-500 dark:text-slate-400'">
                            <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6l4 2m6-2a10 10 0 1 1-20 0 10 10 0 0 1 20 0Z"/></svg>
                            <span x-show="phase !== 'playing'" x-text="phaseTimeLeft + 's'">–</span>
                            <span x-show="phase === 'playing'" x-cloak>{{ __('session.exam.playing') }}</span>
                        </span>
                    </div>
                    <div class="mt-2 h-2 w-full overflow-hidden rounded-full bg-slate-200 dark:bg-slate-700">
                        <div class="h-full rounded-full bg-brand-500 transition-all duration-1000 ease-linear" :style="`width: ${barPct}%`"></div>
                    </div>
                </div>

                {{-- Przyciski — na desktopie zepchnięte w dół (mt-auto), by były NA WYSOKOŚCI
                     pytania i odpowiedzi, nie przy licznikach. „Pokaż" znika po kliknięciu. --}}
                <div class="order-2 space-y-2 lg:order-3 lg:mt-auto">
                    @if ($hasMedia)
                        <button type="button" x-show="!mediaShown" x-on:click="start()" class="btn btn-outline w-full">
                            {{ $hasVideo ? __('session.exam.show_video') : __('session.exam.show_image') }}
                        </button>
                    @endif
                    <button type="button" x-on:click="submit()" class="btn {{ $isLast ? 'btn-dark' : 'btn-brand' }} w-full">
                        {{ $isLast ? __('session.exam.finish') : __('session.exam.next') }}
                    </button>
                </div>
            </aside>
        </div>

        {{-- Formularz zakończenia — używa go czerwony „Zakończ egzamin" i globalny timeout. --}}
        <form method="POST" action="{{ route('sessions.complete', $session) }}" id="finish-form" class="hidden">
            @csrf
        </form>
    </div>
</x-app-layout>
