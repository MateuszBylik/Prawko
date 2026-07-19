<?php

/**
 * Startseite — Deutsch. Übersetzt aus lang/pl/landing.php (Quelle).
 *
 * „WORD" (Wojewódzki Ośrodek Ruchu Drogowego) ist die polnische Prüfstelle und hat
 * keine deutsche Entsprechung — daher umschrieben statt übersetzt.
 */
return [
    'nav' => [
        'login' => 'Anmelden',
        'dashboard' => 'Übersicht',
        'language' => 'Sprache',
    ],

    'type' => [
        'basic' => 'Grundfragen',
        'specialist' => 'Spezialfragen',
    ],

    'hero' => [
        'eyebrow' => 'Theorie · Klasse B und weitere',
        'title' => 'Arbeite den gesamten amtlichen Fragenkatalog durch, bis nichts mehr überrascht',
        'lead' => 'Dieselben Fragen wie in der staatlichen Prüfung — mit Videos und Fotos echter Verkehrssituationen. Die App berechnet deine tatsächliche Bestehenschance und sagt dir, was du zuerst lernen solltest.',
        'cta_primary' => 'Anmelden und lernen',
        'cta_dashboard' => 'Zur Übersicht',
        'cta_secondary' => 'So berechnen wir die Chance',
        'stats' => ':questions Fragen · :videos mit Video · Bestehensgrenze :threshold/:max',
        'sample_badge' => 'Vorschau',
        'sample_question' => 'Musst du in dieser Situation dem Fußgänger Vorrang gewähren?',
        'yes' => 'Ja',
        'no' => 'Nein',
    ],

    'engine' => [
        'eyebrow' => 'Die Engine',
        'title' => 'Keine „Prozent richtig". Deine Bestehenschance.',
        'p1' => '„80 % richtig" sagt nichts über die Prüfung aus. Der Bogen zieht :count Fragen mit unterschiedlicher Gewichtung, und zum Bestehen braucht es :threshold von :max Punkten — also etwa :percent %. Dieselben 80 % reichen mal aus und mal nicht, je nachdem, was gezogen wird.',
        'p2' => 'Deshalb zählen wir keine Prozente, sondern simulieren die Prüfung :simulations Mal. Jeder Durchlauf zieht einen Bogen nach der amtlichen Zusammensetzung und prüft, ob die Grenze erreicht wurde. Der Anteil erfolgreicher Versuche ist deine Bestehenschance.',
        'memory_title' => 'Ein Gedächtnismodell',
        'memory_desc' => 'Für jede Frage schätzen wir, ob du sie am Prüfungstag noch weißt. Die Wiederholung kommt genau dann, bevor du sie vergessen würdest.',
        'difficulty_title' => 'Schwierigkeit der Frage',
        'difficulty_desc' => 'Fragen unterscheiden sich in der Schwierigkeit — unabhängig von dir. Wir lernen beides zugleich: dein Niveau und das der Frage.',
        'time_title' => 'Zeit zählt auch',
        'time_desc' => 'In der Prüfung hast du Sekunden, nicht Minuten. Wenn eine Frage dich immer zu lange kostet, fließt das in die Rechnung ein.',
        'card_chance' => 'Bestehenschance',
        'card_readiness' => 'Nah am Ziel',
        'card_forecast' => 'Prognostiziertes Ergebnis',
        'card_range' => 'Bereich 58–70 Pkt.',
        'card_threshold' => 'Bestehensgrenze: :threshold',
        'card_note' => 'Beispielwert. Deiner wird aus deinen eigenen Antworten berechnet.',
    ],

    'how' => [
        'title' => 'So funktioniert es',
        'lead' => 'Von der ersten Messung bis zum Plan für deinen Prüfungstermin.',
        'diagnosis_title' => 'Mit einer Diagnose beginnen',
        'diagnosis_desc' => 'Vierzig Fragen, zusammengesetzt wie die echte Prüfung, ohne Zeitdruck. Danach weißt du, wo du stehst, statt zu raten.',
        'base_title' => 'Der gesamte amtliche Katalog',
        'base_desc' => 'Grundfragen (JA/NEIN) und Spezialfragen (A/B/C) mit Videos und Fotos — genau die aus dem Katalog des Infrastrukturministeriums.',
        'exam_title' => 'Prüfung wie die echte',
        'exam_desc' => ':count Fragen, ein eigenes Zeitlimit für jede, Bewertung 3/2/1 und eine Grenze von :threshold Punkten. Das Video läuft einmal, ohne Zurückspringen — wie in der Prüfstelle.',
        'reviews_title' => 'Wiederholungen im richtigen Moment',
        'reviews_desc' => 'Die App merkt sich, was dir schwerfällt, und legt dir diese Fragen genau dann wieder vor, bevor du sie vergessen würdest — nicht früher, nicht später.',
        'losses_title' => 'Wo du Punkte verlierst',
        'losses_desc' => 'Die reine Trefferquote täuscht, denn die Bereiche des Bogens wiegen unterschiedlich schwer. Wir zeigen, welche dich die meisten Punkte kosten.',
        'plan_title' => 'Plan für deinen Termin',
        'plan_desc' => 'Du nennst den Prüfungstermin und bekommst ein Tagespensum. Die App sagt klar, ob dein Tempo reicht.',
    ],

    'rules' => [
        'title' => 'So läuft die staatliche Prüfung ab',
        'lead' => 'Die Simulation in der App bildet diese Regeln ab.',
        'questions_label' => 'Fragen im Bogen',
        'questions_note' => ':basic Grundfragen + :specialist Spezialfragen',
        'points_label' => 'erreichbare Punkte',
        'points_note' => 'Fragen sind 3, 2 oder 1 Punkt wert',
        'threshold_label' => 'Punkte zum Bestehen',
        'threshold_note' => 'das sind etwa :percent % — der Spielraum ist knapp',
        'time_label' => 'Minuten insgesamt',
        'time_note' => 'Grundfragen: 20 s zum Lesen, 15 s zum Antworten',
        'sheet_title' => 'Zusammensetzung des Bogens',
        'sheet_lead' => 'So viele Fragen jeder Art zieht die Prüfung — und genau so zieht sie die Simulation.',
        'col_kind' => 'Art',
        'col_weight' => 'Gewicht',
        'col_count' => 'Fragen',
        'col_points' => 'Punkte',
        'total' => 'Gesamt',
        'points_short' => ':n Pkt.',
    ],

    'cta' => [
        'title' => 'Finde heraus, wie viel du wirklich kannst',
        'lead' => 'Konten werden vom Administrator angelegt. Wenn du Zugang hast — melde dich an und beginne mit der Diagnose. Falls nicht, schreib an :email.',
        'button' => 'Anmelden',
    ],

    'footer' => [
        'tagline' => 'Fahrschul-Theorie auf Basis des amtlichen Fragenkatalogs, mit realistischer Einschätzung der Bestehenschance.',
        'disclaimer' => 'Fragenkatalog: polnisches Ministerium für Infrastruktur (gov.pl). Dieser Dienst steht in keiner Verbindung zum Ministerium oder zu einer Prüfstelle.',
        'contact' => 'Kontakt',
        'company' => 'Firmendaten',
        'rights' => '© :year :company. Alle Rechte vorbehalten.',
    ],

    'meta' => [
        'title' => 'Prawko — Theorie für den Führerschein',
        'description' => 'Fahrschul-Theorie auf Basis des amtlichen Fragenkatalogs des polnischen Infrastrukturministeriums. 1:1-Prüfungssimulation und realistische Einschätzung der Bestehenschance.',
    ],
];
