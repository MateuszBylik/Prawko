@php
    $locale = array_key_exists(app()->getLocale(), config('locales')) ? app()->getLocale() : 'pl';
    $current = config('locales.'.$locale);
@endphp

{{-- Przełącznik języka: flaga + kod, lista rozwijana z nazwami własnymi.
     Nazwy podajemy w JĘZYKU DOCELOWYM („Deutsch", nie „Niemiecki") — kto szuka
     swojego języka, szuka go tak, jak sam go nazywa, a nie po polsku.

     Pozycje to zwykłe <a>, więc działa bez JavaScriptu; Alpine odpowiada tylko za
     rozwijanie. Pokazujemy go WYŁĄCZNIE na stronie głównej — reszta aplikacji nie
     jest przetłumaczona (patrz config/locales.php). --}}
<div {{ $attributes->merge(['class' => 'relative']) }}
     x-data="{ open: false }" @click.outside="open = false" @keydown.escape.window="open = false">

    <button type="button" @click="open = ! open"
            class="flex items-center gap-1.5 rounded-lg px-2 py-1.5 text-sm font-medium text-slate-600
                   transition hover:bg-slate-100 hover:text-slate-900
                   dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white"
            :aria-expanded="open" aria-haspopup="true"
            aria-label="{{ __('landing.nav.language') }}">
        <x-flag :code="$locale" class="w-6" />
        <span class="text-xs font-semibold uppercase">{{ $locale }}</span>
        <svg class="h-3.5 w-3.5 text-slate-400 transition" :class="open && 'rotate-180'"
             fill="currentColor" viewBox="0 0 20 20">
            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
        </svg>
    </button>

    <div x-show="open" x-cloak
         x-transition:enter="transition ease-out duration-150"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute end-0 z-50 mt-1 w-44 origin-top-right overflow-hidden rounded-xl border border-slate-200
                bg-white py-1 shadow-lg dark:border-slate-700 dark:bg-slate-800">
        @foreach (config('locales') as $code => $meta)
            @php $active = app()->getLocale() === $code; @endphp
            <a href="{{ route('locale.update', $code) }}"
               lang="{{ $code }}" hreflang="{{ $code }}"
               @if ($active) aria-current="true" @endif
               @class([
                   'flex items-center gap-2.5 px-3 py-2 text-sm transition',
                   'bg-brand-50 font-semibold text-brand-700 dark:bg-brand-500/10 dark:text-brand-300' => $active,
                   'text-slate-700 hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700/60' => ! $active,
               ])>
                <x-flag :code="$code" />
                <span class="flex-1">{{ $meta['native'] }}</span>
                @if ($active)
                    <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2.4" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M4.5 12.75l6 6 9-13.5"/>
                    </svg>
                @endif
            </a>
        @endforeach
    </div>
</div>
