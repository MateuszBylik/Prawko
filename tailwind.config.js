import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    // 'class' zamiast domyślnego 'media': bez tego warianty dark: słuchają wyłącznie
    // ustawienia systemu i użytkownik nie może wybrać motywu (patrz partials/theme-head).
    darkMode: 'class',

    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                // Body/UI — neutralny, świetnie czytelny grotesk z polskimi znakami.
                sans: ['Onest', ...defaultTheme.fontFamily.sans],
                // Nagłówki i duże liczby (np. „szansa zdania") — mocniejszy charakter.
                display: ['"Schibsted Grotesk"', 'Onest', ...defaultTheme.fontFamily.sans],
            },
            colors: {
                // Marka: „drogowy" kobalt. Semantykę egzaminu niosą kolory
                // sygnalizacji (emerald/amber/rose), świadomie osobne od akcentu.
                brand: {
                    50: '#eef4ff',
                    100: '#d9e6ff',
                    200: '#bcd3ff',
                    300: '#8db6ff',
                    400: '#578dfb',
                    500: '#3167ef',
                    600: '#1f4bdc',
                    700: '#1b3cb9',
                    800: '#1c3596',
                    900: '#1c3177',
                    950: '#151e48',
                },
            },
            boxShadow: {
                // Delikatny, „osadzony" cień — nie unoszący.
                card: '0 1px 2px 0 rgba(15, 23, 42, 0.04)',
                brand: '0 6px 20px -8px rgba(31, 75, 220, 0.5)',
            },
        },
    },

    plugins: [forms],
};
