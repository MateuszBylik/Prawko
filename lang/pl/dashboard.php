<?php

/** Pulpit kursanta. Polski jest źródłem — zmiany przenieś do lang/{en,de,uk}/dashboard.php. */
return [
    'title' => 'Pulpit',
    'greeting' => 'Cześć, :name',
    'subtitle' => 'Ucz się dziś kilku pytań — małymi krokami do egzaminu.',

    'diagnosis' => [
        'badge' => 'Zacznij tutaj',
        'title' => 'Zrób diagnozę',
        'lead' => '40 pytań, bez presji czasu (~10 min). Na tej podstawie ocenimy Twój poziom i policzymy realną <strong>szansę zdania</strong> — a potem podpowiemy, czego uczyć się najpierw.',
        'point_level' => 'Szacujemy Twój poziom',
        'point_chance' => 'Liczymy szansę zdania',
        'point_plan' => 'Ustalamy plan nauki',
        'start' => 'Rozpocznij diagnozę',
    ],

    'forecast' => [
        'chance' => 'Szansa zdania',
        'no_data' => 'Za mało danych',
        'predicted' => 'Prognozowany wynik',
        'points_short' => ':n pkt',
        'range' => 'zakres :low–:high pkt',
        'threshold' => 'próg zdania: :n',
        'meta' => ':samples symulacji egzaminu · pokrycie danymi: :coverage%',
        'losses_link' => 'Gdzie tracisz punkty',
        'not_enough' => 'Rozwiąż co najmniej <strong>10 pytań</strong>, aby silnik oszacował Twoją szansę zdania. Na razie masz <strong class="tnum">:answered</strong>. Zacznij od pytań poniżej — to te o największym wpływie na wynik.',
    ],

    'resume' => [
        'text' => 'Masz niedokończoną sesję (:mode) — możesz do niej wrócić.',
        'button' => 'Wznów sesję',
        'exam_over' => 'Czas egzaminu dobiegł końca.',
        'see_result' => 'Zobacz wynik',
    ],

    'express' => [
        'title' => 'Tryb ekspresowy',
        'lead' => 'Utrwal lub odpowiedz poprawnie każde pytanie przed egzaminem.',
        'start' => 'Ćwicz ekspresowo (:n)',
        'done' => 'Cała pula opanowana 🎉',
        'progress' => 'Utrwalone lub poprawne: :satisfied / :total',
        'remaining' => 'zostało :n (≈:perday dziennie)',
    ],

    'plan' => [
        'title' => 'Plan na egzamin',
        'days' => 'dni',
        'until' => 'do :date',
        'chance' => 'szansa (cel :target%)',
        'coverage' => 'pokrycie (:seen/:total)',
        'exam_date' => 'Data egzaminu',
        'set_date' => 'Ustaw datę',
        'dose_left' => 'Dziś zostało: :n pytań',
        'dose_done' => 'Dzisiejsza dawka zrobiona',
        'dose_detail' => ':done/:target nowych',
        'dose_reviews' => ':n powtórek',
        'pace' => 'Twoje tempo: :pace/dzień',
        'pace_days' => '(śr. z :n dni)',
        'start_dose' => 'Codzienne ćwiczenia',
        'keep_going' => 'Ucz się dalej',
        'goal_note' => '<strong>Codzienne ćwiczenia</strong> łączą powtórki z odrobiną nowych. Do pełnego pokrycia zostało <span class="tnum font-semibold text-slate-600 dark:text-slate-300">:unseen</span> z :total pytań na <span class="tnum">:days</span> — czyli :perday nowych dziennie. Chcesz nadrobić szybciej? <strong>Nowe pytania</strong> to sesja wyłącznie z niewidzianych. Szansa zdania (:target%) to osobny, docelowy próg gotowości; pełne pokrycie nie jest warunkiem zdania, tylko najpewniejszą drogą do niego.',
        'days_choice' => '{1} :n dzień|[2,4] :n dni|[5,*] :n dni',
        'change_date' => 'Zmień lub usuń datę',
        'save' => 'Zapisz',
        'remove' => 'Usuń datę',
    ],

    'start' => [
        'title' => 'Egzamin próbny',
        'lead' => 'Sprawdź się w warunkach jak na egzaminie: :count pytań, limit czasu, wynik dopiero na końcu.',
        'category' => 'Kategoria :code',
        'change' => 'Zmień',
        'practice' => 'Rozpocznij naukę',
        'exam' => 'Egzamin próbny',
        'only_new' => 'Nowe pytania (:n)',
    ],

    'browse' => [
        'title' => 'Baza pytań',
        'lead' => 'Przeglądaj wszystkie pytania z odpowiedziami, filmami i zdjęciami. Filtruj po kategorii i typie.',
        'open' => 'Otwórz bazę',
    ],

    'risk' => [
        'title' => 'Co ćwiczyć najpierw',
        'lead' => 'Pytania uszeregowane wg tego, ile punktów oczekiwanie na nich tracisz.',
        'practice_weakest' => 'Ćwicz najsłabsze',
        'chance' => 'szansa',
    ],

    'stats' => [
        'title' => 'Twoje liczby',
        'sessions' => 'ukończonych sesji',
        'avg' => 'średni wynik',
        'exams' => 'egzaminy zdane',
        'answers' => 'odpowiedzi',
        'mastered' => 'opanowanych pytań',
        'due' => 'powtórek na dziś',
    ],

    'recent' => [
        'title' => 'Ostatnie sesje',
        'empty' => 'Brak ukończonych sesji. Rozpocznij pierwszą powyżej!',
        'passed' => 'zdany',
        'failed' => 'niezdany',
    ],

    'chart' => [
        'over_time' => 'Szansa zdania w czasie',
        'measurements' => '{1} :n pomiar, od :date|[2,4] :n pomiary, od :date|[5,*] :n pomiarów, od :date',
        'growing' => 'Wykres zacznie rosnąć, gdy odwiedzisz pulpit w kolejnych dniach',
        'empty' => 'Brak pomiarów. Rozwiąż Diagnozę — pierwszy punkt pojawi się od razu.',
        'aria' => 'Wykres szansy zdania w czasie. Wartości w tabeli pod wykresem.',
        'coverage' => 'Pokrycie',
        'title' => 'Historia szansy zdania',
        'target' => 'cel',
        'pp' => 'pkt proc.',
        'table' => 'Pokaż jako tabelę',
        'date' => 'Data',
        'chance' => 'Szansa',
    ],
];
