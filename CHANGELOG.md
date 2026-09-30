# Changelog

Wszystkie istotne zmiany w aplikacji **Prawko** (nauka do egzaminu na prawo jazdy).

Format oparty na [Keep a Changelog](https://keepachangelog.com/pl/1.1.0/),
wersjonowanie wg [SemVer](https://semver.org/lang/pl/).
Wersja jest trzymana w `config/app.php` (`app.version`) i pokazywana w stopce.

## [1.3.6] — 2026-09-30

### Bezpieczeństwo
- Usunięte `trustProxies(at: ['*'])`: aplikacja nie ufa już nagłówkom proxy
  (`X-Forwarded-For` i podobnym) od nikogo, więc nie da się podrobić IP klienta.
  Prawdziwy adres ustawia teraz nginx (`real_ip` z `CF-Connecting-IP`, tylko od
  adresów Cloudflare), a wejścia z pominięciem Cloudflare są blokowane.
  HTTPS (także adresy assetów Vite) wynika z `fastcgi_params` — sprawdzone, bez `http://`.

## [1.3.5] — 2026-08-21

### Zmienione
- Adres firmy w stopce i danych rejestrowych zmieniony na ul. Heroldów 10B/53,
  01-991 Warszawa (poprzednio al. 700-lecia Torunia 16/64, 87-100 Toruń).
  Zaktualizowany też test pilnujący danych rejestrowych w stopce.

## [1.3.4] — 2026-07-21

### Zmienione
- Przyciski TAK/NIE na egzaminie rozłożone z równymi odstępami: krawędź–TAK–NIE–
  krawędź (justify-evenly) zamiast dosunięcia do skrajnych krawędzi.

## [1.3.3] — 2026-07-21

### Zmienione
- Przyciski TAK/NIE na egzaminie: węższe (25% szerokości), ale wyjustowane do
  krawędzi — TAK z lewej, NIE z prawej, jak było wcześniej.

## [1.3.2] — 2026-07-21

### Naprawione
- Ikona odtwarzania filmu: zamiast półprzezroczystego kółka (łapało niebieski
  kadr filmu i wyglądało jak „niebieski owal") — solidne ciemne kółko z białym,
  wyśrodkowanym trójkątem.

### Zmienione
- Przyciski TAK/NIE na egzaminie są węższe (połowa dotychczasowej szerokości —
  siatka 4-kolumnowa), bliżej realnej wielkości w WORD.

## [1.3.1] — 2026-07-21

### Naprawione
- Egzamin: pytania **specjalistyczne** ze zdjęciem znów je pokazują (774 pytań
  miało foto, a widok je blokował warunkiem „nie pokazuj mediów specjalistycznych").
- Materiał na egzaminie jest w jednej ramce **16:9** (zdjęcia i filmy są 1024×576):
  zdjęcie wypełnia całą szerokość i nie kurczy się po kliknięciu „Pokaż zdjęcie";
  ramka „bez zdjęcia" również 16:9.
- Pod materiałem pokazujemy już **wyłącznie pytanie i odpowiedzi** — bez plakietki
  typu (Podstawowe/Specjalistyczne) i bez numeru pytania „x / 32".
- Ikona odtwarzania na filmie ma teraz wyśrodkowany trójkąt (osadzony w kółku).

### Zmienione
- Przyciski TAK/NIE na egzaminie są mniejsze — bliżej realnej wielkości w WORD.

## [1.3.0] — 2026-07-20

### Dodane
- Tryb ekspresowy — przełącznik w oknie „Plan na egzamin", dostępny w ostatnim
  tygodniu przed egzaminem. Doprowadza każde pytanie kategorii do stanu „utrwalone
  lub odpowiedziane poprawnie" (streak ≥ 1). Włączony zmienia dzienną dawkę na sesje
  z pytań jeszcze niespełnionych i chowa „Nowe pytania" (są jego podzbiorem);
  zapamiętywany na koncie.

### Zmienione
- Egzamin: wracać do przerwanej sesji („Wznów sesję") wolno tylko z zapasem
  > 10 min. Poniżej progu (albo po upływie 25 min) wejście domyka egzamin z obecnymi
  odpowiedziami i pokazuje wynik — nieudzielone pytania = 0 pkt (0% gdy nic nie
  ruszone). Banner pulpitu pokazuje wtedy „Zobacz wynik".
- Karta startu na pulpicie zawiera już tylko „Egzamin próbny" — naukę prowadzi plan.

## [1.2.1] — 2026-07-20

### Naprawione
- Poprawiona nazwa instytucji egzaminacyjnej w dokumentacji i komentarzach:
  błędny skrót „PORD" → „WORD" (Wojewódzki Ośrodek Ruchu Drogowego).

## [1.2.0] — 2026-07-20

### Zmienione
- Tryb egzaminu odwzorowany 1:1 z ekranem WORD: układ dwukolumnowy, górny pasek z
  czasem całego egzaminu (25 min), wartością punktową i kategorią, czerwony
  „Zakończ egzamin" w rogu, liczniki pytań podstawowych i specjalistycznych oraz
  pasek czasu na odpowiedź; przyciski na wysokości pytania i odpowiedzi.
- Odpowiedzi TAK/NIE obok siebie; materiał (film 16:9 / zdjęcie) wypełnia całą
  szerokość bez czarnych pasów; odpowiedzi i oba przyciski widoczne od razu,
  a „Pokaż" znika bez przeładowania strony.
- Czas pytania: czas na zapoznanie (po nim materiał startuje sam), pauza na czas
  filmu, potem 15 s na odpowiedź (po nim automatyczne przejście dalej); przy końcu
  czasu zaznaczona odpowiedź zostaje wybrana.
- Globalny limit 25 minut egzaminu, egzekwowany także po stronie serwera.

## [1.1.0] — 2026-07-20

### Zmienione
- Nauka i diagnoza: pytania podstawowe (TAK/NIE) wyświetlają się w dwóch kolumnach
  zamiast pod sobą — przy wyborze odpowiedzi i w widoku po odpowiedzi.
- Przyciski „wiem / strzelam / następne pytanie" przeniesione do prawej kolumny,
  pod tablicę pytań (na telefonie wskakują tuż pod pytanie).
- Tablica pytań w nauce oznacza błędne odpowiedzi na czerwono (poprawne zielono),
  a w diagnozie neutralnym błękitem, by nie zdradzać wyniku przed ekranem diagnozy.

### Dodane
- README.md z opisem aplikacji, architektury silnika i wdrożenia.

## [1.0.0] — 2026-07-20

Pierwsze oznaczone wydanie. Aplikacja działa produkcyjnie od 2026-07-16.

### Silnik wiedzy i prognoza
- Model FSRS-5 (stabilność, trudność, terminy powtórek) dla każdego pytania.
- Model Rascha/IRT (θ kursanta, trudność pytań) i symulacja Monte Carlo
  licząca realną **szansę zdania** wraz z przedziałem wyniku.
- Kolejka ryzyka — pytania szeregowane wg oczekiwanej straty punktów.
- Ekran „Gdzie tracisz punkty" rozbijający prognozę na warstwy strat.

### Nauka
- Diagnoza wstępna (40 pytań) wyznaczająca poziom i pierwszy plan.
- Codzienne ćwiczenia (powtórki + domieszka nowych) oraz tryb „tylko nowe".
- Egzamin próbny 1:1 z WORD (32 pytania, limit czasu, próg zdania).
- Pary mylące i pułapki egzaminacyjne, plan nauki pod datę egzaminu.
- Wyjaśnienia błędów generowane AI (Claude) z zapisem w bazie.

### Pulpit i profil
- Pulpit z timerem dawki, wykresem szansy zdania w czasie, statystykami.
- Prompt w dniu egzaminu („jak poszło?") i trofea zdanych kategorii.

### Platforma
- Laravel 13, PWA (manifest, ikony, service worker), tryb ciemny.
- Wielojęzyczność: PL (źródło), EN, DE, UK.
- Panel administratora użytkowników.

[1.3.4]: https://github.com/MateuszBylik/Prawko/releases/tag/v1.3.4
[1.3.3]: https://github.com/MateuszBylik/Prawko/releases/tag/v1.3.3
[1.3.2]: https://github.com/MateuszBylik/Prawko/releases/tag/v1.3.2
[1.3.1]: https://github.com/MateuszBylik/Prawko/releases/tag/v1.3.1
[1.3.0]: https://github.com/MateuszBylik/Prawko/releases/tag/v1.3.0
[1.2.1]: https://github.com/MateuszBylik/Prawko/releases/tag/v1.2.1
[1.2.0]: https://github.com/MateuszBylik/Prawko/releases/tag/v1.2.0
[1.1.0]: https://github.com/MateuszBylik/Prawko/releases/tag/v1.1.0
[1.0.0]: https://github.com/MateuszBylik/Prawko/releases/tag/v1.0.0
