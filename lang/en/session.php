<?php

/** Sessions: practice, exam, results, diagnosis. Translated from lang/pl/session.php. */
return [
    'title' => ':mode · question :n',
    'progress' => ':n / :total',
    'category' => 'cat. :code',
    'time_left' => 'Time left',

    'new_badge' => 'new',

    'feedback' => [
        'correct' => 'Correct!',
        'wrong' => 'Not correct.',
        'sure_mistake' => 'Confident mistake',
        'lucky_guess' => 'Lucky guess',
        'time' => 'time: :n s',
        'correct_badge' => 'correct',
        'your_choice' => 'Your choice',
        'next' => 'Next question →',
    ],

    'confidence' => [
        'prompt' => 'Check your answer — how sure are you?',
        'hint' => 'Answer honestly: a lucky guess does not count as knowledge, so the question will come back to you sooner.',
    ],

    'actions' => [
        'check' => 'Check answer',
        'save_next' => 'Save and continue →',
        'finish' => 'Finish session',
        'skip' => 'Skip question',
        'prev' => '← Previous',
        'confirm_finish' => 'Finish the session and see your result?',
    ],

    'navigator' => [
        'title' => 'Questions',
    ],

    'exam' => [
        'badge' => 'Mock exam',
        'answered' => ':done/:total answered',
        'phase_reading' => 'Reading',
        'phase_film' => 'Video',
        'phase_answer' => 'Answer',
        'film_hint' => 'Video material — click <strong class="text-white">Start</strong> to play it.',
        'film_once' => 'The video plays only once.',
        'playing' => 'video playing',
        'read_with_film' => 'Read the question, then play the video. After it you have :n s to answer.',
        'read_no_film' => 'Read the question, then move on to the answer (:n s).',
        'start_film' => '▶ Start – play the video',
        'go_answer' => 'Go to the answer →',
        'film_playing' => 'The video is playing — you answer right after it ends.',
        'finish' => 'Finish the exam',
        'next' => 'Next question →',
        'no_back' => 'No going back',
    ],

    'result' => [
        'title' => 'Session result',
        'threshold' => 'Pass mark: :n / :max pts',
        'heading' => 'Session result · :mode · cat. :code',
        'title_short' => 'Result · :mode',
        'exam_passed' => 'Exam passed',
        'exam_failed' => 'Exam failed',
        'score' => ':score / :max pts',
        'correct_count' => 'correct: :correct of :total',
        'again' => 'Again',
        'back' => 'Back to dashboard',
        'review' => 'Answer review',
        'passed' => 'Passed',
        'failed' => 'Failed',
    ],

    'review' => [
        'skipped' => '— none (skipped)',
        'your_answer' => 'Your answer:',
        'correct_answer' => 'Correct:',
        'preview' => 'Preview the question and answers',
        'points' => ':n pts',
    ],

    'diagnosis' => [
        'result_title' => 'Diagnosis result',
        'title' => 'Diagnosis complete',
        'lead' => 'This is your starting point — from now on the app calculates your chance of passing.',
        'verdict_emerald' => 'Great result — you are in the passing zone. Keep it up with reviews so you do not fall out of rhythm.',
        'verdict_brand' => 'Close to target. A few sessions on your weakest questions and your chance will clear the pass mark.',
        'verdict_amber' => 'A solid start, but there is room to improve. Focus on the questions below — they have the biggest impact on your score.',
        'verdict_rose' => 'This is only the beginning — and it is good that you know where to start. Regular study will lift this number quickly.',
        'accuracy' => 'accuracy',
        'mistakes_title' => 'Your mistakes',
        'mistakes_lead' => 'You got <span class="font-semibold tnum text-slate-700 dark:text-slate-200">:wrong</span> of <span class="tnum">:total</span> questions wrong. Expand “Explain (AI)” to find out why.',
        'show_all' => 'Show all questions',
        'show_mistakes' => 'Show mistakes only',
        'start_title' => 'Where to start',
        'start_lead' => 'The questions with the biggest impact on your score.',
        'start_practice' => 'Start with the weakest',
        'to_dashboard' => 'Go to dashboard',
    ],

    'explanation' => [
        'title' => 'Explanation',
        'ai_title' => 'AI explanation',
        'generate' => 'Explain (AI)',
        'generating' => 'Generating an explanation…',
        'error' => 'Could not generate an explanation right now. Please try again.',
    ],

    'twins' => [
        'difference' => 'Difference:',
        'analyse' => 'How do they differ? (AI)',
        'analysing' => 'Analysing the difference…',
        'error' => 'Could not analyse the difference right now.',
    ],

    'media' => [
        'image_missing' => 'Photo unavailable in the source bank',
        'video_missing' => 'Video unavailable in the source bank',
        'no_video_support' => 'Your browser does not support video playback.',
    ],

    'disputed' => [
        'title' => 'Disputed question — a flaw in the ministry catalogue',
        'lead' => 'According to the Ministry of Infrastructure catalogue the correct answer is <strong class="text-amber-900 dark:text-amber-200">:answer</strong> — and the state exam scores it the same way. <strong>On the exam, tick :answer</strong>, despite the objections below.',
        'basis' => 'Legal basis: :basis.',
    ],
];
