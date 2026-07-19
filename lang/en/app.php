<?php

/** Shared panel strings. Translated from lang/pl/app.php (source of truth). */
return [
    'nav' => [
        'dashboard' => 'Dashboard',
        'questions' => 'Question bank',
        'questions_short' => 'Questions',
        'users' => 'Users',
        'users_short' => 'Accounts',
        'profile' => 'Profile',
        'logout' => 'Log out',
        'exit_preview' => 'Exit preview',
    ],
    'theme' => [
        'label' => 'Theme',
        'light' => 'Light',
        'dark' => 'Dark',
        'system' => 'System',
    ],
    'locale' => [
        'label' => 'Language',
        'locked_hint' => 'An exam is in progress — the language cannot be changed until it ends.',
        'locked_during_exam' => 'You cannot change the language during an exam. You finish in the language you started in.',
    ],
    'plan' => [
        'saved' => 'Exam date saved.',
        'removed' => 'Exam date removed.',
    ],
    'category' => [
        'saved' => 'Category saved. Start with the diagnosis.',
        'title' => 'Choose a category',
        'badge' => 'Start here',
        'heading' => 'Which category are you taking?',
        'lead' => 'It decides the question pool, the composition of the paper and your whole chance-of-passing forecast. You choose once — you can change it later in your profile.',
        'submit' => 'Save and continue',
        'profile_label' => 'Driving licence category',
        'profile_hint' => 'Changing it switches the question pool and recalculates your chance of passing from scratch. Progress in other categories is not lost.',
    ],

    'admin' => [
        'user_created' => 'Account :email created (:role).',
    ],
    'attributes' => [
        'name' => 'name',
        'email' => 'e-mail address',
        'password' => 'password',
        'role' => 'role',
        'driving_category_id' => 'driving licence category',
        'exam_date' => 'exam date',
    ],
    'impersonation' => [
        'banner' => 'Viewing account :email',
        'read_only' => '— read-only, changes are blocked',
        'exit' => 'Exit preview',
        'read_only_error' => 'Account preview is read-only — exit the preview to make changes.',
        'started' => 'Viewing account :email. Changes are blocked.',
        'stopped' => 'Preview closed — you are back on your own account.',
        'nested' => 'You are already previewing another account.',
        'self' => 'This is your own account.',
        'admin' => 'You cannot preview an administrator account.',
        'restore_failed' => 'Could not return to the administrator account — please log in again.',
    ],
    'admin_only' => 'The administrator account is for management — studying and exams are available from learner accounts.',
    'footer_rights' => '© :year :company. All rights reserved.',
];
