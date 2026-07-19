<?php

/** “Where you lose points” screen. Translated from lang/pl/losses.php. */
return [
    'title' => 'Where you lose points',
    'lead' => 'Your chance of passing broken down into its parts — cat. :code.',
    'back' => 'Dashboard',

    'header' => [
        'expected' => 'Expected score',
        'short_by' => ':n pts short of the pass mark',
        'surplus' => ':n pts above the pass mark',
        'expected_loss' => 'Expected loss',
        'earned_lost' => 'earned / lost',
        'same_numbers' => 'The same numbers as “chance of passing” on the dashboard — just broken down by layer. Data coverage: :coverage%',
        'prior_warning' => '— the rest is the prior, not measurement.',
    ],

    'layers' => [
        'title' => 'Losses by layer of the paper',
        'lead' => 'The exam is :questions questions across :layers layers of differing weight. We rank by points lost, not by accuracy.',
        'biggest' => 'biggest loss',
        'on_sheet' => ':n questions on the paper',
        'pool_points' => ':n pts at stake',
        'chance' => 'chance :n',
        'coverage' => 'coverage :n',
        'your_accuracy' => 'your accuracy :n',
        'answers_count' => '(:n answers)',
    ],

    'insight' => [
        'title' => 'Do not chase the worst percentage',
        'body' => 'Your weakest layer is <strong>:weak</strong> (chance :weakChance), but it costs you only <strong class="tnum">:weakLoss pts</strong> — because that is just :weakPoints of the exam’s :max points. You lose the most in the <strong>:worst</strong> layer: <strong class="tnum">:worstLoss pts</strong>, because that is :worstPoints points at stake.',
        'covers_gap' => 'That layer alone covers your entire gap to the pass mark.',
    ],

    'traps' => [
        'title' => 'Losses by trap type',
        'lead' => 'How many points you are expected to give away on questions carrying each linguistic trap. A question can carry several at once, so these losses overlap — they are for comparing with each other, not for adding up.',
        'questions' => ':n questions',
        'accuracy' => '· your accuracy :acc (:n answers)',
    ],

    'calibration' => [
        'title' => 'Confidence calibration',
        'lead' => 'Whether your “I know” really means “I know” — from confidence ratings in practice mode.',
        'sure_right' => 'I know → right',
        'sure_right_note' => 'as it should be',
        'sure_wrong' => 'I know → wrong',
        'sure_wrong_note' => 'confident mistakes — the costliest',
        'guess_right' => 'Guessing → right',
        'guess_right_note' => 'lucky guesses',
        'guess_wrong' => 'Guessing → wrong',
        'guess_wrong_note' => 'honest “I don’t know”',
        'overconfidence' => '“I know” declarations that failed',
        'of_n' => 'of :n',
        'guess_vs_chance' => 'Guess accuracy vs chance level',
        'versus' => 'against :n',
        'sure_mistakes_title' => 'Your confident mistakes',
        'sure_mistakes_lead' => 'You were sure and you were wrong. On the exam nothing will stop you at these — start right here.',
    ],

    'start' => [
        'title' => 'Where to start',
        'lead' => 'The same calculation taken down to individual questions — the risk queue weights mistakes by points, so it already accounts for everything above.',
        'chance' => 'chance',
    ],

    'actions' => [
        'practice' => 'Study what costs you the most',
        'back' => 'Back to dashboard',
    ],
];
