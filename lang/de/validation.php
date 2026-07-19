<?php

/*
|--------------------------------------------------------------------------
| Validierungsmeldungen — Deutsch
|--------------------------------------------------------------------------
|
| BEWUSST UNVOLLSTÄNDIG: übersetzt sind die Regeln, die diese Anwendung
| tatsächlich verwendet (required, email, unique, max, min, confirmed,
| integer, exists, date, lowercase, boolean, array, string, image, file)
| plus die Meldungen von Password::defaults().
|
| Für jede fehlende Regel greift Laravel per Schlüssel auf `fallback_locale`
| (Englisch) zurück — es entsteht also kein leerer Text, sondern höchstens
| eine englische Meldung bei einer Regel, die hier nie ausgelöst wird.
| Wird eine neue Regel eingeführt, gehört ihre Übersetzung hierher.
|
*/
return [
    'accepted' => ':attribute muss akzeptiert werden.',
    'after' => ':attribute muss ein Datum nach :date sein.',
    'array' => ':attribute muss eine Liste sein.',
    'before' => ':attribute muss ein Datum vor :date sein.',
    'boolean' => ':attribute muss true oder false sein.',
    'confirmed' => 'Die Bestätigung von :attribute stimmt nicht überein.',
    'date' => ':attribute ist kein gültiges Datum.',
    'email' => ':attribute muss eine gültige E-Mail-Adresse sein.',
    'exists' => 'Der gewählte Wert für :attribute ist ungültig.',
    'file' => ':attribute muss eine Datei sein.',
    'image' => ':attribute muss ein Bild sein.',
    'in' => 'Der gewählte Wert für :attribute ist ungültig.',
    'integer' => ':attribute muss eine ganze Zahl sein.',
    'lowercase' => ':attribute darf nur Kleinbuchstaben enthalten.',
    'max' => [
        'array' => ':attribute darf nicht mehr als :max Einträge haben.',
        'file' => ':attribute darf nicht größer als :max Kilobyte sein.',
        'numeric' => ':attribute darf nicht größer als :max sein.',
        'string' => ':attribute darf nicht länger als :max Zeichen sein.',
    ],
    'min' => [
        'array' => ':attribute muss mindestens :min Einträge haben.',
        'file' => ':attribute muss mindestens :min Kilobyte groß sein.',
        'numeric' => ':attribute muss mindestens :min sein.',
        'string' => ':attribute muss mindestens :min Zeichen lang sein.',
    ],
    'numeric' => ':attribute muss eine Zahl sein.',
    'required' => ':attribute ist erforderlich.',
    'string' => ':attribute muss ein Text sein.',
    'unique' => ':attribute ist bereits vergeben.',
    'url' => ':attribute muss eine gültige URL sein.',

    // Meldungen von Rules\Password (Password::defaults()).
    'password' => [
        'letters' => ':attribute muss mindestens einen Buchstaben enthalten.',
        'mixed' => ':attribute muss mindestens einen Groß- und einen Kleinbuchstaben enthalten.',
        'numbers' => ':attribute muss mindestens eine Ziffer enthalten.',
        'symbols' => ':attribute muss mindestens ein Sonderzeichen enthalten.',
        'uncompromised' => 'Das angegebene :attribute ist in einem Datenleck aufgetaucht. Bitte wähle ein anderes.',
    ],

    'custom' => [],
    'attributes' => [],
];
