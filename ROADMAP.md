# Roadmap

What is left to build in Prawko, as of October 2026 (v1.4). The core engine is
complete: FSRS-5 memory model, Rasch/IRT ability estimate, Monte Carlo forecast,
risk queue, diagnosis, 1:1 mock exam, confusable pairs, trap taxonomy and the
exam-date plan. The work below builds on that.

Estimates are hours of focused work for one developer and include tests and
documentation for the task. **Total: ~97 h.**

| # | Area | Estimate |
| --- | --- | --- |
| 1 | [Interactive intersection simulator](#1-interactive-intersection-simulator) | 34 h |
| 2 | [Test coverage](#2-test-coverage) | 16 h |
| 3 | [Developer docs: question-bank import pipeline](#3-developer-docs-question-bank-import-pipeline) | 7 h |
| 4 | [Admin tools for explanations and tagging](#4-admin-tools-for-explanations-and-tagging) | 18 h |
| 5 | [PWA, dark mode and translations](#5-pwa-dark-mode-and-translations) | 12 h |
| 6 | [Monte Carlo engine optimisation](#6-monte-carlo-engine-optimisation) | 10 h |

---

## 1. Interactive intersection simulator

The deferred flagship feature (see [`cel.md`](cel.md), section 5, in Polish).
Right of way is the densest cluster of 3-point questions. The simulator is tied
to a **rule**, not to a single question: one scene such as "uncontrolled
intersection, give way to the right" serves dozens of exam questions.

| Task | Estimate | Done when |
| --- | --- | --- |
| 1.1 Scene format and right-of-way resolver | 8 h | A JSON scene schema (roads, lanes, signs A-7 / D-1 / B-20, traffic lights, vehicles including trams and emergency vehicles) and a pure resolver that returns the order in which vehicles pass. Unit tests cover about 15 canonical cases: right-hand rule, main road that turns, tram priority, roundabout, emergency vehicle, police officer's signals. |
| 1.2 SVG renderer | 6 h | A Blade + Alpine component draws any valid scene as SVG. Mobile-first, readable in light and dark mode, no layout shift. |
| 1.3 Animation layer | 6 h | Vehicles move in the resolved order with play, step and replay controls. `prefers-reduced-motion` is respected. GSAP is free, but under its own licence that is not OSI-approved, so check it against the project's MPL-2.0 licence first; the Web Animations API is the fallback. |
| 1.4 Interactive mode | 6 h | The learner taps vehicles in the order they should pass. Feedback names the rule that decides each step. Attempts are stored per user and shown on the dashboard. |
| 1.5 Scenes and links to questions | 8 h | 8–10 scenes cover the most common right-of-way situations. Related question pages show "Try it in the simulator". The repository keeps a small sample of scenes, and the full set loads from outside it, like the rest of the content layer. |

## 2. Test coverage

Today: 193 tests and 872 assertions, mostly feature tests. Several engine classes
and commands have no direct tests.

| Task | Estimate | Done when |
| --- | --- | --- |
| 2.1 Engine maths unit tests | 5 h | `FsrsService` (retrievability, intervals, lapses against reference values), `AbilityService::estimate` (EAP at known points, monotonicity), `PCorrectService` (recall × time model), `RiskQueueService` (loss formula and ordering) and `EngineCache` (epoch invalidation) are covered. |
| 2.2 Trap tagger golden tests | 2 h | A fixture of real question sentences pins every trap type, including known false positives such as "przede wszystkim" (Polish for "above all"). |
| 2.3 Console commands | 3 h | `prawko:tag-traps`, `prawko:detect-twins` (on a small fixture bank) and `prawko:transcode-media` (skipped when `ffmpeg` is missing) have tests. |
| 2.4 HTTP surface | 4 h | `MediaController` is tested for file-name validation, path traversal and missing files. The REST API v1 (`/api/v1/*`, Sanctum) is tested for login, `me`, questions and sessions, including authorisation between users. |
| 2.5 Continuous integration | 2 h | A GitHub Actions workflow runs PHPUnit on SQLite and Pint on every push, with a coverage report and a target of at least 70% for `app/Services`. |

## 3. Developer docs: question-bank import pipeline

The import works, but the knowledge about it lives in code comments.

| Task | Estimate | Done when |
| --- | --- | --- |
| 3.1 `docs/import-pipeline.md` | 4 h | Documents where the official data comes from (the XLSX question bank and media archives published on gov.pl), the expected layout of `storage/app/import/` (`baza_pytan.xlsx`, `media/{mp4, cz. 1, cz. 2}`), the column mapping and translations, what `--fresh` deletes, transcoding with `prawko:transcode-media`, how media files are matched to questions, and the post-import steps (`prawko:tag-traps`, `prawko:detect-twins` and its parameters). |
| 3.2 Media check as an Artisan command | 2 h | The root-level `sprawdz-media.php` becomes `prawko:check-media` with a JSON output option. The generated `braki-mediow.*` reports leave the repository. |
| 3.3 Synthetic sample bank | 1 h | A tiny XLSX and a few media files under `tests/fixtures/` let contributors run the whole pipeline without the 2+ GB official data. |

## 4. Admin tools for explanations and tagging

Current state:
- The admin panel shows counts and manages users.
- Explanations are generated on demand by Claude and stored with source, model and date. 410 of 3,525 active questions have one so far.
- Trap tags come from regex rules and are overwritten on every `prawko:tag-traps` run.
- 3,276 confusable pairs have been detected; none has a generated difference yet.
- Notes on disputed questions live in `config/prawko.php`.

| Task | Estimate | Done when |
| --- | --- | --- |
| 4.1 Explanations screen | 6 h | Explanations can be filtered by source, model, date, media and disputed status. Each previews with its question and media, and can be edited by hand (marked `manual` and protected from regeneration), regenerated or deleted. |
| 4.2 Learner feedback loop | 4 h | A "this didn't help" button under each explanation feeds a review queue sorted by the number of reports, as planned in `cel.md`. |
| 4.3 Trap tag overrides | 4 h | Tags can be added or removed per question and are stored separately, so `prawko:tag-traps` keeps manual decisions. A distribution view helps tune the patterns. |
| 4.4 Confusable pairs review | 3 h | Detected pairs can be accepted or rejected, and differences generated in a rate-limited batch on the queue. |
| 4.5 Disputed questions in the database | 1 h | The notes move from config to a table with an admin form, and still survive a re-import of the question bank. |

## 5. PWA, dark mode and translations

| Task | Estimate | Done when |
| --- | --- | --- |
| 5.1 PWA | 4 h | The app has an offline fallback page. The asset cache name follows the build hash instead of a fixed `prawko-assets-v1`. Users are prompted to reload when a new service worker is waiting, instead of an immediate `skipWaiting`. The Lighthouse PWA and performance audit has been run and its findings fixed. |
| 5.2 Dark mode | 3 h | Every screen passes a WCAG AA contrast audit, including the forecast chart (SVG), the exam media frame and the badges. A screenshot checklist exists for regressions. |
| 5.3 Translations | 5 h | AI explanations and pair differences follow the user's locale; today they are always Polish. English gets the app-level `auth.php` and `validation.php` that German and Ukrainian already have. The translation-coverage test enforces key parity for all locales. |

## 6. Monte Carlo engine optimisation

Measured on production data in October 2026: one forecast for the category B
pool (2,138 questions) takes about 0.8 s. About 0.7 s of that is spent computing
`p_correct`, and about 0.1 s in the 10,000-iteration simulation. The result is
cached until the next answer, so the first dashboard view after every session
pays the full cost.

| Task | Estimate | Done when |
| --- | --- | --- |
| 6.1 Faster `p_correct` batch | 3 h | One query with only the needed columns, no model hydration and a vectorised calculation. Target: under 150 ms for the B pool. |
| 6.2 Exact score distribution | 4 h | Per exam layer, the number of correct answers is modelled as Binomial(n, mean p) (a draw without replacement from a large pool), and the six layers are convolved by dynamic programming over 0–74 points. This gives exact P(pass), median and 5th/95th percentiles in milliseconds. Monte Carlo stays as the reference in tests, and the two agree within 1 percentage point. |
| 6.3 Recompute off the request path | 2 h | A queued job refreshes the forecast after each completed session, and the dashboard serves the previous value while it refreshes. |
| 6.4 Benchmark command | 1 h | `prawko:bench-forecast` reports timings so regressions are visible. |
