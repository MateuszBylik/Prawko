# Changelog

Wszystkie istotne zmiany w aplikacji **Prawko** (nauka do egzaminu na prawo jazdy).

Format oparty na [Keep a Changelog](https://keepachangelog.com/pl/1.1.0/),
wersjonowanie wg [SemVer](https://semver.org/lang/pl/).
Wersja jest trzymana w `config/app.php` (`app.version`) i pokazywana w stopce.

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
- Egzamin próbny 1:1 z PORD (32 pytania, limit czasu, próg zdania).
- Pary mylące i pułapki egzaminacyjne, plan nauki pod datę egzaminu.
- Wyjaśnienia błędów generowane AI (Claude) z zapisem w bazie.

### Pulpit i profil
- Pulpit z timerem dawki, wykresem szansy zdania w czasie, statystykami.
- Prompt w dniu egzaminu („jak poszło?") i trofea zdanych kategorii.

### Platforma
- Laravel 13, PWA (manifest, ikony, service worker), tryb ciemny.
- Wielojęzyczność: PL (źródło), EN, DE, UK.
- Panel administratora użytkowników.

[1.0.0]: https://github.com/MateuszBylik/Prawko/releases/tag/v1.0.0
