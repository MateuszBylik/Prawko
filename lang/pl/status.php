<?php

/*
|--------------------------------------------------------------------------
| Komunikaty stanu (session('status'))
|--------------------------------------------------------------------------
|
| Breeze przekazuje przez session('status') klucze techniczne (np.
| 'profile-updated'). Layout tłumaczy je na tekst dla użytkownika; klucz
| bez tłumaczenia nie zostanie wyświetlony (patrz layouts/app.blade.php).
|
*/

return [
    'profile-updated' => 'Zapisano zmiany w profilu.',
    'password-updated' => 'Hasło zostało zmienione.',
    'verification-link-sent' => 'Link weryfikacyjny został wysłany na Twój adres e-mail.',
];
