@php
    $num = fn ($n) => number_format((int) $n, 0, ',', ' ');

    // Skala wykresu: max z okna, minimum 1 — inaczej przy samych zerach dzielilibyśmy przez zero.
    $peak = max(1, max(array_column($activity, 'count')));
    $activeDays = count(array_filter($activity, fn ($d) => $d['count'] > 0));
@endphp

<x-app-layout>
    <x-slot name="title">{{ __('admin.dashboard.title') }}</x-slot>
    <x-slot name="header">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div>
                <h1 class="font-display text-xl font-bold tracking-tight text-slate-900 dark:text-white">{{ __('admin.dashboard.title') }}</h1>
                <p class="mt-0.5 text-sm text-slate-500 dark:text-slate-400">{{ __('admin.dashboard.subtitle') }}</p>
            </div>
            <div class="flex gap-2">
                <a href="{{ route('admin.users.index') }}" class="btn btn-outline btn-sm">{{ __('admin.dashboard.users_link') }}</a>
                <a href="{{ route('admin.users.create') }}" class="btn btn-brand btn-sm">{{ __('admin.dashboard.new_account') }}</a>
            </div>
        </div>
    </x-slot>

    <div class="mx-auto max-w-6xl space-y-6 px-4 py-6 sm:px-6 lg:px-8">

        {{-- ===== Kluczowe liczby ===== --}}
        <div class="grid grid-cols-2 gap-4 lg:grid-cols-4">
            @foreach ([
                [$num($users['total']), __('admin.tiles.accounts'), __('admin.tiles.accounts_note', ['students' => $users['students'], 'admins' => $users['admins']])],
                [$num($users['active_7d']), __('admin.tiles.active_7d'), __('admin.tiles.active_today', ['n' => $users['active_today']])],
                [$num($answers['total']), __('admin.tiles.answers'), __('admin.tiles.answers_week', ['n' => $num($answers['week'])])],
                [$num($sessions['completed']), __('admin.tiles.sessions'), __('admin.tiles.in_progress', ['n' => $sessions['in_progress']])],
            ] as [$value, $label, $note])
                <div class="card p-5">
                    <div class="font-display text-3xl font-bold leading-none tnum text-slate-900 dark:text-white">{{ $value }}</div>
                    <div class="mt-1.5 text-sm font-medium text-slate-700 dark:text-slate-200">{{ $label }}</div>
                    <p class="mt-1 text-xs text-slate-500 dark:text-slate-400 tnum">{{ $note }}</p>
                </div>
            @endforeach
        </div>

        {{-- ===== Aktywność ===== --}}
        <div class="card p-6">
            <div class="flex flex-wrap items-baseline justify-between gap-2">
                <div>
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('admin.activity.title') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">
                        {{ __('admin.activity.subtitle', ['days' => count($activity), 'active' => $activeDays]) }}
                    </p>
                </div>
                <span class="text-xs text-slate-400 dark:text-slate-500 tnum">{{ __('admin.activity.peak', ['n' => $num($peak)]) }}</span>
            </div>

            {{-- Jedna seria, więc bez legendy — nazywa ją nagłówek. Wartości NIE na każdym
                 słupku: etykietę dostaje tylko szczyt, reszta czyta się z proporcji.
                 Kolor niesie tu wielkość, nie tożsamość, więc jeden odcień marki. --}}
            <div class="mt-5 flex h-28 items-end gap-[2px]" role="img"
                 aria-label="{{ __('admin.activity.aria', ['days' => count($activity), 'peak' => $num($peak)]) }}">
                @foreach ($activity as $day)
                    @php
                        $h = $day['count'] / $peak * 100;
                        $isPeak = $day['count'] === $peak && $peak > 0;
                    @endphp
                    <div class="group relative flex flex-1 flex-col justify-end"
                         title="{{ $day['date']->translatedFormat('j M') }}: {{ $num($day['count']) }}">
                        @if ($isPeak)
                            <span class="mb-1 text-center text-[10px] font-semibold tabular-nums text-brand-600 dark:text-brand-400">
                                {{ $num($day['count']) }}
                            </span>
                        @endif
                        {{-- Zaokrąglony tylko górny koniec: słupek jest zakotwiczony w osi. --}}
                        <div class="w-full rounded-t {{ $day['count'] > 0 ? 'bg-brand-500 dark:bg-brand-500' : 'bg-slate-200 dark:bg-slate-800' }} transition group-hover:opacity-80"
                             style="height: {{ $day['count'] > 0 ? max(3, $h) : 3 }}%"></div>
                    </div>
                @endforeach
            </div>

            <div class="mt-2 flex justify-between border-t border-slate-100 pt-2 text-[11px] text-slate-400 dark:border-slate-800 dark:text-slate-500">
                <span>{{ $activity[0]['date']->translatedFormat('j M') }}</span>
                <span>{{ end($activity)['date']->translatedFormat('j M') }}</span>
            </div>

            {{-- Dostępny bliźniak wykresu — te same dane do odczytania liczbowo. --}}
            <details class="group mt-3">
                <summary class="inline-flex cursor-pointer items-center gap-1 text-xs font-medium text-slate-400 hover:text-slate-600 dark:hover:text-slate-300">
                    <svg class="h-3.5 w-3.5 transition group-open:rotate-90" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M9 5l7 7-7 7"/></svg>
                    {{ __('admin.activity.show_table') }}
                </summary>
                <div class="mt-2 overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="text-slate-500 dark:text-slate-400">
                            <tr><th class="py-1 pr-4 font-medium">{{ __('admin.activity.day') }}</th><th class="py-1 text-right font-medium">{{ __('admin.activity.answers') }}</th></tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach (array_reverse($activity) as $day)
                                <tr>
                                    <td class="py-1 pr-4 text-slate-600 dark:text-slate-300">{{ $day['date']->translatedFormat('j M Y') }}</td>
                                    <td class="py-1 text-right tnum text-slate-700 dark:text-slate-200">{{ $num($day['count']) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </details>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            {{-- ===== Baza pytań ===== --}}
            <div class="card overflow-hidden">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('admin.base.title') }}</h2>
                    <a href="{{ route('questions.index') }}"
                       class="text-xs font-semibold text-brand-600 hover:text-brand-700 dark:text-brand-400">{{ __('admin.base.open') }}</a>
                </div>
                <dl class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ([
                        [__('admin.base.total'), $num($base['total'])],
                        [__('admin.base.active'), $num($base['active'])],
                        [__('admin.base.video'), $num($base['video'])],
                        [__('admin.base.explained'), $num($base['explained'])],
                        [__('admin.base.disputed'), $num($base['disputed'])],
                        [__('admin.base.categories'), $num($base['categories'])],
                    ] as [$label, $value])
                        <div class="flex items-center justify-between px-5 py-2.5">
                            <dt class="text-sm text-slate-600 dark:text-slate-300">{{ $label }}</dt>
                            <dd class="text-sm font-semibold tnum text-slate-900 dark:text-white">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>

            {{-- ===== Egzaminy próbne ===== --}}
            <div class="card overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('admin.exams.title') }}</h2>
                    <p class="mt-0.5 text-xs text-slate-500 dark:text-slate-400">{{ __('admin.exams.subtitle') }}</p>
                </div>
                <div class="p-5">
                    @if ($sessions['exams_taken'] > 0)
                        <div class="flex items-baseline gap-2">
                            <span class="font-display text-4xl font-bold leading-none tnum text-slate-900 dark:text-white">{{ $sessions['pass_rate'] }}%</span>
                            <span class="text-sm text-slate-500 dark:text-slate-400">{{ __('admin.exams.passed') }}</span>
                        </div>
                        <div class="mt-3 h-2 overflow-hidden rounded-full bg-slate-100 dark:bg-slate-800">
                            <div class="h-full rounded-full bg-emerald-500" style="width: {{ $sessions['pass_rate'] }}%"></div>
                        </div>
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400 tnum">
                            {{ __('admin.exams.of_attempts', ['passed' => $num($sessions['exams_passed']), 'total' => $num($sessions['exams_taken'])]) }}
                        </p>
                    @else
                        <p class="text-sm text-slate-500 dark:text-slate-400">{{ __('admin.exams.none') }}</p>
                    @endif
                </div>
            </div>
        </div>

        {{-- ===== Ostatnie sesje ===== --}}
        @if ($recentSessions->isNotEmpty())
            <div class="card overflow-hidden">
                <div class="border-b border-slate-100 px-5 py-4 dark:border-slate-800">
                    <h2 class="text-base font-semibold text-slate-900 dark:text-white">{{ __('admin.recent.title') }}</h2>
                </div>
                <div class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach ($recentSessions as $s)
                        <div class="flex flex-wrap items-center gap-x-4 gap-y-1 px-5 py-3">
                            <div class="min-w-0 flex-1">
                                <div class="truncate text-sm font-medium text-slate-900 dark:text-white">{{ $s->user?->name ?? '—' }}</div>
                                <div class="truncate text-xs text-slate-500 dark:text-slate-400">{{ $s->user?->email }}</div>
                            </div>
                            <span class="badge {{ $s->isExam() ? 'badge-amber' : 'badge-slate' }}">{{ $s->mode->label() }}</span>
                            <span class="text-xs text-slate-400 tnum">{{ __('session.category', ['code' => $s->category?->code]) }}</span>
                            <div class="shrink-0 text-right">
                                <div class="text-sm font-semibold tnum text-slate-900 dark:text-white">{{ $s->scorePercent() }}%</div>
                                <div class="text-[11px] text-slate-400 tnum">{{ $s->completed_at?->diffForHumans() }}</div>
                            </div>
                            @if ($s->passed !== null)
                                <span class="badge {{ $s->passed ? 'badge-emerald' : 'badge-rose' }} shrink-0">
                                    {{ $s->passed ? __('admin.recent.passed') : __('admin.recent.failed') }}
                                </span>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
</x-app-layout>
