<?php

/*
| Breeze übergibt über session('status') technische Schlüssel (z. B. 'profile-updated').
| Das Layout übersetzt sie; ein Schlüssel ohne Übersetzung wird nie angezeigt
| (siehe layouts/app.blade.php).
*/
return [
    'profile-updated' => 'Profiländerungen gespeichert.',
    'password-updated' => 'Dein Passwort wurde geändert.',
    'verification-link-sent' => 'Ein Bestätigungslink wurde an deine E-Mail-Adresse gesendet.',
];
