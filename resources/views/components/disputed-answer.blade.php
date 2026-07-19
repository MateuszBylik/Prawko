@props(['question'])

@php
    // Klucz konfiguracji to numer pytania z arkusza MI. PHP rzutuje numeryczne
    // klucze tablicy na int, więc `config()` trafia tu tak samo dla '10898' i 10898.
    $disputed = config('prawko.disputed.'.$question->external_id);
@endphp

@if ($disputed)
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-500/40 dark:bg-amber-500/10']) }}>
        <div class="flex items-start gap-2.5">
            <svg class="mt-0.5 h-5 w-5 shrink-0 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z"/>
            </svg>

            <div class="min-w-0 space-y-2">
                <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                    {{ __('session.disputed.title') }}
                </p>

                {{-- Najpierw praktyka, potem wywód prawny: na egzaminie punktuje katalog
                     i to musi być pierwsze zdanie, jakie widać. --}}
                <p class="text-sm leading-relaxed text-slate-700 dark:text-slate-200">
                    {!! __('session.disputed.lead', ['answer' => e($disputed['catalog_answer'])]) !!}
                </p>

                <p class="text-sm leading-relaxed text-slate-600 dark:text-slate-300">
                    {{ $disputed['note'] }}
                </p>

                <p class="text-xs text-slate-500 dark:text-slate-400">
                    {{ __('session.disputed.basis', ['basis' => $disputed['basis']]) }}
                    @isset($disputed['petition'])
                        {{ $disputed['petition'] }}
                    @endisset
                </p>
            </div>
        </div>
    </div>
@endif
