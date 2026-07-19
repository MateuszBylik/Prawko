@php
    use App\Support\LocaleLock;

    $locale = array_key_exists(app()->getLocale(), config('locales')) ? app()->getLocale() : 'pl';
    $locked = LocaleLock::locked(Auth::user());
@endphp

{{-- Wybór języka w menu profilu — ta sama forma co przełącznik motywu tuż wyżej,
     żeby dwa ustawienia obok siebie nie wyglądały jak dwa różne pomysły.

     Różnica jest w tym, GDZIE ląduje wybór: motyw w localStorage (cecha urządzenia
     i pory dnia), język na koncie (cecha osoby — kto czyta po ukraińsku, czyta tak
     samo wszędzie). --}}
<div {{ $attributes }}>
    <div class="mb-2 text-xs font-medium text-slate-400 dark:text-slate-500">{{ __('app.locale.label') }}</div>

    @if ($locked)
        {{-- Egzamin w toku zamraża język. Pokazujemy powód, zamiast dawać martwe
             przyciski — inaczej wyglądałoby to na awarię. Prawdziwą bramką jest
             LocaleController; to tylko interfejs. --}}
        <div class="rounded-lg bg-amber-50 px-3 py-2 dark:bg-amber-500/10">
            <div class="flex items-center gap-2 text-xs font-semibold text-amber-800 dark:text-amber-200">
                <x-flag :code="$locale" class="w-5" />
                {{ config('locales.'.$locale.'.native') }}
            </div>
            <p class="mt-1 text-[11px] leading-relaxed text-amber-700 dark:text-amber-300/90">
                {{ __('app.locale.locked_hint') }}
            </p>
        </div>
    @else
        {{-- Sam KOD języka, nie nazwa: menu profilu ma 12rem szerokości, a „Українська"
             i „Deutsch" po prostu się w nim nie mieszczą i były ucinane. Kod plus flaga
             wystarczą do rozpoznania, a pełna nazwa siedzi w `title` i w czytniku ekranu.
             Cztery pozycje mieszczą się w jednym rzędzie. --}}
        <div class="grid grid-cols-4 gap-1 rounded-lg bg-slate-100 p-1 dark:bg-slate-800">
            @foreach (config('locales') as $code => $meta)
                @php $active = $locale === $code; @endphp
                <a href="{{ route('locale.update', $code) }}"
                   lang="{{ $code }}" hreflang="{{ $code }}"
                   title="{{ $meta['native'] }}"
                   @if ($active) aria-current="true" @endif
                   @class([
                       'flex flex-col items-center gap-1 rounded-md px-1 py-1.5 text-[10px] font-semibold uppercase transition',
                       'bg-white text-brand-600 shadow-sm dark:bg-slate-700 dark:text-brand-300' => $active,
                       'text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-200' => ! $active,
                   ])>
                    <x-flag :code="$code" class="w-5" />
                    <span class="sr-only">{{ $meta['native'] }}</span>
                    <span aria-hidden="true">{{ $code }}</span>
                </a>
            @endforeach
        </div>
    @endif
</div>
