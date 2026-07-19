{{-- Stopka strony publicznej z danymi firmy (config/company.php — jedno źródło,
     przepisane ze stopki zalapto.pl). --}}
<footer class="border-t border-slate-200 bg-white dark:border-slate-800 dark:bg-slate-900">
    <div class="mx-auto max-w-6xl px-4 py-12 sm:px-6 lg:px-8">
        <div class="grid gap-8 sm:grid-cols-2 lg:grid-cols-3">

            {{-- Marka --}}
            <div>
                <div class="flex items-center gap-2.5">
                    <span class="grid h-8 w-8 place-items-center rounded-lg bg-brand-600 text-white">
                        <x-application-logo class="h-4 w-4" />
                    </span>
                    <span class="font-display text-base font-bold tracking-tight text-slate-900 dark:text-white">Prawko</span>
                </div>
                <p class="mt-3 max-w-xs text-sm leading-relaxed text-slate-500 dark:text-slate-400">
                    {{ __('landing.footer.tagline') }}
                </p>
                <p class="mt-4 text-xs text-slate-400 dark:text-slate-500">
                    {{ __('landing.footer.disclaimer') }}
                </p>
            </div>

            {{-- Kontakt --}}
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('landing.footer.contact') }}</h2>
                <ul class="mt-3 space-y-2 text-sm">
                    <li>
                        <a href="mailto:{{ config('company.email') }}"
                           class="text-brand-600 hover:text-brand-700 dark:text-brand-400 dark:hover:text-brand-300">
                            {{ config('company.email') }}
                        </a>
                    </li>
                    <li>
                        <a href="tel:{{ config('company.phone_tel') }}"
                           class="text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">
                            {{ config('company.phone') }}
                        </a>
                    </li>
                    <li class="text-slate-500 dark:text-slate-400">{{ config('company.hours') }}</li>
                    <li>
                        <a href="{{ config('company.website') }}" rel="noopener"
                           class="text-slate-600 hover:text-slate-900 dark:text-slate-300 dark:hover:text-white">
                            zalapto.pl
                        </a>
                    </li>
                </ul>
            </div>

            {{-- Dane firmy --}}
            <div>
                <h2 class="text-xs font-semibold uppercase tracking-wide text-slate-500 dark:text-slate-400">{{ __('landing.footer.company') }}</h2>
                {{-- <address> zamiast <div>: to jest adres podmiotu odpowiedzialnego za
                     stronę, czyli dokładnie to, do czego ten element służy. --}}
                <address class="mt-3 space-y-1 text-sm not-italic text-slate-600 dark:text-slate-300">
                    <div class="font-semibold text-slate-800 dark:text-slate-100">{{ config('company.legal_name') }}</div>
                    <div>{{ config('company.street') }}</div>
                    <div>{{ config('company.city') }}</div>
                </address>
                <dl class="mt-3 space-y-1 text-sm text-slate-500 dark:text-slate-400">
                    @foreach ([
                        'NIP' => config('company.nip'),
                        'REGON' => config('company.regon'),
                        'D-U-N-S®' => config('company.duns'),
                    ] as $label => $value)
                        <div class="flex gap-2">
                            <dt class="shrink-0">{{ $label }}:</dt>
                            <dd class="tnum text-slate-600 dark:text-slate-300">{{ $value }}</dd>
                        </div>
                    @endforeach
                </dl>
            </div>
        </div>

        <div class="mt-10 border-t border-slate-100 pt-6 dark:border-slate-800">
            <p class="text-center text-xs text-slate-400 dark:text-slate-600">
                {{ __('landing.footer.rights', ['year' => date('Y'), 'company' => config('company.name')]) }}
            </p>
        </div>
    </div>
</footer>
