<?php

/** Bildschirm „Wo du Punkte verlierst". Übersetzt aus lang/pl/losses.php. */
return [
    'title' => 'Wo du Punkte verlierst',
    'lead' => 'Deine Bestehenschance in ihre Bestandteile zerlegt — Kl. :code.',
    'back' => 'Übersicht',

    'header' => [
        'expected' => 'Erwartetes Ergebnis',
        'short_by' => ':n Pkt. bis zur Bestehensgrenze',
        'surplus' => ':n Pkt. über der Bestehensgrenze',
        'expected_loss' => 'Erwarteter Verlust',
        'earned_lost' => 'erreicht / verloren',
        'same_numbers' => 'Dieselben Zahlen wie „Bestehenschance" in der Übersicht — nur nach Bereichen aufgeschlüsselt. Datenabdeckung: :coverage%',
        'prior_warning' => '— der Rest ist der Prior, keine Messung.',
    ],

    'layers' => [
        'title' => 'Verluste nach Bereich des Bogens',
        'lead' => 'Die Prüfung besteht aus :questions Fragen in :layers unterschiedlich gewichteten Bereichen. Wir sortieren nach verlorenen Punkten, nicht nach der Trefferquote.',
        'biggest' => 'größter Verlust',
        'on_sheet' => ':n Fragen im Bogen',
        'pool_points' => ':n Pkt. im Spiel',
        'chance' => 'Chance :n',
        'coverage' => 'Abdeckung :n',
        'your_accuracy' => 'deine Trefferquote :n',
        'answers_count' => '(:n Antworten)',
    ],

    'insight' => [
        'title' => 'Jage nicht der schlechtesten Prozentzahl hinterher',
        'body' => 'Am schwächsten ist der Bereich <strong>:weak</strong> (Chance :weakChance), er kostet dich aber nur <strong class="tnum">:weakLoss Pkt.</strong> — denn das sind lediglich :weakPoints von :max Punkten der Prüfung. Am meisten verlierst du im Bereich <strong>:worst</strong>: <strong class="tnum">:worstLoss Pkt.</strong>, denn dort stehen :worstPoints Punkte auf dem Spiel.',
        'covers_gap' => 'Allein dieser Bereich deckt deine gesamte Lücke zur Bestehensgrenze ab.',
    ],

    'traps' => [
        'title' => 'Verluste nach Fallentyp',
        'lead' => 'Wie viele Punkte du an Fragen mit der jeweiligen sprachlichen Falle voraussichtlich abgibst. Eine Frage kann mehrere zugleich enthalten, deshalb überlappen sich diese Verluste — sie dienen dem Vergleich untereinander, nicht dem Aufsummieren.',
        'questions' => ':n Fragen',
        'accuracy' => '· deine Trefferquote :acc (:n Antworten)',
    ],

    'calibration' => [
        'title' => 'Kalibrierung der Sicherheit',
        'lead' => 'Ob dein „Ich weiß" wirklich „Ich weiß" bedeutet — aus den Sicherheitsbewertungen im Lernmodus.',
        'sure_right' => 'Ich weiß → richtig',
        'sure_right_note' => 'so soll es sein',
        'sure_wrong' => 'Ich weiß → falsch',
        'sure_wrong_note' => 'sichere Fehler — die teuersten',
        'guess_right' => 'Geraten → richtig',
        'guess_right_note' => 'Glückstreffer',
        'guess_wrong' => 'Geraten → falsch',
        'guess_wrong_note' => 'ehrliches „weiß ich nicht"',
        'overconfidence' => '„Ich weiß"-Angaben, die danebenlagen',
        'of_n' => 'von :n',
        'guess_vs_chance' => 'Treffer beim Raten vs Zufallsniveau',
        'versus' => 'gegenüber :n',
        'sure_mistakes_title' => 'Deine sicheren Fehler',
        'sure_mistakes_lead' => 'Du warst sicher und lagst falsch. In der Prüfung hält dich bei diesen nichts auf — fang genau hier an.',
    ],

    'start' => [
        'title' => 'Wo anfangen',
        'lead' => 'Dieselbe Rechnung, heruntergebrochen auf einzelne Fragen — die Risikoliste gewichtet Fehler mit Punkten und berücksichtigt damit bereits alles oben Genannte.',
        'chance' => 'Chance',
    ],

    'actions' => [
        'practice' => 'Lerne das, was am meisten kostet',
        'back' => 'Zurück zur Übersicht',
    ],
];
