# Prawko

![Wersja](https://img.shields.io/badge/wersja-1.3.1-1f4bdc)
![Laravel](https://img.shields.io/badge/Laravel-13-ff2d20)
![PHP](https://img.shields.io/badge/PHP-8.3%2B-777bb4)
![PWA](https://img.shields.io/badge/PWA-tak-5a0fc8)
![Licencja](https://img.shields.io/badge/licencja-zastrze%C5%BCona-lightgrey)

Aplikacja do nauki na **egzamin teoretyczny prawa jazdy**. Zamiast pokazywać
surowe „ile procent zrobiłeś", liczy realną **szansę zdania** i prowadzi kursanta
najkrótszą drogą do niej — powtórkami w odpowiednim momencie i pytaniami, na
których naprawdę traci punkty.

Działa produkcyjnie pod [prawojazdy.zalapto.pl](https://prawojazdy.zalapto.pl).

---

## Filozofia: niewidoczny silnik

Kursant widzi prosty pulpit i jeden wskaźnik — **szansę zdania**. Pod spodem
pracują trzy modele, których użytkownik nigdy nie musi rozumieć:

| Model | Rola |
| --- | --- |
| **FSRS-5** | pamięć: kiedy powtórzyć każde pytanie (stabilność + trudność) |
| **Rasch / IRT** | umiejętność kursanta (θ) i trudność pytań (b); `p = σ(θ − b)` |
| **Monte Carlo** | tysiące symulacji arkusza egzaminacyjnego → rozkład wyniku i szansa zdania |

Na tej podstawie aplikacja układa **kolejkę ryzyka** (pytania szeregowane wg
oczekiwanej straty punktów: udział na arkuszu × waga × `1 − p`) i **plan pod datę
egzaminu**. Pełne założenia produktu opisuje [`cel.md`](cel.md).

---

## Funkcje

- **Diagnoza** (40 pytań, bez presji czasu) — jednym przebiegiem szacuje θ (EAP) i
  ustawia pierwszy plan.
- **Codzienne ćwiczenia** — powtórki FSRS z domieszką nowych pytań, dawka pod datę
  egzaminu; osobny tryb **„tylko nowe"** do nadrabiania pokrycia puli.
- **Egzamin próbny 1:1 z WORD** — 32 pytania (20 podstawowych + 12
  specjalistycznych), limit 25 min, fazy czytanie → film → odpowiedź, próg 68/74.
- **Prognoza szansy zdania** w czasie (wykres) + ekran **„gdzie tracisz punkty"**
  rozbijający prognozę na warstwy strat.
- **Pary mylące** i **pułapki** egzaminacyjne (negacja / kwantyfikator / obowiązek…).
- **Wyjaśnienia błędów generowane AI** (Claude) — na żądanie, z zapisem w bazie.
- **Prompt w dniu egzaminu** („jak poszło?") i **trofea** zdanych kategorii.
- **PWA** (instalowalna, offline dla assetów), **tryb ciemny**, **i18n** PL/EN/DE/UK.
- **Panel administratora** użytkowników.

---

## Stack technologiczny

- **Backend:** PHP 8.3+ · Laravel 13 · Laravel Breeze (auth) · Sanctum
- **Baza:** MySQL / MariaDB (produkcja), SQLite in-memory (testy)
- **Frontend:** Blade · Tailwind CSS 3 · Alpine.js · Vite
- **Import puli:** `phpoffice/phpspreadsheet` (XLSX oficjalnej bazy pytań)
- **Media:** transkodowanie WMV → MP4 (H.264) do odtwarzania w przeglądarce
- **AI:** Anthropic Claude (wyjaśnienia błędów, wykrywanie różnic w parach mylących)
- **Bez Redis** — silnik i kolejki działają na sterowniku bazodanowym.

---

## Wymagania

- PHP **8.3+** (produkcja: 8.4) z rozszerzeniami wymaganymi przez Laravela
- Composer 2
- Node **20+** i npm
- MySQL/MariaDB (lub SQLite do lokalnego rozwoju)
- `ffmpeg` w `PATH` — tylko jeśli używasz `prawko:transcode-media`

---

## Instalacja

```bash
# 1. Zależności
composer install
npm install

# 2. Środowisko
cp .env.example .env
php artisan key:generate
#   ustaw DB_* oraz (opcjonalnie) ANTHROPIC_API_KEY dla wyjaśnień AI

# 3. Baza
php artisan migrate

# 4. Baza pytań (oficjalny arkusz XLSX)
php artisan prawko:import-questions

# 5. Assety
npm run build
```

Szybki start środowiska deweloperskiego (serwer + kolejka + logi + Vite w jednym):

```bash
composer dev
```

---

## Komendy `prawko:*`

| Komenda | Opis |
| --- | --- |
| `prawko:import-questions` | Importuje oficjalną bazę pytań z pliku XLSX do bazy danych. |
| `prawko:transcode-media` | Konwertuje wideo pytań z WMV na MP4 (H.264) do odtwarzania w przeglądarce. |
| `prawko:detect-twins` | Wykrywa pary mylące (podobne pytania) trigramami → `question_similars`. |
| `prawko:tag-traps` | Taguje pytania typami pułapek (negacja / kwantyfikator / obowiązek / warunek / liczba). |

---

## Architektura silnika

Logika domenowa mieszka w `app/Services/` — cienkie kontrolery, gruby silnik:

- **FsrsService**, **SpacedRepetitionService** — FSRS-5, terminy powtórek.
- **AbilityService**, **PCorrectService** — Rasch/IRT (θ, b) i `p(correct)`.
- **CalibrationService** — kalibracja deklaracji „wiem/strzelam".
- **ExamForecastService**, **ForecastHistoryService** — Monte Carlo + migawki wykresu.
- **RiskQueueService**, **LossBreakdownService** — kolejka ryzyka i rozbicie strat.
- **StudyPlanService** — dawka i plan pod datę egzaminu.
- **ExamService** — orkiestracja sesji (start / odpowiedź / domknięcie / wynik).
- **ExplanationService**, **TrapTagger**, **WalletService**, **KnowledgeService**,
  **EngineCache** — wyjaśnienia AI, pułapki, portfel kredytów, cache silnika.

Parametry egzaminu, diagnozy, FSRS i modelu czasowego trzymamy w
[`config/prawko.php`](config/prawko.php) (nie w kodzie), więc reguły stroi się bez
zmian w logice.

**Tryby sesji** (`App\Enums\ExamMode`):

| Tryb | Widok | Feedback | Limit czasu |
| --- | --- | --- | --- |
| `practice` (nauka) | `sessions/question` | natychmiast, + „wiem/strzelam" | miękki, per pytanie |
| `exam` (egzamin) | `sessions/exam-question` | dopiero wynik | 25 min, twardy |
| `diagnosis` (diagnoza) | `sessions/question` | dopiero wynik (szacuje θ) | brak |

---

## Testy

```bash
php artisan test          # (composer test robi wcześniej config:clear)
```

> ⚠️ **Ważne:** na produkcji konfiguracja jest zcache'owana (`config:cache`), co
> przesłania `phpunit.xml` i skierowałoby testy na **produkcyjną** bazę, którą
> `RefreshDatabase` by wyczyściło. Dlatego **zawsze** `php artisan config:clear`
> przed testami (skrypt `composer test` robi to za Ciebie; jest też bezpiecznik w
> `tests/TestCase.php`).

---

## Wdrożenie (produkcja)

- Zbuduj cache po każdej zmianie konfiguracji / tras / widoków:
  ```bash
  php artisan config:cache && php artisan route:cache && php artisan view:cache
  npm run build
  ```
- Artisan na serwerze uruchamiaj jako **`www-data`** (ownership logów i cache).
- Kolejka na sterowniku bazodanowym — po deployu `php artisan queue:restart`.
- `MAIL_MAILER=log` (poczta trafia do logu, nie wychodzi na zewnątrz).

---

## Wersjonowanie

Wersja jest stałą w [`config/app.php`](config/app.php) (`app.version`), pokazywaną
w stopce, a historia zmian mieszka w [`CHANGELOG.md`](CHANGELOG.md) (SemVer). Przy
wydaniu podbij `app.version`, dopisz wpis do CHANGELOG i przebuduj cache.

---

## Internacjonalizacja

Źródłem prawdy jest **polski** (`lang/pl`); `lang/{en,de,uk}` to tłumaczenia.
Parzystość kluczy pilnuje test pokrycia tłumaczeń. Treść pytań ma warianty
językowe (polski zawsze pierwszy na liście tłumaczeń).

---

## Licencja

Kod aplikacji jest własnością **ZałapTo (Mateusz Bylica)** — wszelkie prawa
zastrzeżone. Framework Laravel jest dostępny na licencji MIT.
