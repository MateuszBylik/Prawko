<?php

/**
 * Landing page — English. Translated from lang/pl/landing.php (source of truth).
 *
 * Polish-specific terms kept deliberately: WORD is the state examination centre
 * (Wojewódzki Ośrodek Ruchu Drogowego) and has no English equivalent, so it is
 * explained rather than translated away.
 */
return [
    'nav' => [
        'login' => 'Log in',
        'dashboard' => 'Dashboard',
        'language' => 'Language',
    ],

    'type' => [
        'basic' => 'Basic',
        'specialist' => 'Specialist',
    ],

    'hero' => [
        'eyebrow' => 'Theory · category B and others',
        'title' => 'Work through the whole official question bank until nothing surprises you',
        'lead' => 'The same questions as on the state exam — with videos and photos of real traffic situations. The app calculates your actual chance of passing and tells you what to study first.',
        'cta_primary' => 'Log in and study',
        'cta_dashboard' => 'Go to dashboard',
        'cta_secondary' => 'How we calculate your chance',
        'stats' => ':questions questions · :videos with video · pass mark :threshold/:max',
        'sample_badge' => 'preview',
        'sample_question' => 'In this situation, are you required to give way to the pedestrian?',
        'yes' => 'Yes',
        'no' => 'No',
    ],

    'engine' => [
        'eyebrow' => 'The engine',
        'title' => 'Not “percent correct”. Your chance of passing.',
        'p1' => '“80% correct” tells you nothing about the exam. The paper draws :count questions of differing weight, and passing takes :threshold out of :max points — roughly :percent%. The same 80% will sometimes be enough and sometimes not, depending on what you draw.',
        'p2' => 'So instead of counting percentages, we simulate the exam :simulations times. Each run draws a paper matching the official composition and checks whether it clears the pass mark. The share of successful attempts is your chance of passing.',
        'memory_title' => 'A memory model',
        'memory_desc' => 'For every question we estimate whether you will still remember it on exam day. Reviews come back right before you would forget.',
        'difficulty_title' => 'Question difficulty',
        'difficulty_desc' => 'Questions differ in difficulty regardless of you. We learn both at once: your level and the question’s level.',
        'time_title' => 'Time counts too',
        'time_desc' => 'On the exam you have seconds, not minutes. If a question always takes you too long, that enters the calculation.',
        'card_chance' => 'Chance of passing',
        'card_readiness' => 'Close to target',
        'card_forecast' => 'Forecast score',
        'card_range' => 'range 58–70 pts',
        'card_threshold' => 'pass mark: :threshold',
        'card_note' => 'Sample reading. Yours is calculated from your own answers.',
    ],

    'how' => [
        'title' => 'How it works',
        'lead' => 'From the first measurement to a plan built around your exam date.',
        'diagnosis_title' => 'Start with a diagnosis',
        'diagnosis_desc' => 'Forty questions composed like the real exam, with no time pressure. Afterwards you know where you stand instead of guessing.',
        'base_title' => 'The whole official bank',
        'base_desc' => 'Basic questions (YES/NO) and specialist ones (A/B/C) with videos and photos — exactly those from the Ministry of Infrastructure catalogue.',
        'exam_title' => 'An exam like the real one',
        'exam_desc' => ':count questions, a separate time limit for each, 3/2/1 scoring and a :threshold-point pass mark. The video plays once, with no going back — just like at the examination centre.',
        'reviews_title' => 'Reviews at the right moment',
        'reviews_desc' => 'The app remembers what gives you trouble and brings those questions back right before you would forget them — no sooner, no later.',
        'losses_title' => 'Where you lose points',
        'losses_desc' => 'Accuracy alone misleads, because the layers of the paper carry different weight. We show which ones cost you the most points.',
        'plan_title' => 'A plan for your date',
        'plan_desc' => 'Give us your exam date and get a daily dose. The app says plainly whether your current pace will get you there.',
    ],

    'rules' => [
        'title' => 'What the state exam looks like',
        'lead' => 'The simulation in the app mirrors these rules.',
        'questions_label' => 'questions on the paper',
        'questions_note' => ':basic basic + :specialist specialist',
        'points_label' => 'points available',
        'points_note' => 'questions are worth 3, 2 or 1 point',
        'threshold_label' => 'points needed to pass',
        'threshold_note' => 'that is about :percent% — the margin is narrow',
        'time_label' => 'minutes in total',
        'time_note' => 'basic questions: 20 s to read, 15 s to answer',
        'sheet_title' => 'Composition of the paper',
        'sheet_lead' => 'This is how many questions of each kind the exam draws — and exactly how the simulation draws them.',
        'col_kind' => 'Kind',
        'col_weight' => 'Weight',
        'col_count' => 'Questions',
        'col_points' => 'Points',
        'total' => 'Total',
        'points_short' => ':n pts',
    ],

    'cta' => [
        'title' => 'Find out how much you actually know',
        'lead' => 'Accounts are created by the administrator. If you have access — log in and start with the diagnosis. If not, write to :email.',
        'button' => 'Log in',
    ],

    'footer' => [
        'tagline' => 'Driving theory practice on the official question bank, with a realistic estimate of your chance of passing.',
        'disclaimer' => 'Question bank: Polish Ministry of Infrastructure (gov.pl). This service is not affiliated with the Ministry or with any examination centre.',
        'contact' => 'Contact',
        'company' => 'Company details',
        'rights' => '© :year :company. All rights reserved.',
    ],

    'meta' => [
        'title' => 'Prawko — driving theory practice',
        'description' => 'Driving theory practice on the official question bank of the Polish Ministry of Infrastructure. A 1:1 exam simulation and a realistic estimate of your chance of passing.',
    ],
];
