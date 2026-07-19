<?php

/**
 * Napisy wspólne dla całego panelu: nawigacja, akcje, komunikaty systemowe.
 * Polski jest ŹRÓDŁEM — zmieniając cokolwiek tutaj, zajrzyj do lang/{en,de,uk}/app.php.
 */
return [
    'nav' => [
        'dashboard' => 'Pulpit',
        'questions' => 'Baza pytań',
        'questions_short' => 'Pytania',
        'users' => 'Użytkownicy',
        'users_short' => 'Konta',
        'profile' => 'Profil',
        'logout' => 'Wyloguj',
        'exit_preview' => 'Wyjdź z podglądu',
    ],
    'theme' => [
        'label' => 'Motyw',
        'light' => 'Jasny',
        'dark' => 'Ciemny',
        'system' => 'System',
    ],
    'locale' => [
        'label' => 'Język',
        'locked_hint' => 'Egzamin w toku — języka nie można zmienić do jego zakończenia.',
        'locked_during_exam' => 'Nie można zmienić języka w trakcie egzaminu. Z jakim językiem zaczynasz, z takim kończysz.',
    ],
    'plan' => [
        'saved' => 'Zapisano datę egzaminu.',
        'removed' => 'Usunięto datę egzaminu.',
    ],
    'category' => [
        'saved' => 'Kategoria zapisana. Zacznij od diagnozy.',
        'title' => 'Wybierz kategorię',
        'badge' => 'Zacznij tutaj',
        'heading' => 'Na jaką kategorię zdajesz?',
        'lead' => 'Od tego zależy pula pytań, skład arkusza i cała prognoza szansy zdania. Wybierasz raz — później zmienisz to w profilu.',
        'submit' => 'Zapisz i przejdź dalej',
        'profile_label' => 'Kategoria prawa jazdy',
        'profile_hint' => 'Zmiana przestawia pulę pytań i przelicza szansę zdania od nowa. Postępy z innych kategorii nie znikają.',
    ],

    'admin' => [
        'user_created' => 'Utworzono konto :email (:role).',
    ],
    'attributes' => [
        'name' => 'imię i nazwisko',
        'email' => 'adres e-mail',
        'password' => 'hasło',
        'role' => 'rola',
        'driving_category_id' => 'kategoria prawa jazdy',
        'exam_date' => 'data egzaminu',
    ],
    'impersonation' => [
        'banner' => 'Podgląd konta :email',
        'read_only' => '— tylko do odczytu, zmiany są zablokowane',
        'exit' => 'Wyjdź z podglądu',
        'read_only_error' => 'Podgląd konta jest tylko do odczytu — wyjdź z podglądu, aby wprowadzać zmiany.',
        'started' => 'Podgląd konta :email. Zmiany są zablokowane.',
        'stopped' => 'Wyjście z podglądu — jesteś znów na swoim koncie.',
        'nested' => 'Jesteś już w podglądzie innego konta.',
        'self' => 'To Twoje własne konto.',
        'admin' => 'Nie można wejść w podgląd konta administratora.',
        'restore_failed' => 'Nie udało się wrócić na konto administratora — zaloguj się ponownie.',
    ],
    'admin_only' => 'Konto administratora służy do zarządzania — nauka i egzaminy są dostępne z kont kursantów.',
    'footer_rights' => '© :year :company. Wszelkie prawa zastrzeżone.',
];
