<?php

/** Gemeinsame Texte des Panels. Übersetzt aus lang/pl/app.php (Quelle). */
return [
    'nav' => [
        'dashboard' => 'Übersicht',
        'questions' => 'Fragenkatalog',
        'questions_short' => 'Fragen',
        'users' => 'Benutzer',
        'users_short' => 'Konten',
        'profile' => 'Profil',
        'logout' => 'Abmelden',
        'exit_preview' => 'Vorschau verlassen',
    ],
    'theme' => [
        'label' => 'Design',
        'light' => 'Hell',
        'dark' => 'Dunkel',
        'system' => 'System',
    ],
    'locale' => [
        'label' => 'Sprache',
        'locked_hint' => 'Eine Prüfung läuft — die Sprache kann bis zu ihrem Ende nicht geändert werden.',
        'locked_during_exam' => 'Während einer Prüfung lässt sich die Sprache nicht ändern. Du beendest sie in der Sprache, in der du begonnen hast.',
    ],
    'plan' => [
        'saved' => 'Prüfungstermin gespeichert.',
        'removed' => 'Prüfungstermin entfernt.',
    ],
    'category' => [
        'saved' => 'Klasse gespeichert. Beginne mit der Diagnose.',
        'title' => 'Klasse wählen',
        'badge' => 'Hier beginnen',
        'heading' => 'Für welche Klasse machst du die Prüfung?',
        'lead' => 'Davon hängen der Fragenpool, die Zusammensetzung des Bogens und die gesamte Prognose der Bestehenschance ab. Du wählst einmal — später änderst du es im Profil.',
        'submit' => 'Speichern und weiter',
        'profile_label' => 'Führerscheinklasse',
        'profile_hint' => 'Eine Änderung stellt den Fragenpool um und berechnet die Bestehenschance neu. Fortschritte in anderen Klassen gehen nicht verloren.',
    ],

    'admin' => [
        'user_created' => 'Konto :email angelegt (:role).',
    ],
    'attributes' => [
        'name' => 'Name',
        'email' => 'E-Mail-Adresse',
        'password' => 'Passwort',
        'role' => 'Rolle',
        'driving_category_id' => 'Führerscheinklasse',
        'exam_date' => 'Prüfungstermin',
    ],
    'impersonation' => [
        'banner' => 'Vorschau des Kontos :email',
        'read_only' => '— nur Lesezugriff, Änderungen sind gesperrt',
        'exit' => 'Vorschau verlassen',
        'read_only_error' => 'Die Kontovorschau ist schreibgeschützt — verlasse die Vorschau, um Änderungen vorzunehmen.',
        'started' => 'Vorschau des Kontos :email. Änderungen sind gesperrt.',
        'stopped' => 'Vorschau beendet — du bist wieder in deinem eigenen Konto.',
        'nested' => 'Du bist bereits in der Vorschau eines anderen Kontos.',
        'self' => 'Das ist dein eigenes Konto.',
        'admin' => 'Ein Administratorkonto kann nicht in der Vorschau geöffnet werden.',
        'restore_failed' => 'Rückkehr zum Administratorkonto fehlgeschlagen — bitte erneut anmelden.',
    ],
    'admin_only' => 'Das Administratorkonto dient der Verwaltung — Lernen und Prüfungen sind über Lernkonten möglich.',
    'footer_rights' => '© :year :company. Alle Rechte vorbehalten.',
];
