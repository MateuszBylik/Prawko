<?php

/** Beschriftungen von Enums und DTOs. Übersetzt aus lang/pl/enums.php (Quelle). */
return [
    'question_type' => [
        'basic' => 'Grundfragen',
        'specialist' => 'Spezialfragen',
    ],
    'answer' => [
        'yes' => 'Ja',
        'no' => 'Nein',
    ],

    'exam_mode' => [
        'practice' => 'Lernen',
        'exam' => 'Übungsprüfung',
        'diagnosis' => 'Diagnose',
    ],
    'role' => [
        'student' => 'Fahrschüler',
        'instructor' => 'Fahrlehrer',
        'school_admin' => 'Fahrschul-Administrator',
        'admin' => 'Administrator',
    ],
    'confidence' => [
        'sure' => 'Ich weiß',
        'guess' => 'Geraten',
    ],
    'trap' => [
        'negation' => ['label' => 'Verneinung', 'hint' => 'Achte auf die Verneinung — gefragt ist, was NICHT erlaubt ist oder was die Ausnahme bildet.'],
        'quantifier' => ['label' => 'Quantor', 'hint' => 'Achte auf „immer / nie / nur / jeder" — das stimmt selten ohne Ausnahme.'],
        'modality' => ['label' => 'Erlaubnis', 'hint' => 'Unterscheide Pflicht („du musst") von Erlaubnis („du darfst").'],
        'scope' => ['label' => 'Bedingung', 'hint' => 'Die Antwort hängt von einer Bedingung ab („wenn / sofern / im Falle von").'],
        'number' => ['label' => 'Zahl', 'hint' => 'Der genaue Wert zählt — verwechsle den Schwellenwert nicht (Meter, km/h, Tonnen, ‰).'],
    ],
    'readiness' => [
        'ready' => 'Bereit für die Prüfung',
        'close' => 'Nah am Ziel',
        'learning' => 'Noch am Lernen',
        'start' => 'Ganz am Anfang',
    ],
    'plan_status' => [
        'ready' => ['label' => 'Du bist bereit', 'note' => 'Deine Bestehenschance liegt über dem Ziel — halte sie mit Wiederholungen.'],
        'on_track' => ['label' => 'Auf gutem Weg', 'note' => 'Dein Tempo reicht, um rechtzeitig fertig zu werden. Bleib im Rhythmus.'],
        'behind' => ['label' => 'Tempo erhöhen', 'note' => 'Mit dem aktuellen Tempo schaffst du es nicht rechtzeitig — erhöhe das Tagespensum.'],
        'past' => ['label' => 'Termin ist vorbei', 'note' => 'Dein Prüfungstermin liegt in der Vergangenheit — setze einen neuen, wenn du weiterplanen willst.'],
        'no_date' => ['label' => 'Prüfungstermin festlegen', 'note' => 'Nenne den Termin und wir bestimmen dein Tagespensum.'],
    ],
    'calibration' => [
        'no_data' => ['label' => 'Zu wenig Daten', 'note' => 'Bewerte deine Sicherheit im Lernmodus („Ich weiß" / „Geraten"), dann berechnen wir, wie sehr du dir trauen kannst.'],
        'overconfident' => ['label' => 'Zu selbstsicher', 'note' => 'Zu oft bist du sicher und liegst falsch. Das sind die teuersten Fehler — nichts warnt dich davor, in der Prüfung kreuzt du sie ohne Zögern an. Fang mit der Liste unten an.'],
        'underconfident' => ['label' => 'Du weißt mehr, als du denkst', 'note' => 'Deine „Rateversuche" liegen deutlich über dem Zufallsniveau — das ist kein Raten, sondern Wissen, dem du nicht traust. Sag ruhig öfter „Ich weiß".'],
        'good' => ['label' => 'Gut kalibriert', 'note' => 'Deine Einschätzung deckt sich mit den Ergebnissen — wenn du sagst, du weißt es, dann weißt du es meist auch. Ein gutes Zeichen: Du kannst deinem Gefühl in der Prüfung trauen.'],
    ],
    'errors' => [
        'no_questions' => 'Keine aktiven Fragen für die gewählte Klasse.',
        'no_new_questions' => 'Keine neuen Fragen — du hast den gesamten Fragenpool dieser Klasse bereits bearbeitet.',
        'no_category' => 'Wähle eine Führerscheinklasse, bevor du mit dem Lernen beginnst.',
        'pick_category' => 'Wähle eine Führerscheinklasse.',
    ],
];
