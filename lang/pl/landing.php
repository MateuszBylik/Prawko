<?php

/**
 * Teksty strony głównej. To JEDYNA przetłumaczona część aplikacji — reszta jest
 * po polsku (patrz config/locales.php).
 *
 * Polski jest źródłem: tu powstaje treść, a pozostałe pliki są jej tłumaczeniem.
 * Zmieniając cokolwiek tutaj, zajrzyj do lang/{en,de,uk}/landing.php.
 *
 * Liczby (próg, punkty, skład arkusza) NIE są tłumaczone — idą z config/prawko.php
 * przez `:placeholdery`, żeby strona nie mogła obiecać czegoś innego niż symulacja.
 */
return [
    'nav' => [
        'login' => 'Zaloguj się',
        'dashboard' => 'Pulpit',
        'language' => 'Język',
    ],

    'type' => [
        'basic' => 'Podstawowe',
        'specialist' => 'Specjalistyczne',
    ],

    'hero' => [
        'eyebrow' => 'Teoria · kat. B i pozostałe',
        'title' => 'Rozwiąż całą oficjalną bazę, aż przestanie zaskakiwać',
        'lead' => 'Te same pytania co na egzaminie państwowym — z filmami i zdjęciami sytuacji drogowych. Aplikacja liczy, ile realnie masz szans zdać, i mówi, czego uczyć się najpierw.',
        'cta_primary' => 'Zaloguj się i ucz',
        'cta_dashboard' => 'Przejdź do pulpitu',
        'cta_secondary' => 'Jak liczymy szansę zdania',
        'stats' => ':questions pytań · :videos z wideo · próg zdania :threshold/:max',
        'sample_badge' => 'podgląd',
        'sample_question' => 'Czy w tej sytuacji masz obowiązek ustąpić pierwszeństwa pieszemu?',
        'yes' => 'Tak',
        'no' => 'Nie',
    ],

    'engine' => [
        'eyebrow' => 'Silnik',
        'title' => 'Nie „procent poprawnych". Szansa zdania.',
        'p1' => '„80% poprawnych" nie mówi nic o egzaminie. Arkusz losuje :count pytań o różnej wadze, a do zdania trzeba :threshold z :max punktów — czyli około :percent%. Te same 80% raz wystarczą, a raz nie, zależnie od tego, co się wylosuje.',
        'p2' => 'Dlatego zamiast liczyć procenty, symulujemy egzamin :simulations razy. Za każdym razem losujemy arkusz zgodny z oficjalnym składem i sprawdzamy, czy wyszedł próg. Odsetek udanych podejść to Twoja szansa zdania.',
        'memory_title' => 'Model pamięci',
        'memory_desc' => 'Dla każdego pytania szacujemy, czy w dniu egzaminu jeszcze będziesz je pamiętać. Powtórka wraca tuż przed tym, jak byś zapomniał.',
        'difficulty_title' => 'Trudność pytania',
        'difficulty_desc' => 'Pytania różnią się trudnością niezależnie od Ciebie. Uczymy się obu rzeczy naraz: Twojego poziomu i poziomu pytania.',
        'time_title' => 'Czas też się liczy',
        'time_desc' => 'Na egzaminie masz sekundy, nie minuty. Jeśli pytanie zawsze zajmuje Ci za długo, wchodzi to do rachunku.',
        'card_chance' => 'Szansa zdania',
        'card_readiness' => 'Blisko celu',
        'card_forecast' => 'Prognozowany wynik',
        'card_range' => 'zakres 58–70 pkt',
        'card_threshold' => 'próg zdania: :threshold',
        'card_note' => 'Przykładowy odczyt. Twój policzy się z Twoich odpowiedzi.',
    ],

    'how' => [
        'title' => 'Jak to działa',
        'lead' => 'Od pierwszego pomiaru po plan pod konkretną datę egzaminu.',
        'diagnosis_title' => 'Diagnoza na start',
        'diagnosis_desc' => 'Czterdzieści pytań o składzie jak na egzaminie, bez presji czasu. Po nich wiesz, na czym stoisz, zamiast zgadywać.',
        'base_title' => 'Cała oficjalna baza',
        'base_desc' => 'Pytania podstawowe (TAK/NIE) i specjalistyczne (A/B/C) z filmami i zdjęciami — dokładnie te z katalogu Ministerstwa Infrastruktury.',
        'exam_title' => 'Egzamin jak państwowy',
        'exam_desc' => ':count pytań, limity czasu na każde z osobna, punktacja 3/2/1 i próg :threshold pkt. Film leci raz, bez cofania — jak w WORD-zie.',
        'reviews_title' => 'Powtórki w punkt',
        'reviews_desc' => 'Aplikacja pamięta, co sprawia Ci trudność, i podsuwa te pytania tuż przed tym, jak byś je zapomniał — nie wcześniej i nie później.',
        'losses_title' => 'Gdzie tracisz punkty',
        'losses_desc' => 'Sam procent skuteczności myli, bo warstwy arkusza mają różną wagę. Pokazujemy, które z nich zjadają Ci najwięcej punktów.',
        'plan_title' => 'Plan pod datę',
        'plan_desc' => 'Podajesz termin egzaminu, dostajesz dzienną dawkę. Aplikacja mówi wprost, czy przy obecnym tempie zdążysz.',
    ],

    'rules' => [
        'title' => 'Jak wygląda egzamin państwowy',
        'lead' => 'Symulacja w aplikacji odwzorowuje te reguły.',
        'questions_label' => 'pytań na arkuszu',
        'questions_note' => ':basic podstawowych + :specialist specjalistycznych',
        'points_label' => 'punktów do zdobycia',
        'points_note' => 'pytania są warte 3, 2 albo 1 pkt',
        'threshold_label' => 'punktów, by zdać',
        'threshold_note' => 'to około :percent% — margines jest wąski',
        'time_label' => 'minut na całość',
        'time_note' => 'podstawowe: 20 s na czytanie, 15 s na odpowiedź',
        'sheet_title' => 'Skład arkusza',
        'sheet_lead' => 'Tyle pytań każdego rodzaju losuje egzamin — i dokładnie tak losuje je symulacja.',
        'col_kind' => 'Rodzaj',
        'col_weight' => 'Waga',
        'col_count' => 'Pytań',
        'col_points' => 'Punktów',
        'total' => 'Razem',
        'points_short' => ':n pkt',
    ],

    'cta' => [
        'title' => 'Sprawdź, ile naprawdę umiesz',
        'lead' => 'Konta zakłada administrator. Jeśli masz dostęp — zaloguj się i zacznij od diagnozy. Jeśli nie, napisz na :email.',
        'button' => 'Zaloguj się',
    ],

    'footer' => [
        'tagline' => 'Nauka teorii na prawo jazdy na oficjalnej bazie pytań, z realnym szacowaniem szansy zdania.',
        'disclaimer' => 'Baza pytań: Ministerstwo Infrastruktury (gov.pl). Serwis nie jest powiązany z MI ani z WORD.',
        'contact' => 'Kontakt',
        'company' => 'Dane firmy',
        'rights' => '© :year :company. Wszelkie prawa zastrzeżone.',
    ],

    'meta' => [
        'title' => 'Prawko — nauka teorii na prawo jazdy',
        'description' => 'Nauka teorii na prawo jazdy na oficjalnej bazie pytań Ministerstwa Infrastruktury. Symulacja egzaminu 1:1 i realne szacowanie szansy zdania.',
    ],
];
