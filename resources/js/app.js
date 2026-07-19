

import Alpine from 'alpinejs';

window.Alpine = Alpine;

/**
 * Przełącznik motywu: jasny / ciemny / systemowy.
 *
 * Pierwsze ustawienie klasy `.dark` robi inline'owy skrypt w <head>
 * (partials/theme-head) — tu tylko reagujemy na zmiany użytkownika.
 * Wybór trzymamy w localStorage, więc jest per urządzenie.
 */
Alpine.data('themeSwitcher', () => ({
    theme: 'system',

    init() {
        this.theme = localStorage.getItem('prawko-theme') || 'system';

        // Gdy wybrano „systemowy", nadążaj za zmianą ustawień OS na żywo
        // (np. automatyczny tryb nocny) bez przeładowania strony.
        window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
            if (this.theme === 'system') this.apply();
        });
    },

    set(value) {
        this.theme = value;
        localStorage.setItem('prawko-theme', value);
        this.apply();
    },

    apply() {
        const dark =
            this.theme === 'dark' ||
            (this.theme === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);

        document.documentElement.classList.toggle('dark', dark);

        // MUSI iść w parze z klasą. `color-scheme` ustawia inline'owo skrypt z <head>
        // (patrz partials/theme-head.blade.php), a styl inline przebija arkusz — więc
        // gdyby przełącznik go tu nie nadpisał, przejście na jasny motyw zostawiłoby
        // ciemne paski przewijania, kontrolki i ciemną kanwę.
        document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
    },
}));

Alpine.start();

// PWA: rejestracja service workera (cache'uje tylko assety — patrz public/sw.js).
if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
        navigator.serviceWorker.register('/sw.js').catch(() => {
            // Brak SW to nie awaria — aplikacja działa normalnie, bez trybu offline.
        });
    });
}
