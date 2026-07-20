# Changelog

Wszystkie istotne zmiany w aplikacji **Prawko** (nauka do egzaminu na prawo jazdy).

Format oparty na [Keep a Changelog](https://keepachangelog.com/pl/1.1.0/),
wersjonowanie wg [SemVer](https://semver.org/lang/pl/).
Wersja jest trzymana w `config/app.php` (`app.version`) i pokazywana w stopce.

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

[1.2.1]: https://github.com/MateuszBylik/Prawko/releases/tag/v1.2.1
[1.2.0]: https://github.com/MateuszBylik/Prawko/releases/tag/v1.2.0
[1.1.0]: https://github.com/MateuszBylik/Prawko/releases/tag/v1.1.0
[1.0.0]: https://github.com/MateuszBylik/Prawko/releases/tag/v1.0.0
