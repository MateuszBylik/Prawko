<?php

/** Learner dashboard. Translated from lang/pl/dashboard.php (source of truth). */
return [
    'title' => 'Dashboard',
    'greeting' => 'Hi, :name',
    'subtitle' => 'Do a few questions today — small steps get you to the exam.',

    'diagnosis' => [
        'badge' => 'Start here',
        'title' => 'Take the diagnosis',
        'lead' => '40 questions, no time pressure (~10 min). From that we work out your level and calculate a real <strong>chance of passing</strong> — then tell you what to study first.',
        'point_level' => 'We estimate your level',
        'point_chance' => 'We calculate your chance of passing',
        'point_plan' => 'We set up your study plan',
        'start' => 'Start the diagnosis',
    ],

    'forecast' => [
        'chance' => 'Chance of passing',
        'no_data' => 'Not enough data',
        'predicted' => 'Forecast score',
        'points_short' => ':n pts',
        'range' => 'range :low–:high pts',
        'threshold' => 'pass mark: :n',
        'meta' => ':samples exam simulations · data coverage: :coverage%',
        'losses_link' => 'Where you lose points',
        'not_enough' => 'Answer at least <strong>10 questions</strong> so the engine can estimate your chance of passing. So far you have <strong class="tnum">:answered</strong>. Start with the questions below — they have the biggest impact on your score.',
    ],

    'resume' => [
        'text' => 'You have an unfinished session (:mode) — you can go back to it.',
        'button' => 'Resume session',
    ],

    'plan' => [
        'title' => 'Exam plan',
        'days' => 'days',
        'until' => 'until :date',
        'chance' => 'chance (target :target%)',
        'coverage' => 'coverage (:seen/:total)',
        'exam_date' => 'Exam date',
        'set_date' => 'Set date',
        'dose_left' => 'Left today: :n questions',
        'dose_done' => 'Today’s dose is done',
        'dose_detail' => ':done/:target new',
        'dose_reviews' => ':n reviews',
        'pace' => 'Your pace: :pace/day',
        'pace_days' => '(avg. over :n days)',
        'start_dose' => 'Daily practice',
        'keep_going' => 'Keep studying',
        'goal_note' => '<strong>Daily practice</strong> mixes reviews with a few new questions. For full coverage, <span class="tnum font-semibold text-slate-600 dark:text-slate-300">:unseen</span> of :total questions are still untouched, over <span class="tnum">:days</span> — that is :perday new per day. Want to catch up faster? <strong>New questions</strong> is a session of unseen questions only. The chance of passing (:target%) is a separate readiness target; full coverage is not a condition of passing, just the surest route to it.',
        'days_choice' => '{1} :n day|[2,*] :n days',
        'change_date' => 'Change or remove the date',
        'save' => 'Save',
        'remove' => 'Remove date',
    ],

    'start' => [
        'title' => 'Start practising',
        'lead' => '<strong>Practice</strong> gives feedback right away. A <strong>mock exam</strong> simulates the test (:count questions, time limit).',
        'category' => 'Category :code',
        'change' => 'Change',
        'practice' => 'Start practice',
        'exam' => 'Mock exam',
        'only_new' => 'New questions (:n)',
    ],

    'browse' => [
        'title' => 'Question bank',
        'lead' => 'Browse every question with answers, videos and photos. Filter by category and type.',
        'open' => 'Open the bank',
    ],

    'risk' => [
        'title' => 'What to practise first',
        'lead' => 'Questions ranked by how many points you are expected to lose on them.',
        'practice_weakest' => 'Practise the weakest',
        'chance' => 'chance',
    ],

    'stats' => [
        'title' => 'Your numbers',
        'sessions' => 'sessions completed',
        'avg' => 'average score',
        'exams' => 'exams passed',
        'answers' => 'answers',
        'mastered' => 'questions mastered',
        'due' => 'reviews due today',
    ],

    'recent' => [
        'title' => 'Recent sessions',
        'empty' => 'No completed sessions yet. Start your first one above!',
        'passed' => 'passed',
        'failed' => 'failed',
    ],

    'chart' => [
        'over_time' => 'Chance of passing over time',
        'measurements' => '{1} :n measurement, from :date|[2,*] :n measurements, from :date',
        'growing' => 'The chart will start to grow as you visit the dashboard on more days',
        'empty' => 'No measurements yet. Take the diagnosis — the first point appears right away.',
        'aria' => 'Chance of passing over time. Values in the table below the chart.',
        'coverage' => 'Coverage',
        'title' => 'Chance of passing over time',
        'target' => 'target',
        'pp' => 'pp',
        'table' => 'Show as a table',
        'date' => 'Date',
        'chance' => 'Chance',
    ],
];
