<?php

/**
 * Dane firmy — przepisane 1:1 ze stopki zalapto.pl (zweryfikowane w źródle strony).
 *
 * Trzymane w jednym miejscu, bo pojawiają się w kilku widokach, a NIP i adres to
 * dane, których nie wolno rozjechać między stronami.
 *
 * ŚWIADOMIE POMINIĘTY numer rachunku bankowego, który jest w stopce zalapto.pl:
 * w aplikacji do nauki nie ma za co płacić, więc nie pełniłby żadnej funkcji,
 * a opublikowany IBAN to zaproszenie dla podszywających się pod firmę.
 * Jeśli kiedyś pojawią się płatności — dodaj go świadomie, razem z regulaminem.
 */
return [
    'name' => 'ZałapTo',
    'legal_name' => 'ZałapTo Mateusz Bylica',
    'street' => 'ul. Heroldów 10B/53',
    'city' => '01-991 Warszawa',
    'nip' => '7011290100',
    'regon' => '543455540',
    'duns' => '369370289',
    'email' => 'kontakt@zalapto.pl',
    'phone' => '+48 22 247 26 19',
    // Do `tel:` — bez spacji, inaczej część telefonów nie wybierze numeru.
    'phone_tel' => '+48222472619',
    'hours' => 'pon – sb: 14:00 – 22:00',
    'website' => 'https://zalapto.pl',
];
