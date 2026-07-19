<?php

/** Sesje: nauka, egzamin, wyniki, diagnoza. Polski jest źródłem. */
return [
    'title' => ':mode · pytanie :n',
    'progress' => ':n / :total',
    'category' => 'kat. :code',
    'time_left' => 'Pozostały czas',

    'new_badge' => 'nowe',

    'feedback' => [
        'correct' => 'Poprawna odpowiedź!',
        'wrong' => 'Niepoprawnie.',
        'sure_mistake' => 'Pewny błąd',
        'lucky_guess' => 'Trafiony strzał',
        'time' => 'czas: :n s',
        'correct_badge' => 'poprawna',
        'your_choice' => 'Twój wybór',
        'next' => 'Następne pytanie →',
    ],

    'confidence' => [
        'prompt' => 'Sprawdź odpowiedź — na ile jesteś pewien?',
        'hint' => 'Odpowiadaj szczerze: trafiony strzał nie liczy się jak wiedza, więc pytanie wróci do Ciebie szybciej.',
    ],

    'actions' => [
        'check' => 'Sprawdź odpowiedź',
        'save_next' => 'Zapisz i dalej →',
        'finish' => 'Zakończ sesję',
        'skip' => 'Pomiń pytanie',
        'prev' => '← Poprzednie',
        'confirm_finish' => 'Zakończyć sesję i zobaczyć wynik?',
    ],

    'navigator' => [
        'title' => 'Pytania',
    ],

    'exam' => [
        'badge' => 'Egzamin próbny',
        'answered' => ':done/:total udzielonych',
        'phase_reading' => 'Czytanie',
        'phase_film' => 'Film',
        'phase_answer' => 'Odpowiedź',
        'film_hint' => 'Materiał wideo — kliknij <strong class="text-white">Start</strong>, aby odtworzyć.',
        'film_once' => 'Film odtwarza się tylko raz.',
        'playing' => 'trwa film',
        'read_with_film' => 'Przeczytaj pytanie, a następnie odtwórz film. Po filmie masz :n s na odpowiedź.',
        'read_no_film' => 'Przeczytaj pytanie, potem przejdź do odpowiedzi (:n s).',
        'start_film' => '▶ Start – odtwórz film',
        'go_answer' => 'Przejdź do odpowiedzi →',
        'film_playing' => 'Trwa odtwarzanie filmu — odpowiadasz zaraz po jego zakończeniu.',
        'finish' => 'Zakończ egzamin',
        'next' => 'Następne pytanie →',
        'no_back' => 'Bez cofania',
    ],

    'result' => [
        'title' => 'Wynik sesji',
        'threshold' => 'Próg zaliczenia: :n / :max pkt',
        'heading' => 'Wynik sesji · :mode · kat. :code',
        'title_short' => 'Wynik · :mode',
        'exam_passed' => 'Egzamin zdany',
        'exam_failed' => 'Egzamin niezdany',
        'score' => ':score / :max pkt',
        'correct_count' => 'poprawnych: :correct z :total',
        'again' => 'Jeszcze raz',
        'back' => 'Powrót do pulpitu',
        'review' => 'Przegląd odpowiedzi',
        'passed' => 'Zdany',
        'failed' => 'Niezdany',
    ],

    'review' => [
        'skipped' => '— brak (pominięte)',
        'your_answer' => 'Twoja odpowiedź:',
        'correct_answer' => 'Poprawna:',
        'preview' => 'Podgląd pytania i odpowiedzi',
        'points' => ':n pkt',
    ],

    'diagnosis' => [
        'result_title' => 'Wynik diagnozy',
        'title' => 'Diagnoza ukończona',
        'lead' => 'Oto Twój punkt startowy — od teraz aplikacja liczy Twoją szansę zdania.',
        'verdict_emerald' => 'Świetny wynik — jesteś w strefie zdania. Utrzymaj formę powtórkami, żeby nie wypaść z rytmu.',
        'verdict_brand' => 'Blisko celu. Kilka sesji na najsłabszych pytaniach i szansa przekroczy próg zdania.',
        'verdict_amber' => 'Solidny start, ale jest co poprawiać. Skup się na pytaniach poniżej — mają największy wpływ na wynik.',
        'verdict_rose' => 'To dopiero początek — i dobrze, że wiesz od czego zacząć. Regularna nauka szybko podniesie ten wynik.',
        'accuracy' => 'skuteczność',
        'mistakes_title' => 'Twoje błędy',
        'mistakes_lead' => 'Pomyliłeś się w <span class="font-semibold tnum text-slate-700 dark:text-slate-200">:wrong</span> z <span class="tnum">:total</span> pytań. Rozwiń „Wyjaśnij (AI)", żeby poznać powód.',
        'show_all' => 'Pokaż wszystkie pytania',
        'show_mistakes' => 'Pokaż tylko błędy',
        'start_title' => 'Od czego zacząć',
        'start_lead' => 'Pytania o największym wpływie na Twój wynik.',
        'start_practice' => 'Zacznij naukę od najsłabszych',
        'to_dashboard' => 'Przejdź do pulpitu',
    ],

    'explanation' => [
        'title' => 'Wyjaśnienie',
        'ai_title' => 'Wyjaśnienie AI',
        'generate' => 'Wyjaśnij (AI)',
        'generating' => 'Generuję wyjaśnienie…',
        'error' => 'Nie udało się teraz wygenerować wyjaśnienia. Spróbuj ponownie.',
    ],

    'twins' => [
        'difference' => 'Różnica:',
        'analyse' => 'Czym się różnią? (AI)',
        'analysing' => 'Analizuję różnicę…',
        'error' => 'Nie udało się teraz przeanalizować różnicy.',
    ],

    'media' => [
        'image_missing' => 'Zdjęcie niedostępne w bazie źródłowej',
        'video_missing' => 'Materiał wideo niedostępny w bazie źródłowej',
        'no_video_support' => 'Twoja przeglądarka nie obsługuje odtwarzania wideo.',
    ],

    'disputed' => [
        'title' => 'Pytanie sporne — wada katalogu MI',
        'lead' => 'Wg katalogu Ministerstwa Infrastruktury prawidłowa jest odpowiedź <strong class="text-amber-900 dark:text-amber-200">:answer</strong> — i tak samo punktuje ją państwowy egzamin. <strong>Na egzaminie zaznacz :answer</strong>, mimo poniższych zastrzeżeń.',
        'basis' => 'Podstawa prawna: :basis.',
    ],
];
