@php
    /*
     * Linki nawigacji budujemy RAZ, w jednej kolejności dla obu pasków (desktop
     * i mobile). Wcześniej każdy z nich składał listę osobno i pulpit admina lądował
     * za „Bazą pytań" — bo doklejał się w bloku `if isAdmin`, a nie na swoim miejscu.
     *
     * Pulpit jest zawsze pierwszy: to strona domowa roli. Dla kursanta to nauka,
     * dla admina panel — ale pozycja w pasku ta sama, bo pełni tę samą funkcję.
     */
    $isAdmin = Auth::user()->isAdmin();

    $homeRoute = $isAdmin ? 'admin.dashboard' : 'dashboard';
    $homeActive = $isAdmin ? request()->routeIs('admin.dashboard') : request()->routeIs('dashboard');

    $navLinks = [
        ['label' => __('app.nav.dashboard'), 'route' => $homeRoute, 'active' => $homeActive,
         'icon' => 'M3 10.5 12 3l9 7.5M5.25 9.75V21h13.5V9.75'],
        ['label' => __('app.nav.questions'), 'mobile' => __('app.nav.questions_short'), 'route' => 'questions.index', 'active' => request()->routeIs('questions.*'),
         'icon' => 'M12 6.75A2.25 2.25 0 1 1 12 2.25a2.25 2.25 0 0 1 0 4.5ZM4.5 21v-1.5a4.5 4.5 0 0 1 4.5-4.5M9 12.75h6M9 15.75h4.5'],
    ];

    if ($isAdmin) {
        $navLinks[] = ['label' => __('app.nav.users'), 'mobile' => __('app.nav.users_short'), 'route' => 'admin.users.index',
                       'active' => request()->routeIs('admin.users.*'),
                       'icon' => 'M18 18.72a9.1 9.1 0 0 0 3.74-.78 3 3 0 0 0-4.68-3.66M18 18.72V14.5M6 18.72a9.1 9.1 0 0 1-3.74-.78 3 3 0 0 1 4.68-3.66M6 18.72V14.5M15 8.25a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z'];
    }
@endphp

{{-- ===================== Górny pasek ===================== --}}
<nav class="sticky top-0 z-40 border-b border-slate-200 bg-white/85 backdrop-blur
            dark:border-slate-800 dark:bg-slate-900/85">
    <div class="mx-auto flex h-16 max-w-6xl items-center justify-between px-4 sm:px-6 lg:px-8">

        {{-- Marka + linki (desktop) --}}
        <div class="flex items-center gap-8">
            {{-- Logo prowadzi do „strony domowej" roli: kursant ma pulpit nauki, admin
                 swój panel. Dla admina route('dashboard') byłoby linkiem donikąd. --}}
            <a href="{{ route($homeRoute) }}" class="flex items-center gap-2.5">
                <span class="grid h-9 w-9 place-items-center rounded-xl bg-brand-600 text-white shadow-brand">
                    <x-application-logo class="h-5 w-5" />
                </span>
                <span class="text-lg font-bold tracking-tight text-slate-900 dark:text-white">Prawko</span>
            </a>

            <div class="hidden items-center gap-6 sm:flex">
                @foreach ($navLinks as $link)
                    <x-nav-link :href="route($link['route'])" :active="$link['active']">
                        {{ $link['label'] }}
                    </x-nav-link>
                @endforeach
            </div>
        </div>

        {{-- Menu użytkownika (wszystkie rozmiary) --}}
        <x-dropdown align="right" width="48">
            <x-slot name="trigger">
                <button class="flex items-center gap-2 rounded-xl px-2 py-1.5 text-sm font-medium
                               text-slate-600 hover:bg-slate-100 hover:text-slate-900 focus:outline-none
                               dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-white">
                    <span class="grid h-8 w-8 place-items-center rounded-full bg-brand-100 text-sm font-bold text-brand-700
                                 dark:bg-brand-500/15 dark:text-brand-300">
                        {{ mb_strtoupper(mb_substr(Auth::user()->name, 0, 1)) }}
                    </span>
                    <span class="hidden max-w-[9rem] truncate sm:block">{{ Auth::user()->name }}</span>
                    <svg class="h-4 w-4 text-slate-400" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                    </svg>
                </button>
            </x-slot>

            <x-slot name="content">
                <div class="px-4 py-2 text-xs text-slate-400 dark:text-slate-500">
                    {{ Auth::user()->email }}
                </div>
                <x-dropdown-link :href="route('profile.edit')">{{ __('app.nav.profile') }}</x-dropdown-link>

                {{-- Przełącznik motywu (wybór zapisany per urządzenie) --}}
                <x-theme-switcher class="border-t border-slate-100 px-4 py-3 dark:border-slate-800" />

                {{-- Język (wybór zapisany na koncie — inaczej niż motyw, który jest
                     per urządzenie). W trakcie egzaminu zablokowany: patrz LocaleLock. --}}
                <x-locale-choice class="border-t border-slate-100 px-4 py-3 dark:border-slate-800" />
                {{-- W podglądzie cudzego konta wylogowanie nie jest tym, czego chcesz —
                     wyrzuciłoby Cię z aplikacji zamiast wrócić na konto administratora. --}}
                @if (\App\Support\Impersonation::active())
                    <form method="POST" action="{{ route('impersonate.stop') }}">
                        @csrf
                        <x-dropdown-link :href="route('impersonate.stop')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                            {{ __('app.nav.exit_preview') }}
                        </x-dropdown-link>
                    </form>
                @else
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')"
                            onclick="event.preventDefault(); this.closest('form').submit();">
                            {{ __('app.nav.logout') }}
                        </x-dropdown-link>
                    </form>
                @endif
            </x-slot>
        </x-dropdown>
    </div>
</nav>

{{-- ===================== Dolna nawigacja (mobile) ===================== --}}
@php
    // Te same linki co na desktopie, w tej samej kolejności — plus profil na końcu
    // (na desktopie siedzi w menu pod awatarem, na telefonie musi być zakładką).
    $tabs = $navLinks;
    $tabs[] = ['label' => __('app.nav.profile'), 'route' => 'profile.edit', 'active' => request()->routeIs('profile.*'),
               'icon'  => 'M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.5 20.25a7.5 7.5 0 0 1 15 0'];
@endphp
<nav class="fixed inset-x-0 bottom-0 z-40 border-t border-slate-200 bg-white/95 backdrop-blur
            dark:border-slate-800 dark:bg-slate-900/95 sm:hidden"
     style="padding-bottom: env(safe-area-inset-bottom);">
    {{-- Liczba zakładek zależy od roli (kursant 3, admin 4), więc siatkę wyliczamy. --}}
    <div class="grid" style="grid-template-columns: repeat({{ max(1, count($tabs)) }}, minmax(0, 1fr));">
        @foreach ($tabs as $tab)
            <a href="{{ route($tab['route']) }}"
               class="flex flex-col items-center gap-1 py-2.5 text-xs font-medium transition
                      {{ $tab['active'] ? 'text-brand-600 dark:text-brand-400' : 'text-slate-500 dark:text-slate-400' }}">
                <svg class="h-6 w-6" fill="none" stroke="currentColor" stroke-width="1.7"
                     stroke-linecap="round" stroke-linejoin="round" viewBox="0 0 24 24">
                    <path d="{{ $tab['icon'] }}" />
                </svg>
                {{-- Krótsza etykieta na wąskim pasku, gdy podana („Baza pytań" → „Pytania"). --}}
                {{ $tab['mobile'] ?? $tab['label'] }}
            </a>
        @endforeach
    </div>
</nav>
