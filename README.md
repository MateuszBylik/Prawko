# Prawko

![Version](https://img.shields.io/badge/version-1.3.9-1f4bdc)
![Laravel](https://img.shields.io/badge/Laravel-13-ff2d20)
![PHP](https://img.shields.io/badge/PHP-8.3%2B-777bb4)
![PWA](https://img.shields.io/badge/PWA-yes-5a0fc8)

A study app for the Polish **driving licence theory exam**. Instead of showing a raw
"percentage completed", it estimates the learner's real **chance of passing** and
guides them along the shortest path to it — with reviews at the right moment and
the questions on which they actually lose points.

Running in production at [prawojazdy.zalapto.pl](https://prawojazdy.zalapto.pl).

---

## Philosophy: an invisible engine

The learner sees a simple dashboard and a single indicator — the **chance of
passing**. Underneath, three models are at work that the user never needs to
understand:

| Model | Role |
| --- | --- |
| **FSRS-5** | memory: when to review each question (stability + difficulty) |
| **Rasch / IRT** | learner ability (θ) and question difficulty (b); `p = σ(θ − b)` |
| **Monte Carlo** | thousands of simulated exam sheets → score distribution and chance of passing |

On this basis the app builds a **risk queue** (questions ranked by expected point
loss: share of the exam sheet × weight × `1 − p`) and a **plan for the exam date**.
The full product assumptions are described in [`cel.md`](cel.md) (in Polish).

---

## Features

- **Diagnosis** (40 questions, no time pressure) — estimates θ (EAP) in a single pass
  and sets up the first plan.
- **Daily practice** — FSRS reviews mixed with new questions, dosed to the exam date;
  a separate **"new only"** mode to catch up on coverage of the question pool.
- **Mock exam, 1:1 with the official exam** (as taken at WORD, the regional exam
  centres) — 32 questions (20 basic + 12 specialist), 25-minute limit,
  read → video → answer phases, pass mark 68/74.
- **Chance-of-passing forecast** over time (chart) + a **"where you lose points"**
  screen that breaks the forecast down into layers of losses.
- **Confusable pairs** and exam **traps** (negation / quantifier / obligation…).
- **AI-generated explanations of mistakes** (Claude) — on demand, stored in the database.
- **Exam-day prompt** ("how did it go?") and **trophies** for passed categories.
- **PWA** (installable, assets available offline), **dark mode**, **i18n** PL/EN/DE/UK.
- **Admin panel** for user management.

---

## Tech stack

- **Backend:** PHP 8.3+ · Laravel 13 · Laravel Breeze (auth) · Sanctum
- **Database:** MySQL / MariaDB (production), in-memory SQLite (tests)
- **Frontend:** Blade · Tailwind CSS 3 · Alpine.js · Vite
- **Question import:** `phpoffice/phpspreadsheet` (XLSX of the official question bank)
- **Media:** WMV → MP4 (H.264) transcoding for in-browser playback
- **AI:** Anthropic Claude (explanations of mistakes, spotting the difference in confusable pairs)
- **No Redis** — the engine and queues run on the database driver.

---

## Requirements

- PHP **8.3+** (production: 8.4) with the extensions required by Laravel
- Composer 2
- Node **20+** and npm
- MySQL/MariaDB (or SQLite for local development)
- `ffmpeg` in `PATH` — only if you use `prawko:transcode-media`

---

## Installation

```bash
# 1. Dependencies
composer install
npm install

# 2. Environment
cp .env.example .env
php artisan key:generate
#   set DB_* and (optionally) ANTHROPIC_API_KEY for AI explanations

# 3. Database
php artisan migrate

# 4. Question bank (official XLSX sheet)
php artisan prawko:import-questions

# 5. Assets
npm run build
```

Quick start for local development (server + queue + logs + Vite in one):

```bash
composer dev
```

---

## `prawko:*` commands

| Command | Description |
| --- | --- |
| `prawko:import-questions` | Imports the official question bank from an XLSX file into the database. |
| `prawko:transcode-media` | Converts question videos from WMV to MP4 (H.264) for in-browser playback. |
| `prawko:detect-twins` | Detects confusable pairs (similar questions) using trigrams → `question_similars`. |
| `prawko:tag-traps` | Tags questions with trap types (negation / quantifier / obligation / condition / number). |

---

## Engine architecture

Domain logic lives in `app/Services/` — thin controllers, fat engine:

- **FsrsService**, **SpacedRepetitionService** — FSRS-5, review scheduling.
- **AbilityService**, **PCorrectService** — Rasch/IRT (θ, b) and `p(correct)`.
- **CalibrationService** — calibration of "I know / I'm guessing" declarations.
- **ExamForecastService**, **ForecastHistoryService** — Monte Carlo + chart snapshots.
- **RiskQueueService**, **LossBreakdownService** — risk queue and loss breakdown.
- **StudyPlanService** — daily dose and plan for the exam date.
- **ExamService** — session orchestration (start / answer / close / result).
- **ExplanationService**, **TrapTagger**, **WalletService**, **KnowledgeService**,
  **EngineCache** — AI explanations, traps, credit wallet, engine cache.

Exam, diagnosis, FSRS and timing-model parameters live in
[`config/prawko.php`](config/prawko.php) (not in code), so the rules can be tuned
without changing the logic.

**Session modes** (`App\Enums\ExamMode`):

| Mode | View | Feedback | Time limit |
| --- | --- | --- | --- |
| `practice` | `sessions/question` | immediate, + "I know / I'm guessing" | soft, per question |
| `exam` | `sessions/exam-question` | only in the result | 25 min, hard |
| `diagnosis` | `sessions/question` | only in the result (estimates θ) | none |

---

## Tests

```bash
php artisan test          # (composer test runs config:clear first)
```

> ⚠️ **Important:** in production the configuration is cached (`config:cache`),
> which overrides `phpunit.xml` and would point the tests at the **production**
> database — which `RefreshDatabase` would then wipe. So **always** run
> `php artisan config:clear` before testing (the `composer test` script does it for
> you; there is also a safeguard in `tests/TestCase.php`).

---

## Deployment (production)

- Rebuild the caches after every change to config / routes / views:
  ```bash
  php artisan config:cache && php artisan route:cache && php artisan view:cache
  npm run build
  ```
- Run Artisan on the server as **`www-data`** (ownership of logs and cache).
- The queue runs on the database driver — run `php artisan queue:restart` after each deploy.
- `MAIL_MAILER=log` (mail goes to the log and is never sent out).

---

## Versioning

The version is a constant in [`config/app.php`](config/app.php) (`app.version`),
shown in the footer; the change history lives in [`CHANGELOG.md`](CHANGELOG.md)
(SemVer, in Polish). For a release, bump `app.version`, add a CHANGELOG entry and
rebuild the caches.

---

## Internationalisation

The source of truth is **Polish** (`lang/pl`); `lang/{en,de,uk}` are translations.
A translation-coverage test enforces key parity. Question content has language
variants (Polish always comes first in the list of translations).
