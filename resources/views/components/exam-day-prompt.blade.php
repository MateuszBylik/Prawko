@php
    // Świadomie bez JavaScriptu: to pytanie musi się pokazać, choćby Alpine nie
    // wstał. Nakładka jest statyczna, jedynym wyjściem są cztery akcje (każda robi
    // POST i przeładowuje stronę), a zmianę terminu chowa natywne <details>.
    $user = Auth::user();
@endphp

@if ($user?->hasExamDayPrompt())
    @php $cat = $user->drivingCategory; @endphp

    <div class="fixed inset-0 z-50 flex items-end justify-center p-4 sm:items-center"
         role="dialog" aria-modal="true" aria-labelledby="exam-day-title">
        <div class="absolute inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

        <div class="relative w-full max-w-md rounded-2xl bg-white p-6 shadow-2xl dark:bg-slate-900 dark:ring-1 dark:ring-white/10 sm:p-7">
            <div class="flex items-center gap-2">
                <span class="text-2xl" aria-hidden="true">🚗</span>
                @if ($cat)
                    <span class="badge badge-brand">{{ __('exam_day.category', ['code' => $cat->code]) }}</span>
                @endif
            </div>

            <h2 id="exam-day-title" class="mt-3 font-display text-xl font-bold tracking-tight text-slate-900 dark:text-white">
                {{ __('exam_day.title') }}
            </h2>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400">{{ __('exam_day.lead') }}</p>

            {{-- Główne akcje --}}
            <div class="mt-5 space-y-2.5">
                <form method="POST" action="{{ route('exam.day.respond') }}">
                    @csrf
                    <input type="hidden" name="outcome" value="passed">
                    <button type="submit" class="btn btn-brand w-full justify-center gap-2">
                        <svg class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="2.2" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/></svg>
                        {{ __('exam_day.passed') }}
                    </button>
                </form>

                <form method="POST" action="{{ route('exam.day.respond') }}">
                    @csrf
                    <input type="hidden" name="outcome" value="failed">
                    <button type="submit" class="btn btn-outline w-full justify-center">{{ __('exam_day.failed') }}</button>
                </form>
            </div>

            {{-- Drugorzędne — celowo mniej widoczne (drobny szary tekst) --}}
            <div class="mt-4 space-y-2 border-t border-slate-100 pt-4 text-center dark:border-slate-800">
                <form method="POST" action="{{ route('exam.day.respond') }}">
                    @csrf
                    <input type="hidden" name="outcome" value="later">
                    <button type="submit" class="text-xs text-slate-400 transition hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300">
                        {{ __('exam_day.later') }}
                    </button>
                </form>

                <details class="group">
                    <summary class="cursor-pointer list-none text-xs text-slate-400 transition hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300">
                        {{ __('exam_day.reschedule') }}
                    </summary>
                    <form method="POST" action="{{ route('exam.day.respond') }}" class="mt-3 flex items-end gap-2 text-left">
                        @csrf
                        <input type="hidden" name="outcome" value="reschedule">
                        <div class="flex-1">
                            <label for="exam_day_new_date" class="label">{{ __('exam_day.new_date') }}</label>
                            <input type="date" id="exam_day_new_date" name="exam_date" required
                                   class="field mt-1" min="{{ now()->addDay()->format('Y-m-d') }}">
                        </div>
                        <button type="submit" class="btn btn-brand btn-sm">{{ __('exam_day.save') }}</button>
                    </form>
                </details>
            </div>
        </div>
    </div>
@endif
