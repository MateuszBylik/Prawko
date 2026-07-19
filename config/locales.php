<?php

/**
 * Języki interfejsu.
 *
 * Na razie przetłumaczona jest WYŁĄCZNIE strona główna — reszta aplikacji jest
 * po polsku. Dlatego przełącznik pokazujemy tylko tam; wpuszczenie go do zalogowanej
 * części obiecywałoby tłumaczenie, którego nie ma.
 *
 * Kod ukraińskiego to `uk` (ISO 639-1), a nie `ua` (to kod KRAJU). W danych pytań
 * z arkusza MI klucz tłumaczenia nazywa się jednak `ua` — dlatego każdy język ma
 * `questions_key`: nazwę pod którą leżą jego tłumaczenia w `questions.translations`.
 * To JEDYNE miejsce, gdzie te dwa światy się spotykają; nie rozsiewaj mapowania
 * po kodzie.
 *
 * `questions_key => null` (polski) znaczy „tekst bazowy z kolumny `text`”, bo
 * arkusz nie zawiera polskiego jako tłumaczenia — polski JEST oryginałem.
 *
 * UWAGA: w `translations` jest też klucz `pjm` (polski język migowy), ale trzyma
 * NAZWY PLIKÓW WMV, a nie tekst („pjm290.wmv”). Nie ma go tu i mieć nie może —
 * wyświetlony jako treść pytania byłby bełkotem.
 *
 * Flagi rysuje komponent <x-flag :code="..."> jako inline SVG — emoji odpadło, bo
 * Windows nie ma czcionki z flagami i degraduje je do liter kraju.
 *
 * Uwaga — flaga oznacza KRAJ, nie język; dla tych czterech pozycji skrót jest
 * czytelny, ale przy dodaniu np. angielskiego z USA albo niemieckiego z Austrii
 * przestanie być jednoznaczny. Dlatego obok flagi zawsze jest nazwa języka.
 */
return [
    'pl' => ['label' => 'Polski', 'native' => 'Polski', 'questions_key' => null],
    'en' => ['label' => 'Angielski', 'native' => 'English', 'questions_key' => 'en'],
    'de' => ['label' => 'Niemiecki', 'native' => 'Deutsch', 'questions_key' => 'de'],
    'uk' => ['label' => 'Ukraiński', 'native' => 'Українська', 'questions_key' => 'ua'],
];
