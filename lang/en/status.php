<?php

/*
| Breeze passes technical keys through session('status') (e.g. 'profile-updated').
| The layout translates them; a key without a translation is never shown
| (see layouts/app.blade.php).
*/
return [
    'profile-updated' => 'Profile changes saved.',
    'password-updated' => 'Your password has been changed.',
    'verification-link-sent' => 'A verification link has been sent to your e-mail address.',
];
