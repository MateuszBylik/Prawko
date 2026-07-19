<?php

/** Enum and DTO labels. Translated from lang/pl/enums.php (source of truth). */
return [
    'question_type' => [
        'basic' => 'Basic',
        'specialist' => 'Specialist',
    ],
    'answer' => [
        'yes' => 'Yes',
        'no' => 'No',
    ],

    'exam_mode' => [
        'practice' => 'Practice',
        'exam' => 'Mock exam',
        'diagnosis' => 'Diagnosis',
    ],
    'role' => [
        'student' => 'Learner',
        'instructor' => 'Instructor',
        'school_admin' => 'School administrator',
        'admin' => 'Administrator',
    ],
    'confidence' => [
        'sure' => 'I know',
        'guess' => 'Guessing',
    ],
    'trap' => [
        'negation' => ['label' => 'Negation', 'hint' => 'Mind the negative — the question asks what is NOT allowed, or what the exception is.'],
        'quantifier' => ['label' => 'Quantifier', 'hint' => 'Mind words like “always / never / only / every” — they are rarely true without exceptions.'],
        'modality' => ['label' => 'Permission', 'hint' => 'Tell obligation (“you must”) apart from permission (“you may, you are allowed”).'],
        'scope' => ['label' => 'Condition', 'hint' => 'The answer depends on a condition (“if / provided that / in the case of”).'],
        'number' => ['label' => 'Number', 'hint' => 'The exact value matters — do not confuse the threshold (metres, km/h, tonnes, ‰).'],
    ],
    'readiness' => [
        'ready' => 'Ready for the exam',
        'close' => 'Close to target',
        'learning' => 'Still learning',
        'start' => 'Just starting',
    ],
    'plan_status' => [
        'ready' => ['label' => 'You are ready', 'note' => 'Your chance of passing has cleared the target — keep it up with reviews.'],
        'on_track' => ['label' => 'On track', 'note' => 'Your pace is enough to make it in time. Keep the rhythm.'],
        'behind' => ['label' => 'Pick up the pace', 'note' => 'At your current pace you will not make it in time — increase the daily dose.'],
        'past' => ['label' => 'Date has passed', 'note' => 'Your exam date is in the past — set a new one if you want to keep planning.'],
        'no_date' => ['label' => 'Set your exam date', 'note' => 'Give us the date and we will work out your daily dose.'],
    ],
    'calibration' => [
        'no_data' => ['label' => 'Not enough data', 'note' => 'Rate your confidence in practice mode (“I know” / “Guessing”) and we will work out how much you can trust yourself.'],
        'overconfident' => ['label' => 'Overconfident', 'note' => 'Too often you are sure and wrong. These are the costliest mistakes — nothing warns you, so on the exam you will tick them without hesitating. Start with the list below.'],
        'underconfident' => ['label' => 'You know more than you think', 'note' => 'Your “guesses” land well above chance level — those are not guesses but knowledge you do not trust. Be bolder with “I know”.'],
        'good' => ['label' => 'Well calibrated', 'note' => 'Your declarations match your results — when you say you know, you usually do. That is a good sign: you can trust your own sense of certainty on the exam.'],
    ],
    'errors' => [
        'no_questions' => 'No active questions for the selected category.',
        'no_new_questions' => 'No new questions — you have already covered the whole pool for this category.',
        'no_category' => 'Choose a driving licence category before you start studying.',
        'pick_category' => 'Choose a driving licence category.',
    ],
];
