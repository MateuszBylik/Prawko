<?php

/** Sitzungen: Lernen, Prüfung, Ergebnisse, Diagnose. Übersetzt aus lang/pl/session.php. */
return [
    'title' => ':mode · Frage :n',
    'progress' => ':n / :total',
    'category' => 'Kl. :code',
    'time_left' => 'Verbleibende Zeit',

    'new_badge' => 'neu',

    'feedback' => [
        'correct' => 'Richtig!',
        'wrong' => 'Nicht richtig.',
        'sure_mistake' => 'Sicherer Fehler',
        'lucky_guess' => 'Glückstreffer',
        'time' => 'Zeit: :n s',
        'correct_badge' => 'richtig',
        'your_choice' => 'Deine Wahl',
        'next' => 'Nächste Frage →',
    ],

    'confidence' => [
        'prompt' => 'Antwort prüfen — wie sicher bist du?',
        'hint' => 'Antworte ehrlich: Ein Glückstreffer zählt nicht als Wissen, deshalb kommt die Frage schneller zurück.',
    ],

    'actions' => [
        'check' => 'Antwort prüfen',
        'save_next' => 'Speichern und weiter →',
        'finish' => 'Sitzung beenden',
        'skip' => 'Frage überspringen',
        'prev' => '← Zurück',
        'confirm_finish' => 'Sitzung beenden und Ergebnis ansehen?',
    ],

    'navigator' => [
        'title' => 'Fragen',
    ],

    'exam' => [
        'badge' => 'Übungsprüfung',
        'answered' => ':done/:total beantwortet',
        'phase_reading' => 'Lesen',
        'phase_film' => 'Video',
        'phase_answer' => 'Antwort',
        'film_hint' => 'Videomaterial — klicke auf <strong class="text-white">Start</strong>, um es abzuspielen.',
        'film_once' => 'Das Video läuft nur einmal.',
        'playing' => 'Video läuft',
        'read_with_film' => 'Lies die Frage und spiele dann das Video ab. Danach hast du :n s zum Antworten.',
        'read_no_film' => 'Lies die Frage und gehe dann zur Antwort (:n s).',
        'start_film' => '▶ Start – Video abspielen',
        'go_answer' => 'Zur Antwort →',
        'film_playing' => 'Das Video läuft — du antwortest direkt nach dem Ende.',
        'finish' => 'Prüfung beenden',
        'next' => 'Nächste Frage →',
        'no_back' => 'Kein Zurück',
        'total_time_label' => 'Verbleibende Prüfungszeit',
        'points_label' => 'Punktwert',
        'category_label' => 'Klasse',
        'confirm_end' => 'Prüfung beenden? Wir werten die bisher gegebenen Antworten.',
        'basic_counter' => 'Grundfragen',
        'specialist_counter' => 'Spezialfragen',
        'read_time_label' => 'Zeit zum Lesen',
        'answer_time_label' => 'Zeit zum Antworten',
        'image_hint' => 'Foto zur Frage — tippe auf „Foto zeigen“.',
        'show_image' => 'Foto zeigen',
        'show_video' => 'Film zeigen',
    ],

    'result' => [
        'title' => 'Ergebnis der Sitzung',
        'threshold' => 'Bestehensgrenze: :n / :max Pkt.',
        'heading' => 'Ergebnis der Sitzung · :mode · Kl. :code',
        'title_short' => 'Ergebnis · :mode',
        'exam_passed' => 'Prüfung bestanden',
        'exam_failed' => 'Prüfung nicht bestanden',
        'score' => ':score / :max Pkt.',
        'correct_count' => 'richtig: :correct von :total',
        'again' => 'Noch einmal',
        'back' => 'Zurück zur Übersicht',
        'review' => 'Antworten durchsehen',
        'passed' => 'Bestanden',
        'failed' => 'Nicht bestanden',
    ],

    'review' => [
        'skipped' => '— keine (übersprungen)',
        'your_answer' => 'Deine Antwort:',
        'correct_answer' => 'Richtig:',
        'preview' => 'Frage und Antworten ansehen',
        'points' => ':n Pkt.',
    ],

    'diagnosis' => [
        'result_title' => 'Ergebnis der Diagnose',
        'title' => 'Diagnose abgeschlossen',
        'lead' => 'Das ist dein Ausgangspunkt — ab jetzt berechnet die App deine Bestehenschance.',
        'verdict_emerald' => 'Starkes Ergebnis — du bist in der Bestehenszone. Halte die Form mit Wiederholungen, damit du nicht aus dem Rhythmus fällst.',
        'verdict_brand' => 'Nah am Ziel. Ein paar Sitzungen mit den schwächsten Fragen und die Chance übersteigt die Bestehensgrenze.',
        'verdict_amber' => 'Solider Start, aber da geht noch was. Konzentriere dich auf die Fragen unten — sie wirken sich am stärksten auf das Ergebnis aus.',
        'verdict_rose' => 'Das ist erst der Anfang — und gut, dass du weißt, wo du anfangen sollst. Regelmäßiges Lernen hebt diesen Wert schnell.',
        'accuracy' => 'Trefferquote',
        'mistakes_title' => 'Deine Fehler',
        'mistakes_lead' => 'Du hattest <span class="font-semibold tnum text-slate-700 dark:text-slate-200">:wrong</span> von <span class="tnum">:total</span> Fragen falsch. Klappe „Erklären (KI)" auf, um den Grund zu erfahren.',
        'show_all' => 'Alle Fragen anzeigen',
        'show_mistakes' => 'Nur Fehler anzeigen',
        'start_title' => 'Wo anfangen',
        'start_lead' => 'Die Fragen mit dem größten Einfluss auf dein Ergebnis.',
        'start_practice' => 'Mit den schwächsten anfangen',
        'to_dashboard' => 'Zur Übersicht',
    ],

    'explanation' => [
        'title' => 'Erklärung',
        'ai_title' => 'KI-Erklärung',
        'generate' => 'Erklären (KI)',
        'generating' => 'Erklärung wird erstellt…',
        'error' => 'Die Erklärung konnte gerade nicht erstellt werden. Bitte versuche es erneut.',
    ],

    'twins' => [
        'difference' => 'Unterschied:',
        'analyse' => 'Worin unterscheiden sie sich? (KI)',
        'analysing' => 'Unterschied wird analysiert…',
        'error' => 'Der Unterschied konnte gerade nicht analysiert werden.',
    ],

    'media' => [
        'image_missing' => 'Foto im Quellkatalog nicht verfügbar',
        'video_missing' => 'Videomaterial im Quellkatalog nicht verfügbar',
        'no_video_support' => 'Dein Browser unterstützt die Videowiedergabe nicht.',
    ],

    'disputed' => [
        'title' => 'Strittige Frage — Fehler im Ministeriumskatalog',
        'lead' => 'Laut Katalog des Infrastrukturministeriums ist die richtige Antwort <strong class="text-amber-900 dark:text-amber-200">:answer</strong> — und die staatliche Prüfung wertet sie genauso. <strong>Kreuze in der Prüfung :answer an</strong>, trotz der Einwände unten.',
        'basis' => 'Rechtsgrundlage: :basis.',
    ],
];
