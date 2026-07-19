@php
    $fmt = fn (float $v, int $d = 1) => number_format($v, $d, ',', ' ');
    $pct = fn (?float $v) => $v === null ? '—' : round($v * 100).'%';

    // Sedno ekranu: warstwa o najniższej skuteczności to zwykle NIE ta, która
    // kosztuje najwięcej — bo warstwy mają różną wagę punktową. Mówimy o tym
    // wprost, ale TYLKO gdy te procenty faktycznie się różnią. Przy wyrównanym
    // profilu (wszystkie warstwy ~90%) wniosek nie zachodzi, a zdanie „najsłabiej
    // idzie Ci 90%, a najwięcej tracisz na 90%" byłoby bełkotem.
    $worst = $breakdown->worstLayer();
    $weakest = collect($breakdown->layers)->sortBy('mean_p')->first();
    $procentMyli = $worst && $weakest
        && ($weakest['type'] !== $worst['type'] || $weakest['points'] !== $worst['points'])
        && $weakest['mean_p'] < $worst['mean_p'] - 0.03
        && $weakest['loss'] < $worst['loss'];
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('losses.title') }}</x-slot>
    <x-slot name="header">
        {{-- Jedyne wejście na ten ekran to karta prognozy na pulpicie (to jej rozwinięcie),
             więc powrót musi być tutaj — w navbarze go nie ma. --}}
        <div class="space-y-2">
            <a href="{{ route('dashboard') }}" class="btn btn-ghost btn-sm -ml-3">
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19l-7-7 7-7"/></svg>
                {{ __('losses.back') }}
            </a>
            <div>
                <h1 class="font-display text-xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('losses.title') }}</h1>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                    {{ __('losses.lead', ['code' => $category->code]) }}
                </p>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-4xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

        {{-- ===== Nagłówek: jedna liczba i dystans do progu ===== --}}
        <div class="card p-6">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:gap-8">
                <div class="shrink-0">
                    <div class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('losses.header.expected') }}</div>
                    {{-- Bez `tnum`: tabular-nums daje przecinkowi pełną szerokość cyfry, co przy
                         text-6xl wygląda jak dziura („67 ,8"). Cyfry i tak z niczym się tu nie
                         kolumnują — to pojedyncza liczba, nie wiersz tabeli. --}}
                    <div class="mt-1 font-display text-6xl font-bold leading-none text-slate-900 dark:text-white">
                        {{ $fmt($breakdown->expectedScore) }}<span class="text-3xl text-slate-400 dark:text-slate-500">/{{ $breakdown->maxScore }}</span>
                    </div>
                    @if ($breakdown->isShort())
                        <span class="badge badge-rose mt-3">{{ __('losses.header.short_by', ['n' => $fmt($breakdown->gap())]) }}</span>
                    @else
                        <span class="badge badge-emerald mt-3">{{ __('losses.header.surplus', ['n' => $fmt(-$breakdown->gap())]) }}</span>
                    @endif
                </div>

                <div class="min-w-0 flex-1">
                    @php
                        $pExp = min(100, max(0, $breakdown->expectedScore / max(1, $breakdown->maxScore) * 100));
                        $pThr = min(100, max(0, $breakdown->threshold / max(1, $breakdown->maxScore) * 100));
                    @endphp
                    <div class="flex items-baseline justify-between gap-3 text-sm">
                        <span class="font-medium text-slate-700 dark:text-slate-200">{{ __('losses.header.expected_loss') }}</span>
                        <span class="font-display font-bold tnum text-rose-500 dark:text-rose-400">−{{ $fmt($breakdown->totalLoss()) }} pkt</span>
                    </div>
                    <div class="relative mt-3 h-3 rounded-full bg-rose-100 dark:bg-rose-500/15">
                        <div class="absolute inset-y-0 left-0 rounded-full {{ $breakdown->isShort() ? 'bg-brand-500' : 'bg-emerald-500' }}"
                             style="width: {{ $pExp }}%"></div>
                        <div class="absolute -inset-y-1 w-px border-l border-dashed border-slate-500 dark:border-slate-400"
                             style="left: {{ $pThr }}%"></div>
                    </div>
                    <div class="mt-2 flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 tnum">
                        <span>{{ __('losses.header.earned_lost') }}</span>
                        <span>{{ __('dashboard.forecast.threshold', ['n' => $breakdown->threshold]) }}</span>
                    </div>
                    <p class="mt-3 text-xs text-slate-400 dark:text-slate-500">
                        {{ __('losses.header.same_numbers', ['coverage' => $forecast->coveragePercent()]) }}
                        @if ($forecast->coveragePercent() < 50)
                            <span class="text-amber-600 dark:text-amber-400">{{ __('losses.header.prior_warning') }}</span>
                        @endif
                    </p>
                </div>
            </div>
        </div>

        {{-- ===== Warstwy arkusza — rdzeń ekranu ===== --}}
        <div class="card overflow-hidden">
            <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('losses.layers.title') }}</h2>
                <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                    {{ __('losses.layers.lead', [
                        'questions' => collect($breakdown->layers)->sum('count'),
                        'layers' => count($breakdown->layers),
                    ]) }}
                </p>
            </div>

            <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                @foreach ($breakdown->layers as $i => $l)
                    @php
                        $share = $l['loss'] / $breakdown->maxLayerLoss() * 100;
                        $isWorst = $i === 0 && $l['loss'] > 0;
                    @endphp
                    <li class="px-5 py-4 {{ $isWorst ? 'bg-rose-50/50 dark:bg-rose-500/5' : '' }}">
                        <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                            <div class="flex flex-wrap items-center gap-1.5">
                                <span class="badge {{ $l['type']->value === 'basic' ? 'badge-brand' : 'badge-amber' }}">{{ $l['type']->label() }}</span>
                                <span class="badge badge-slate">{{ __('session.review.points', ['n' => $l['points']]) }}</span>
                                @if ($isWorst)
                                    <span class="badge badge-rose">{{ __('losses.layers.biggest') }}</span>
                                @endif
                            </div>
                            <span class="font-display text-sm font-bold tnum text-rose-500 dark:text-rose-400">−{{ $fmt($l['loss']) }} pkt</span>
                        </div>

                        <div class="mt-2.5 h-2 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div class="h-full rounded-full {{ $isWorst ? 'bg-rose-500' : 'bg-rose-300 dark:bg-rose-500/50' }}"
                                 style="width: {{ max(1, $share) }}%"></div>
                        </div>

                        <div class="mt-2 flex flex-wrap items-center gap-x-3 gap-y-0.5 text-xs text-slate-500 dark:text-slate-400 tnum">
                            <span>{{ __('losses.layers.on_sheet', ['n' => $l['count']]) }}</span>
                            <span class="text-slate-300 dark:text-slate-600">·</span>
                            <span>{{ __('losses.layers.pool_points', ['n' => $l['pool_points']]) }}</span>
                            <span class="text-slate-300 dark:text-slate-600">·</span>
                            <span>{{ __('losses.layers.chance', ['n' => $pct($l['mean_p'])]) }}</span>
                            <span class="text-slate-300 dark:text-slate-600">·</span>
                            <span>{{ __('losses.layers.coverage', ['n' => $pct($l['coverage'])]) }}</span>
                            @if ($l['hist_accuracy'] !== null)
                                <span class="text-slate-300 dark:text-slate-600">·</span>
                                <span>{{ __('losses.layers.your_accuracy', ['n' => $pct($l['hist_accuracy'])]) }}
                                    <span class="text-slate-400 dark:text-slate-500">{{ __('losses.layers.answers_count', ['n' => $l['hist_n']]) }}</span>
                                </span>
                            @endif
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>

        {{-- ===== Wniosek: procent myli ===== --}}
        @if ($procentMyli)
            <div class="card border-brand-200 bg-brand-50/50 p-5 dark:border-brand-500/30 dark:bg-brand-500/5">
                <h2 class="text-sm font-semibold text-brand-800 dark:text-brand-200">{{ __('losses.insight.title') }}</h2>
                <p class="mt-1.5 text-sm leading-relaxed text-slate-700 dark:text-slate-300">
                    {!! __('losses.insight.body', [
                        'weak' => e($weakest['type']->label().' '.__('losses.layers.pool_points', ['n' => $weakest['points']])),
                        'weakChance' => $pct($weakest['mean_p']),
                        'weakLoss' => $fmt($weakest['loss']),
                        'weakPoints' => $weakest['pool_points'],
                        'max' => $breakdown->maxScore,
                        'worst' => e($worst['type']->label().' '.__('losses.layers.pool_points', ['n' => $worst['points']])),
                        'worstLoss' => $fmt($worst['loss']),
                        'worstPoints' => $worst['pool_points'],
                    ]) !!}
                    @if ($breakdown->isShort() && $worst['loss'] >= $breakdown->gap())
                        {{ __('losses.insight.covers_gap') }}
                    @endif
                </p>
            </div>
        @endif

        {{-- ===== Pułapki ===== --}}
        @if (! empty($breakdown->traps))
            <div class="card overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('losses.traps.title') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        {{ __('losses.traps.lead') }}
                    </p>
                </div>
                <ul class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($breakdown->traps as $t)
                        @php $share = $t['loss'] / $breakdown->maxTrapLoss() * 100; @endphp
                        <li class="px-5 py-3.5">
                            <div class="flex flex-wrap items-center justify-between gap-x-3 gap-y-1">
                                <div class="flex flex-wrap items-center gap-2">
                                    <span class="badge {{ $t['trap']->badgeClass() }}">{{ $t['trap']->label() }}</span>
                                    <span class="text-xs text-slate-400 dark:text-slate-500 tnum">{{ __('losses.traps.questions', ['n' => $t['pool_size']]) }}</span>
                                </div>
                                <span class="text-sm font-semibold tnum text-slate-600 dark:text-slate-300">−{{ $fmt($t['loss']) }} pkt</span>
                            </div>
                            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                                <div class="h-full rounded-full bg-slate-400 dark:bg-slate-600" style="width: {{ max(1, $share) }}%"></div>
                            </div>
                            <p class="mt-1.5 text-xs text-slate-500 dark:text-slate-400">
                                {{ $t['trap']->hint() }}
                                @if ($t['hist_accuracy'] !== null)
                                    <span class="tnum text-slate-400 dark:text-slate-500">
                                        {{ __('losses.traps.accuracy', ['acc' => $pct($t['hist_accuracy']), 'n' => $t['hist_n']]) }}
                                    </span>
                                @endif
                            </p>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        {{-- ===== Kalibracja pewności ===== --}}
        @php $v = $calibration->verdict(); @endphp
        <div class="card overflow-hidden">
            <div class="flex flex-wrap items-start justify-between gap-3 border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                <div>
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('losses.calibration.title') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        {{ __('losses.calibration.lead') }}
                    </p>
                </div>
                <span class="badge badge-{{ $v['tone'] }} shrink-0">{{ $v['label'] }}</span>
            </div>

            <div class="p-5">
                <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">{{ $v['note'] }}</p>

                @if ($calibration->total() > 0)
                    {{-- Tabela 2×2. Wyróżniamy wyłącznie pola rozjazdu deklaracji z wynikiem. --}}
                    <div class="mt-4 grid grid-cols-2 gap-2 sm:gap-3">
                        @foreach ([
                            [__('losses.calibration.sure_right'), $calibration->sureCorrect, 'emerald', __('losses.calibration.sure_right_note')],
                            [__('losses.calibration.sure_wrong'), $calibration->sureWrong, 'rose', __('losses.calibration.sure_wrong_note')],
                            [__('losses.calibration.guess_right'), $calibration->guessCorrect, 'amber', __('losses.calibration.guess_right_note')],
                            [__('losses.calibration.guess_wrong'), $calibration->guessWrong, 'slate', __('losses.calibration.guess_wrong_note')],
                        ] as [$label, $count, $tone, $note])
                            <div class="rounded-xl border border-slate-200 px-3 py-3 dark:border-slate-800">
                                <div class="text-xs font-medium text-slate-500 dark:text-slate-400">{{ $label }}</div>
                                <div class="mt-1 font-display text-2xl font-bold tnum
                                    {{ [
                                        'emerald' => 'text-emerald-600 dark:text-emerald-400',
                                        'rose' => 'text-rose-500 dark:text-rose-400',
                                        'amber' => 'text-amber-600 dark:text-amber-400',
                                        'slate' => 'text-slate-500 dark:text-slate-400',
                                    ][$tone] }}">{{ $count }}</div>
                                <div class="mt-0.5 text-[11px] text-slate-400 dark:text-slate-500">{{ $note }}</div>
                            </div>
                        @endforeach
                    </div>

                    @if ($calibration->hasSignal())
                        <div class="mt-4 grid gap-3 border-t border-slate-100 pt-4 dark:border-slate-800 sm:grid-cols-2">
                            @if ($calibration->overconfidence() !== null)
                                <div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">{{ __('losses.calibration.overconfidence') }}</div>
                                    <div class="mt-0.5 font-display text-lg font-bold tnum text-slate-900 dark:text-white">
                                        {{ $pct($calibration->overconfidence()) }}
                                        <span class="text-xs font-normal text-slate-400">{{ __('losses.calibration.of_n', ['n' => $calibration->sureTotal()]) }}</span>
                                    </div>
                                </div>
                            @endif
                            @if ($calibration->guessAccuracy() !== null)
                                <div>
                                    <div class="text-xs text-slate-500 dark:text-slate-400">
                                        {{ __('losses.calibration.guess_vs_chance') }}
                                    </div>
                                    <div class="mt-0.5 font-display text-lg font-bold tnum text-slate-900 dark:text-white">
                                        {{ $pct($calibration->guessAccuracy()) }}
                                        <span class="text-xs font-normal text-slate-400">{{ __('losses.calibration.versus', ['n' => $pct($calibration->chanceLevel)]) }}</span>
                                        @php $lift = $calibration->guessLift(); @endphp
                                        <span class="text-xs font-semibold {{ $lift > 0.05 ? 'text-emerald-600 dark:text-emerald-400' : 'text-slate-400' }}">
                                            ({{ $lift >= 0 ? '+' : '−' }}{{ $pct(abs($lift)) }})
                                        </span>
                                    </div>
                                </div>
                            @endif
                        </div>
                    @endif
                @endif

                {{-- Pewne błędy — jedyna w pełni działająca konkluzja tej sekcji. --}}
                @if ($sureMistakes->isNotEmpty())
                    <div class="mt-5 border-t border-slate-100 pt-4 dark:border-slate-800">
                        <h3 class="text-sm font-semibold text-slate-900 dark:text-white">{{ __('losses.calibration.sure_mistakes_title') }}</h3>
                        <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                            {{ __('losses.calibration.sure_mistakes_lead') }}
                        </p>
                        <ul class="mt-3 space-y-1.5">
                            @foreach ($sureMistakes as $m)
                                <li>
                                    <a href="{{ route('questions.show', $m['question']) }}"
                                       class="flex items-start gap-3 rounded-lg border border-slate-200 px-3 py-2.5 transition hover:border-brand-300 hover:bg-slate-50 dark:border-slate-800 dark:hover:border-brand-500/40 dark:hover:bg-slate-800/50">
                                        <span class="min-w-0 flex-1 text-sm text-slate-700 dark:text-slate-200">{{ Str::limit($m['question']->text, 100) }}</span>
                                        @if ($m['times'] > 1)
                                            <span class="badge badge-rose shrink-0 tnum">{{ $m['times'] }}×</span>
                                        @endif
                                        <span class="shrink-0 text-xs text-slate-400 tnum">{{ __('session.review.points', ['n' => $m['question']->points]) }}</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            </div>
        </div>

        {{-- ===== Od czego zacząć ===== --}}
        @if ($riskQuestions->isNotEmpty())
            <div class="card overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('losses.start.title') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        {{ __('losses.start.lead') }}
                    </p>
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
                                <div class="text-[11px] text-slate-400">{{ __('losses.start.chance') }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- ===== Akcja ===== --}}
        <div class="flex flex-col gap-3 sm:flex-row">
            <form method="POST" action="{{ route('sessions.store') }}" class="sm:flex-1">
                @csrf
                <input type="hidden" name="mode" value="practice">
                <button type="submit" class="btn btn-brand w-full">{{ __('losses.actions.practice') }}</button>
            </form>
            <a href="{{ route('dashboard') }}" class="btn btn-outline sm:flex-1">{{ __('losses.actions.back') }}</a>
        </div>
    </div>
</x-app-layout>
