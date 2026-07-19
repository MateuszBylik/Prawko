@props(['compact' => false])

{{-- Przełącznik motywu. Wybór trzyma localStorage, więc jest per urządzenie —
     na telefonie możesz mieć inny motyw niż na komputerze (patrz app.js →
     Alpine.data('themeSwitcher') i partials/theme-head.blade.php).

     Wariant `compact` (ikony bez etykiet) mieści się w pasku strony głównej;
     pełny wariant siedzi w menu profilu. --}}
<div x-data="themeSwitcher" {{ $attributes }}>
    @unless ($compact)
        <div class="mb-2 text-xs font-medium text-slate-400 dark:text-slate-500">{{ __('app.theme.label') }}</div>
    @endunless

    <div class="grid grid-cols-3 gap-1 rounded-lg bg-slate-100 p-1 dark:bg-slate-800">
        @foreach ([
            ['light', __('app.theme.light'), 'M12 3v2.25m6.364.386-1.591 1.591M21 12h-2.25m-.386 6.364-1.591-1.591M12 18.75V21m-4.773-4.227-1.591 1.591M5.25 12H3m4.227-4.773L5.636 5.636M15.75 12a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z'],
            ['dark', __('app.theme.dark'), 'M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z'],
            ['system', __('app.theme.system'), 'M9 17.25v1.007a3 3 0 0 1-.879 2.122L7.5 21h9l-.621-.621A3 3 0 0 1 15 18.257V17.25m6-12V15a2.25 2.25 0 0 1-2.25 2.25H5.25A2.25 2.25 0 0 1 3 15V5.25A2.25 2.25 0 0 1 5.25 3h13.5A2.25 2.25 0 0 1 21 5.25Z'],
        ] as [$value, $label, $icon])
            <button type="button" @click="set('{{ $value }}')"
                    :class="theme === '{{ $value }}'
                        ? 'bg-white text-brand-600 shadow-sm dark:bg-slate-700 dark:text-brand-300'
                        : 'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200'"
                    class="flex items-center justify-center rounded-md transition
                           {{ $compact ? 'h-7 w-7' : 'flex-col gap-1 px-1 py-1.5 text-[10px] font-medium' }}"
                    :aria-pressed="theme === '{{ $value }}'"
                    @if ($compact) aria-label="{{ $label }}" title="{{ $label }}" @endif>
                <svg class="h-4 w-4" fill="none" stroke="currentColor" stroke-width="1.6"
                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="{{ $icon }}" />
                </svg>
                @unless ($compact){{ $label }}@endunless
            </button>
        @endforeach
    </div>
</div>
