<?php

namespace App\Enums;

/**
 * Rodzaj materiału multimedialnego dołączonego do pytania.
 * Egzaminacyjne pytania podstawowe zawierają zwykle klip wideo,
 * a pytania specjalistyczne — zdjęcie sytuacji drogowej.
 */
enum MediaType: string
{
    case None = 'none';
    case Image = 'image';
    case Video = 'video';

    public function hasMedia(): bool
    {
        return $this !== self::None;
    }
}
