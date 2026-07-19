<?php

/**
 * Etykiety enumów i DTO — wszystko, co PHP zwraca użytkownikowi jako gotowy tekst.
 * Polski jest źródłem; zmiany przenieś do lang/{en,de,uk}/enums.php.
 */
return [
    'question_type' => [
        'basic' => 'Podstawowe',
        'specialist' => 'Specjalistyczne',
    ],
    'answer' => [
        'yes' => 'Tak',
        'no' => 'Nie',
    ],

    'exam_mode' => [
        'practice' => 'Nauka',
        'exam' => 'Egzamin próbny',
        'diagnosis' => 'Diagnoza',
    ],
    'role' => [
        'student' => 'Kursant',
        'instructor' => 'Instruktor',
        'school_admin' => 'Administrator szkoły',
        'admin' => 'Administrator',
    ],
    'confidence' => [
        'sure' => 'Wiem',
        'guess' => 'Strzelam',
    ],
    'trap' => [
        'negation' => ['label' => 'Negacja', 'hint' => 'Uwaga na przeczenie — pytanie pyta, czego NIE wolno lub co jest wyjątkiem.'],
        'quantifier' => ['label' => 'Kwantyfikator', 'hint' => 'Uwaga na słowa „zawsze / nigdy / tylko / każdy" — rzadko są prawdziwe bez wyjątków.'],
        'modality' => ['label' => 'Możliwość', 'hint' => 'Rozróżnij obowiązek („musisz") od możliwości („możesz, wolno").'],
        'scope' => ['label' => 'Warunek', 'hint' => 'Odpowiedź zależy od warunku („jeżeli / pod warunkiem / w przypadku").'],
        'number' => ['label' => 'Liczba', 'hint' => 'Liczy się konkretna wartość — nie pomyl progu (metry, km/h, tony, ‰).'],
    ],
    'readiness' => [
        'ready' => 'Gotowy do egzaminu',
        'close' => 'Blisko celu',
        'learning' => 'W trakcie nauki',
        'start' => 'Początek drogi',
    ],
    'plan_status' => [
        'ready' => ['label' => 'Jesteś gotowy', 'note' => 'Szansa zdania przekroczyła cel — utrzymuj formę powtórkami.'],
        'on_track' => ['label' => 'Na dobrej drodze', 'note' => 'Twoje tempo wystarcza, by zdążyć na czas. Trzymaj rytm.'],
        'behind' => ['label' => 'Trzeba przyspieszyć', 'note' => 'Przy obecnym tempie nie wyrobisz się na czas — zwiększ dzienną dawkę.'],
        'past' => ['label' => 'Termin minął', 'note' => 'Data egzaminu już minęła — ustaw nową, jeśli chcesz dalej planować.'],
        'no_date' => ['label' => 'Ustaw datę egzaminu', 'note' => 'Podaj datę, a wyznaczę dzienną dawkę nauki do celu.'],
    ],
    'calibration' => [
        'no_data' => ['label' => 'Za mało danych', 'note' => 'Oceniaj pewność w trybie nauki („Wiem" / „Strzelam"), a policzymy, na ile możesz sobie ufać.'],
        'overconfident' => ['label' => 'Nadmierna pewność', 'note' => 'Zbyt często jesteś pewien i się mylisz. To najdroższe błędy — nic ich nie zapowiada, więc na egzaminie zaznaczysz je bez wahania. Zacznij od listy poniżej.'],
        'underconfident' => ['label' => 'Wiesz więcej, niż sądzisz', 'note' => 'Twoje „strzały" trafiają wyraźnie powyżej poziomu przypadku — to nie są strzały, tylko wiedza, której sobie nie ufasz. Śmielej deklaruj „Wiem".'],
        'good' => ['label' => 'Dobra kalibracja', 'note' => 'Twoje deklaracje zgadzają się z wynikami — kiedy mówisz „wiem", zwykle wiesz. To dobry znak: możesz ufać własnemu poczuciu pewności na egzaminie.'],
    ],
    'errors' => [
        'no_questions' => 'Brak aktywnych pytań dla wybranej kategorii.',
        'no_new_questions' => 'Brak nowych pytań — całą pulę tej kategorii masz już przerobioną.',
        'no_category' => 'Wybierz kategorię prawa jazdy, zanim zaczniesz naukę.',
        'pick_category' => 'Wybierz kategorię prawa jazdy.',
    ],
];
