<?php

/** Ekran „Gdzie tracisz punkty". Polski jest źródłem. */
return [
    'title' => 'Gdzie tracisz punkty',
    'lead' => 'Rozbicie Twojej szansy zdania na czynniki — kat. :code.',
    'back' => 'Pulpit',

    'header' => [
        'expected' => 'Oczekiwany wynik',
        'short_by' => 'Do progu brakuje :n pkt',
        'surplus' => ':n pkt zapasu ponad próg',
        'expected_loss' => 'Oczekiwana strata',
        'earned_lost' => 'zdobyte / stracone',
        'same_numbers' => 'Te same liczby, co „szansa zdania" na pulpicie — tylko rozłożone na warstwy. Pokrycie danymi: :coverage%',
        'prior_warning' => '— reszta to prior, nie pomiar.',
    ],

    'layers' => [
        'title' => 'Straty wg warstwy arkusza',
        'lead' => 'Egzamin to :questions pytań w :layers warstwach o różnej wadze. Sortujemy po utraconych punktach, nie po procencie skuteczności.',
        'biggest' => 'największa strata',
        'on_sheet' => ':n pytań na arkuszu',
        'pool_points' => ':n pkt puli',
        'chance' => 'szansa :n',
        'coverage' => 'pokrycie :n',
        'your_accuracy' => 'Twoja skuteczność :n',
        'answers_count' => '(:n odp.)',
    ],

    'insight' => [
        'title' => 'Nie goń najgorszego procentu',
        'body' => 'Najsłabiej idzie Ci warstwa <strong>:weak</strong> (szansa :weakChance), ale kosztuje Cię tylko <strong class="tnum">:weakLoss pkt</strong> — bo to raptem :weakPoints z :max punktów egzaminu. Najwięcej tracisz w warstwie <strong>:worst</strong>: <strong class="tnum">:worstLoss pkt</strong>, bo to :worstPoints punktów stawki.',
        'covers_gap' => 'Sama ta warstwa przykrywa całą Twoją lukę do progu.',
    ],

    'traps' => [
        'title' => 'Straty wg typu pułapki',
        'lead' => 'Ile punktów oczekiwanie oddajesz na pytaniach z danym haczykiem językowym. Pytanie może nieść kilka naraz, więc te straty się nakładają — służą porównaniu między sobą, nie sumowaniu.',
        'questions' => ':n pytań',
        'accuracy' => '· Twoja skuteczność :acc (:n odp.)',
    ],

    'calibration' => [
        'title' => 'Kalibracja pewności',
        'lead' => 'Czy Twoje „wiem" znaczy „wiem" — z ocen pewności w trybie nauki.',
        'sure_right' => 'Wiem → dobrze',
        'sure_right_note' => 'tak ma być',
        'sure_wrong' => 'Wiem → błąd',
        'sure_wrong_note' => 'pewne błędy — najdroższe',
        'guess_right' => 'Strzelam → dobrze',
        'guess_right_note' => 'trafione strzały',
        'guess_wrong' => 'Strzelam → błąd',
        'guess_wrong_note' => 'uczciwe „nie wiem"',
        'overconfidence' => 'Deklaracje „wiem", które zawiodły',
        'of_n' => 'z :n',
        'guess_vs_chance' => 'Trafność strzałów vs poziom przypadku',
        'versus' => 'wobec :n',
        'sure_mistakes_title' => 'Twoje pewne błędy',
        'sure_mistakes_lead' => 'Byłeś pewien i się pomyliłeś. Na egzaminie nic Cię przy nich nie zatrzyma — zacznij właśnie od tych.',
    ],

    'start' => [
        'title' => 'Od czego zacząć',
        'lead' => 'Ten sam rachunek zejściem na pojedyncze pytania — kolejka ryzyka waży błędy punktami, więc już uwzględnia wszystko powyżej.',
        'chance' => 'szansa',
    ],

    'actions' => [
        'practice' => 'Ucz się tego, co kosztuje najwięcej',
        'back' => 'Wróć do pulpitu',
    ],
];
