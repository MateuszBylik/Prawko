{{-- Ustawia motyw PRZED pierwszym malowaniem strony.

     Musi być inline i synchronicznie w <head>: gdyby leciało z app.js (defer),
     strona mignęłaby najpierw jasna, a dopiero potem przeskoczyła na ciemną.
     Zapis w localStorage, więc wybór jest per urządzenie — na telefonie możesz
     mieć inny motyw niż na komputerze.

     Uwaga na kolejność: ten skrypt stoi PRZED arkuszami stylów i to jest cały
     sens `color-scheme` ustawianego tutaj, a nie w CSS. Przy przechodzeniu między
     stronami przeglądarka maluje kanwę nowego dokumentu, ZANIM zdąży pobrać
     i zastosować arkusze — bez `color-scheme` domyślna kanwa jest BIAŁA i to ona
     mrugała w trybie ciemnym. Ustawione inline działa od pierwszej klatki i przy
     okazji nadaje ciemny wygląd paskom przewijania i kontrolkom formularzy.

     `data-cfasync="false"` NIE JEST ozdobnikiem — bez niego cały ten plik jest
     martwy. Cloudflare Rocket Loader przechwytuje skrypty, podmienia im `type`
     na własny (`<hash>-text/javascript`), przez co przeglądarka ich NIE wykonuje,
     i odpala je dopiero PO wyrenderowaniu strony. Skutek był dokładnie odwrotny
     do zamierzonego: strona malowała się jasna i dopiero potem przeskakiwała na
     ciemną. Skrypty Vite są modułami, więc Rocket Loader je omija — ten był jego
     jedyną ofiarą i akurat on musi wystartować pierwszy. Ten atrybut to
     udokumentowany sposób wypisania się (docs: speed/optimization/content/rocket-loader). --}}
<script data-cfasync="false">
    (function () {
        try {
            var t = localStorage.getItem('prawko-theme') || 'system';
            var dark = t === 'dark'
                || (t === 'system' && window.matchMedia('(prefers-color-scheme: dark)').matches);
            document.documentElement.classList.toggle('dark', dark);
            document.documentElement.style.colorScheme = dark ? 'dark' : 'light';
        } catch (e) {
            /* Prywatny tryb bez localStorage — zostaje domyślny motyw jasny. */
        }
    })();
</script>
