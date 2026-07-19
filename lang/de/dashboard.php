<?php

/** Übersicht des Lernenden. Übersetzt aus lang/pl/dashboard.php (Quelle). */
return [
    'title' => 'Übersicht',
    'greeting' => 'Hallo, :name',
    'subtitle' => 'Lerne heute ein paar Fragen — in kleinen Schritten zur Prüfung.',

    'diagnosis' => [
        'badge' => 'Hier beginnen',
        'title' => 'Mach die Diagnose',
        'lead' => '40 Fragen, ohne Zeitdruck (~10 Min.). Daraus bestimmen wir dein Niveau und berechnen eine echte <strong>Bestehenschance</strong> — und sagen dir dann, was du zuerst lernen solltest.',
        'point_level' => 'Wir schätzen dein Niveau',
        'point_chance' => 'Wir berechnen die Bestehenschance',
        'point_plan' => 'Wir legen deinen Lernplan fest',
        'start' => 'Diagnose starten',
    ],

    'forecast' => [
        'chance' => 'Bestehenschance',
        'no_data' => 'Zu wenig Daten',
        'predicted' => 'Prognostiziertes Ergebnis',
        'points_short' => ':n Pkt.',
        'range' => 'Bereich :low–:high Pkt.',
        'threshold' => 'Bestehensgrenze: :n',
        'meta' => ':samples Prüfungssimulationen · Datenabdeckung: :coverage%',
        'losses_link' => 'Wo du Punkte verlierst',
        'not_enough' => 'Beantworte mindestens <strong>10 Fragen</strong>, damit die Engine deine Bestehenschance schätzen kann. Bisher hast du <strong class="tnum">:answered</strong>. Fang mit den Fragen unten an — sie wirken sich am stärksten auf das Ergebnis aus.',
    ],

    'resume' => [
        'text' => 'Du hast eine unbeendete Sitzung (:mode) — du kannst zu ihr zurück.',
        'button' => 'Sitzung fortsetzen',
    ],

    'plan' => [
        'title' => 'Prüfungsplan',
        'days' => 'Tage',
        'until' => 'bis :date',
        'chance' => 'Chance (Ziel :target%)',
        'coverage' => 'Abdeckung (:seen/:total)',
        'exam_date' => 'Prüfungstermin',
        'set_date' => 'Termin setzen',
        'dose_left' => 'Heute übrig: :n Fragen',
        'dose_done' => 'Heutiges Pensum geschafft',
        'dose_detail' => ':done/:target neue',
        'dose_reviews' => ':n Wiederholungen',
        'pace' => 'Dein Tempo: :pace/Tag',
        'pace_days' => '(Ø aus :n Tagen)',
        'start_dose' => 'Tägliche Übung',
        'keep_going' => 'Weiterlernen',
        'goal_note' => '<strong>Tägliche Übung</strong> mischt Wiederholungen mit ein paar neuen Fragen. Für die volle Abdeckung sind noch <span class="tnum font-semibold text-slate-600 dark:text-slate-300">:unseen</span> von :total Fragen offen, auf <span class="tnum">:days</span> — also :perday neue pro Tag. Schneller aufholen? <strong>Neue Fragen</strong> ist eine Sitzung nur aus ungesehenen Fragen. Die Bestehenschance (:target%) ist ein separates Bereitschaftsziel; volle Abdeckung ist keine Bedingung fürs Bestehen, nur der sicherste Weg dorthin.',
        'days_choice' => '{1} :n Tag|[2,*] :n Tage',
        'change_date' => 'Termin ändern oder entfernen',
        'save' => 'Speichern',
        'remove' => 'Termin entfernen',
    ],

    'start' => [
        'title' => 'Übung starten',
        'lead' => '<strong>Lernen</strong> gibt sofort Rückmeldung. Die <strong>Übungsprüfung</strong> simuliert den Test (:count Fragen, Zeitlimit).',
        'category' => 'Klasse :code',
        'change' => 'Ändern',
        'practice' => 'Lernen starten',
        'exam' => 'Übungsprüfung',
        'only_new' => 'Neue Fragen (:n)',
    ],

    'browse' => [
        'title' => 'Fragenkatalog',
        'lead' => 'Durchsuche alle Fragen mit Antworten, Videos und Fotos. Filtere nach Klasse und Typ.',
        'open' => 'Katalog öffnen',
    ],

    'risk' => [
        'title' => 'Was zuerst üben',
        'lead' => 'Fragen sortiert danach, wie viele Punkte du an ihnen voraussichtlich verlierst.',
        'practice_weakest' => 'Schwächste üben',
        'chance' => 'Chance',
    ],

    'stats' => [
        'title' => 'Deine Zahlen',
        'sessions' => 'abgeschlossene Sitzungen',
        'avg' => 'Durchschnittsergebnis',
        'exams' => 'bestandene Prüfungen',
        'answers' => 'Antworten',
        'mastered' => 'beherrschte Fragen',
        'due' => 'Wiederholungen für heute',
    ],

    'recent' => [
        'title' => 'Letzte Sitzungen',
        'empty' => 'Noch keine abgeschlossenen Sitzungen. Starte oben deine erste!',
        'passed' => 'bestanden',
        'failed' => 'nicht bestanden',
    ],

    'chart' => [
        'over_time' => 'Bestehenschance im Zeitverlauf',
        'measurements' => '{1} :n Messung, seit :date|[2,*] :n Messungen, seit :date',
        'growing' => 'Der Verlauf wächst, sobald du die Übersicht an weiteren Tagen besuchst',
        'empty' => 'Noch keine Messungen. Mach die Diagnose — der erste Punkt erscheint sofort.',
        'aria' => 'Bestehenschance im Zeitverlauf. Werte in der Tabelle unter dem Diagramm.',
        'coverage' => 'Abdeckung',
        'title' => 'Verlauf der Bestehenschance',
        'target' => 'Ziel',
        'pp' => 'Pp.',
        'table' => 'Als Tabelle anzeigen',
        'date' => 'Datum',
        'chance' => 'Chance',
    ],
];
