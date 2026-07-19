<?php

/*
| Breeze передає через session('status') технічні ключі (напр. 'profile-updated').
| Макет їх перекладає; ключ без перекладу ніколи не показується
| (див. layouts/app.blade.php).
*/
return [
    'profile-updated' => 'Зміни у профілі збережено.',
    'password-updated' => 'Пароль змінено.',
    'verification-link-sent' => 'Посилання для підтвердження надіслано на твою адресу електронної пошти.',
];
