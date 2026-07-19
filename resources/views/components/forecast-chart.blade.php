@props(['history', 'target' => 0.95])

@php
    /**
     * Wykres „szansa zdania w czasie" — inline SVG, bez bibliotek.
     *
     * Jedna seria, więc bez legendy (tytuł ją nazywa). Wartości są też w tabeli
     * pod wykresem — tooltip nigdy nie jest jedyną drogą do liczby.
     */
    $points = $history->map(fn ($s) => [
        'date' => $s->snapshot_date,
        'pct' => $s->passPercent(),
        'coverage' => (float) $s->coverage_percent,
    ])->values();

    $n = $points->count();
    $targetPct = (int) round($target * 100);

    // Układ współrzędnych SVG (viewBox); oś X pełni rolę czasu.
    $w = 640;
    $h = 200;
    $padL = 34;   // miejsce na etykiety osi Y
    $padR = 58;   // miejsce na etykietę końcówki (np. „67.2%") — za mały ucina znaki
    $padT = 16;   // miejsce na etykietę celu nad linią progu
    $padB = 26;   // pasmo osi X — musi się zmieścić, inaczej karta dostanie scroll

    $plotW = $w - $padL - $padR;
    $plotH = $h - $padT - $padB;

    // Skala Y: zawsze pełne 0–100%, żeby wzrost nie był wyolbrzymiony zoomem.
    $x = fn (int $i) => $padL + ($n <= 1 ? $plotW / 2 : $i * $plotW / ($n - 1));
    $y = fn (float $pct) => $padT + $plotH - ($pct / 100 * $plotH);

    $line = $points->map(fn ($p, $i) => ($i === 0 ? 'M' : 'L').round($x($i), 2).' '.round($y($p['pct']), 2))->implode(' ');

    // Wypełnienie pod linią — domknięte do podstawy wykresu.
    $area = $n > 1
        ? $line.' L'.round($x($n - 1), 2).' '.round($y(0), 2).' L'.round($x(0), 2).' '.round($y(0), 2).' Z'
        : '';

    $last = $points->last();
    $first = $points->first();
    $delta = $n > 1 ? round($last['pct'] - $first['pct'], 1) : null;
@endphp

<div class="card p-5 sm:p-6">
    <div class="flex flex-wrap items-start justify-between gap-3">
        <div>
            <h2 class="font-display text-base font-bold tracking-tight text-slate-900 dark:text-white">
                {{ __('dashboard.chart.over_time') }}
            </h2>
            <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">
                @if ($n > 1)
                    {{ trans_choice('dashboard.chart.measurements', $n, ['n' => $n, 'date' => $first['date']->translatedFormat('j M')]) }}
                @else
                    {{ __('dashboard.chart.growing') }}
                @endif
            </p>
        </div>

        @if ($delta !== null)
            @php $up = $delta >= 0; @endphp
            <span class="badge {{ $up ? 'badge-emerald' : 'badge-rose' }} shrink-0">
                {{ $up ? '↑' : '↓' }} {{ number_format(abs($delta), 1, ',', ' ') }} {{ __('dashboard.chart.pp') }}
            </span>
        @endif
    </div>

    @if ($n === 0)
        <p class="mt-4 text-sm text-slate-500 dark:text-slate-400">
            {{ __('dashboard.chart.empty') }}
        </p>
    @else
        <div class="mt-4 overflow-x-auto">
            {{-- Kolor marki podajemy RAZ, na <svg>, i wszędzie niżej bierzemy go przez
                 currentColor — dzięki temu linia, punkty i gradient reagują na motyw
                 jednym przełącznikiem (zaszyty #1f4bdc w gradiencie ginął w dark mode). --}}
            <svg viewBox="0 0 {{ $w }} {{ $h }}" class="h-48 w-full min-w-[20rem] text-brand-600 dark:text-brand-400"
                 role="img" aria-label="{{ __('dashboard.chart.aria') }}">
                <defs>
                    <linearGradient id="fc-fill" x1="0" y1="0" x2="0" y2="1">
                        <stop offset="0%" stop-color="currentColor" stop-opacity="0.24" />
                        <stop offset="100%" stop-color="currentColor" stop-opacity="0.02" />
                    </linearGradient>
                </defs>

                {{-- Siatka: pełne hairline'y, o ton od tła (nigdy kreskowane) --}}
                @foreach ([0, 25, 50, 75, 100] as $tick)
                    <line x1="{{ $padL }}" y1="{{ $y($tick) }}" x2="{{ $w - $padR }}" y2="{{ $y($tick) }}"
                          class="stroke-slate-200 dark:stroke-slate-800" stroke-width="1" />
                    <text x="{{ $padL - 8 }}" y="{{ $y($tick) + 3.5 }}" text-anchor="end"
                          class="fill-slate-400 text-[10px] tnum dark:fill-slate-500">{{ $tick }}</text>
                @endforeach

                {{-- Cel: kreskowany ŚWIADOMIE — to próg, a nie element siatki.
                     Etykieta NAD linią po lewej: przy prawej krawędzi zderzałaby się
                     z etykietą końcówki, gdy szansa dobije w okolice celu. --}}
                <line x1="{{ $padL }}" y1="{{ $y($targetPct) }}" x2="{{ $w - $padR }}" y2="{{ $y($targetPct) }}"
                      class="stroke-emerald-500/70" stroke-width="1.5" stroke-dasharray="5 4" />
                <text x="{{ $padL + 2 }}" y="{{ $y($targetPct) - 5 }}"
                      class="fill-emerald-600 text-[10px] font-semibold dark:fill-emerald-400">{{ __('dashboard.chart.target') }} {{ $targetPct }}%</text>

                @if ($n > 1)
                    <path d="{{ $area }}" fill="url(#fc-fill)" />
                    <path d="{{ $line }}" fill="none" stroke="currentColor" stroke-width="2"
                          stroke-linecap="round" stroke-linejoin="round" />
                @endif

                {{-- Punkty: 2px pierścień w kolorze powierzchni zamiast obrysu.
                     <title> daje natywny dymek i czyta się w czytniku ekranu. --}}
                @foreach ($points as $i => $p)
                    <circle cx="{{ $x($i) }}" cy="{{ $y($p['pct']) }}" r="{{ $i === $n - 1 ? 4.5 : 3 }}"
                            fill="currentColor" stroke-width="2"
                            class="stroke-white dark:stroke-slate-900">
                        <title>{{ $p['date']->translatedFormat('j M Y') }}: {{ $p['pct'] }}%</title>
                    </circle>
                @endforeach

                {{-- Etykieta tylko na końcówce — nigdy liczba przy każdym punkcie --}}
                <text x="{{ $x($n - 1) + 9 }}" y="{{ $y($last['pct']) + 4 }}"
                      class="fill-slate-900 text-xs font-bold dark:fill-white">{{ $last['pct'] }}%</text>

                {{-- Oś X: tylko skrajne daty, żeby nie zlewały się na telefonie --}}
                <text x="{{ $padL }}" y="{{ $h - 8 }}" class="fill-slate-400 text-[10px] dark:fill-slate-500">
                    {{ $first['date']->translatedFormat('j M') }}
                </text>
                @if ($n > 1)
                    <text x="{{ $w - $padR }}" y="{{ $h - 8 }}" text-anchor="end"
                          class="fill-slate-400 text-[10px] dark:fill-slate-500">
                        {{ $last['date']->translatedFormat('j M') }}
                    </text>
                @endif
            </svg>
        </div>

        {{-- Dostępny odpowiednik wykresu — tooltip/kolor nigdy nie są jedyną drogą do danych --}}
        <details class="mt-3 group">
            <summary class="cursor-pointer text-xs font-medium text-slate-500 hover:text-slate-700
                            dark:text-slate-400 dark:hover:text-slate-200">
                {{ __('dashboard.chart.table') }}
            </summary>
            <div class="mt-2 max-h-56 overflow-y-auto">
                <table class="w-full text-left text-xs">
                    <thead class="sticky top-0 bg-white text-slate-500 dark:bg-slate-900 dark:text-slate-400">
                        <tr>
                            <th class="py-1.5 font-semibold">{{ __('dashboard.chart.date') }}</th>
                            <th class="py-1.5 text-right font-semibold">{{ __('dashboard.chart.chance') }}</th>
                            <th class="py-1.5 text-right font-semibold">{{ __('dashboard.chart.coverage') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach ($points->reverse() as $p)
                            <tr>
                                <td class="py-1.5 text-slate-600 dark:text-slate-300">{{ $p['date']->translatedFormat('j M Y') }}</td>
                                <td class="py-1.5 text-right font-semibold tnum text-slate-900 dark:text-white">{{ $p['pct'] }}%</td>
                                <td class="py-1.5 text-right tnum text-slate-500 dark:text-slate-400">{{ round($p['coverage']) }}%</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </details>
    @endif
</div>
