@php
    use App\Support\Impersonation;

    $impersonator = Impersonation::active() ? Impersonation::impersonator() : null;
@endphp

@if ($impersonator)
    {{-- Pas ma być NIE do przeoczenia: admin patrzący na cudzy pulpit musi w każdej
         chwili wiedzieć, czyje dane widzi — inaczej weźmie je za własne. --}}
    {{-- Bez `sticky`: navbar poniżej już jest `sticky top-0`, więc oba przykleiłyby
         się do tej samej krawędzi i nachodziły na siebie. Stały uchwyt do wyjścia
         jest w menu profilu, ten pas to głośny sygnał na wejściu. --}}
    <div class="relative z-50 bg-amber-500 text-amber-950">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-between gap-2 px-4 py-2 sm:px-6 lg:px-8">
            <p class="flex items-center gap-2 text-sm font-semibold">
                <svg class="h-4 w-4 shrink-0" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z"/>
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z"/>
                </svg>
                <span>
                    {!! __('app.impersonation.banner', ['email' => '<strong>'.e(Auth::user()->email).'</strong>']) !!}
                    <span class="hidden font-normal sm:inline">{{ __('app.impersonation.read_only') }}</span>
                </span>
            </p>
            <form method="POST" action="{{ route('impersonate.stop') }}" class="shrink-0">
                @csrf
                <button type="submit"
                        class="rounded-lg bg-amber-950/90 px-3 py-1.5 text-xs font-semibold text-amber-50 transition hover:bg-amber-950">
                    {{ __('app.impersonation.exit') }}
                </button>
            </form>
        </div>
    </div>
@endif
