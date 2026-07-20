<?php

use App\Enums\QuestionType;

/**
 * Ustawienia domenowe aplikacji do nauki na prawo jazdy.
 * Trzymamy je w configu (nie w kodzie), aby łatwo dostrajać reguły egzaminu
 * bez zmian w logice.
 */
return [
    // Bonus powitalny w kredytach dla nowego konta (wirtualny portfel).
    'signup_bonus' => 100,

    // Parametry symulacji państwowego egzaminu teoretycznego.
    'exam' => [
        'time_limit_seconds' => 25 * 60, // 25 minut
        'pass_threshold' => 68,          // wymagane punkty (na 74)
        // Wracać do przerwanego egzaminu („Wznów sesję") wolno tylko z zapasem
        // czasu WIĘKSZYM niż ten próg. Poniżej (lub po upływie limitu) sesja jest
        // domykana z obecnymi odpowiedziami — bez wznawiania (brak odpowiedzi = 0 pkt).
        'min_resume_seconds' => 10 * 60, // 10 minut

        // Skład arkusza: [typ pytania, waga punktowa, liczba pytań].
        // Razem: 20 podstawowych + 12 specjalistycznych = 32 pytania, max 74 pkt.
        'composition' => [
            [QuestionType::Basic, 3, 10],
            [QuestionType::Basic, 2, 6],
            [QuestionType::Basic, 1, 4],
            [QuestionType::Specialist, 3, 6],
            [QuestionType::Specialist, 2, 4],
            [QuestionType::Specialist, 1, 2],
        ],
    ],

    /*
     * Pytania sporne — odpowiedź katalogowa uznana za niezgodną z przepisami.
     *
     * Klucz = „Numer pytania" z arkusza MI (external_id, ten sam, który widać
     * w aplikacji). Trzymamy to w configu, a nie w bazie, bo to treść autorska:
     * przetrwa `prawko:import-questions`, który nadpisuje rekordy pytań, i jest
     * wersjonowana razem z kodem.
     *
     * UWAGA: na egzaminie punktuje odpowiedź KATALOGOWA. Tabliczka musi mówić
     * o tym wprost — wiedza o wadzie pytania nie może kosztować punktów.
     */
    'disputed' => [
        '10898' => [
            'catalog_answer' => 'B',
            'basis' => 'art. 135 ust. 1 pkt 2 lit. b w zw. z art. 135 ust. 2 ustawy Prawo o ruchu drogowym',
            'note' => 'Katalog uznaje za poprawne przewożenie osób „w liczbie przekraczającej o dwa liczbę miejsc określoną w dowodzie rejestracyjnym". '
                .'Tymczasem art. 135 ust. 2 wyłącza zatrzymanie prawa jazdy, gdy liczba przewożonych osób ponad limit miejsc nie przekracza 2 '
                .'(samochód osobowy). Przekroczenie o dokładnie dwie osoby mieści się więc w wyłączeniu i policjant prawa jazdy NIE zatrzyma — '
                .'obowiązek powstaje dopiero przy przekroczeniu o więcej niż 2.',
            'petition' => 'Zgłoszone petycją do Ministerstwa Infrastruktury.',
        ],
        '10899' => [
            'catalog_answer' => 'B',
            'basis' => 'art. 135 ust. 1 pkt 2 lit. b w zw. z art. 135 ust. 2 ustawy Prawo o ruchu drogowym',
            'note' => 'Wariant tego samego błędu co w pytaniu 10898, tyle że o pozwoleniu czasowym zamiast dowodu rejestracyjnego. '
                .'Art. 135 ust. 2 wyłącza zatrzymanie prawa jazdy, gdy liczba przewożonych osób ponad limit miejsc nie przekracza 2 '
                .'(samochód osobowy), więc przekroczenie o dokładnie dwie osoby nie daje podstawy do zatrzymania — '
                .'obowiązek powstaje dopiero przy przekroczeniu o więcej niż 2.',
            'petition' => 'Zgłoszone petycją do Ministerstwa Infrastruktury.',
        ],
    ],

    // Szybka sesja nauki.
    'practice' => [
        'default_size' => 10,
        // Ile sesji nauki (ułamek) rezerwujemy na NOWE pytania. „Trochę", żeby
        // pokrycie puli szło do przodu mimo że kolejka ryzyka spycha niewidziane
        // na dół. Reszta to powtórki + najsłabsze (kolejka ryzyka).
        'new_ratio' => 0.2,
    ],

    // Tryb ekspresowy (końcówka przed egzaminem): dowieźć KAŻDE pytanie kategorii
    // do stanu „utrwalone lub odpowiedziane poprawnie" = streak ≥ 1 w FSRS
    // (streak zeruje się przy błędzie, więc ≥ 1 znaczy „ostatnio poprawnie").
    'express' => [
        'available_within_days' => 7, // przycisk w oknie planu, gdy do egzaminu ≤ tydzień
        'session_size' => 32,         // wielkość jednej sesji ekspresowej
    ],

    // Plan pod datę egzaminu (Etap 8).
    'plan' => [
        'target_pass' => 0.95, // docelowa szansa zdania na dzień egzaminu
        // Górny limit „dziennej dawki" jednym kliknięciem. 32 = tyle, ile na
        // egzaminie: na dłuższych sesjach uwaga siada na ostatnich pytaniach.
        'max_session' => 32,
    ],

    // Diagnoza (Etap 3): onboarding, który szacuje θ_u z jednego przebiegu.
    'diagnosis' => [
        // Skład stratyfikowany — proporcje jak na egzaminie (62,5% podst. / 37,5% spec.), suma = 40.
        'composition' => [
            [QuestionType::Basic, 3, 13],
            [QuestionType::Basic, 2, 8],
            [QuestionType::Basic, 1, 4],
            [QuestionType::Specialist, 3, 8],
            [QuestionType::Specialist, 2, 5],
            [QuestionType::Specialist, 1, 2],
        ],
        // Szerokość priora N(0, sd²) w estymatorze EAP — regularizuje θ (bez ±∞ przy 0/100%).
        'prior_sd' => 1.5,
    ],

    // ===== Silnik wiedzy (Etap 1) =====

    // FSRS-5 — pamięć: stabilność (S) i trudność (D) na parę (użytkownik, pytanie).
    'fsrs' => [
        'request_retention' => 0.90, // docelowa retencja przy planowaniu powtórek
        'decay' => -0.5,
        'maximum_interval' => 3650,  // górny limit odstępu (dni)
        // Domyślne wagi FSRS-5 (19 sztuk). Do dostrojenia po zebraniu danych.
        'weights' => [
            0.40255, 1.18385, 3.173, 15.69105, 7.1949, 0.5345, 1.4604, 0.0046,
            1.54575, 0.1192, 1.01925, 1.9395, 0.11, 0.29605, 2.2698, 0.2315,
            2.9898, 0.51655, 0.6621,
        ],
    ],

    // Prior IRT/Rasch dla pytań jeszcze nierozwiązanych: p = σ(θ_u − b_q).
    'ability' => [
        'k_user' => 0.12,     // tempo aktualizacji θ_u
        'k_question' => 0.04, // tempo aktualizacji b_q (wolniejsze — pytanie jest „stabilniejsze")
        'clamp' => 4.0,       // |θ|, |b| ≤ 4 (skala logit)
    ],

    // Model czasowy: p(zdążę w limicie) — bo na egzaminie liczy się też czas.
    // *_seconds to ŁĄCZNE budżety (wejście dla silnika wiedzy). Faktyczny przebieg
    // egzaminu dzieli podstawowe na fazy: czytanie → (film) → odpowiedź.
    'timing' => [
        'basic_seconds' => 35,          // 20 s czytania + 15 s odpowiedzi (dla silnika)
        'basic_read_seconds' => 20,     // faza czytania (podstawowe) — potem Start/film
        'basic_answer_seconds' => 15,   // faza odpowiedzi (podstawowe), po filmie
        'specialist_seconds' => 50,     // specjalistyczne — jedna faza (czytanie + odpowiedź)
        'default_p' => 0.97,            // gdy brak danych o czasie odpowiedzi
    ],
];
