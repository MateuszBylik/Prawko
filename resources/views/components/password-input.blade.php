@props([
    'id' => 'password',
    'name' => 'password',
    'autocomplete' => 'current-password',
])

{{-- Pole hasła z „oczkiem" do podglądu.
     Bez migania i bez zależności od globalnego [x-cloak]:
       • input ma STATYCZNY type="password" (zamaskowane, zanim wstanie Alpine),
         a Alpine tylko NADPISUJE go przez x-bind:type;
       • ikona „ukryj" startuje z inline display:none, więc do czasu inicjalizacji
         Alpine widać wyłącznie ikonę „pokaż". --}}
<div x-data="{ show: false }" class="relative">
    <input type="password"
           x-bind:type="show ? 'text' : 'password'"
           id="{{ $id }}"
           name="{{ $name }}"
           autocomplete="{{ $autocomplete }}"
           {{ $attributes->merge(['class' => 'field pr-11']) }}>

    <button type="button" tabindex="-1"
            x-on:click="show = ! show"
            x-bind:aria-label="show ? @js(__('auth_ui.reveal.hide')) : @js(__('auth_ui.reveal.show'))"
            class="absolute inset-y-0 right-0 flex items-center px-3 text-slate-400 transition hover:text-slate-600 dark:text-slate-500 dark:hover:text-slate-300">
        {{-- Pokaż (oko) --}}
        <svg x-show="! show" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
        </svg>
        {{-- Ukryj (oko przekreślone) — ukryte inline do wczytania Alpine --}}
        <svg x-show="show" style="display:none" class="h-5 w-5" fill="none" stroke="currentColor" stroke-width="1.7" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.45 10.45 0 0 1 12 4.5c4.756 0 8.774 3.162 10.066 7.498a10.523 10.523 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.243 4.243L9.879 9.879"/>
        </svg>
    </button>
</div>
