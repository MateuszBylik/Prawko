<?php

namespace App\Services;

use App\Enums\TrapType;

/**
 * Deterministyczne wykrywanie pułapek w treści pytania (bez AI) — dopasowanie
 * wzorców słów-kluczy typowych dla każdego rodzaju haczyka. Wzorce trzymamy tu,
 * żeby łatwo je dostrajać w jednym miejscu.
 */
class TrapTagger
{
    /**
     * Wzorce (regex, UTF-8, case-insensitive dzięki normalizacji) per typ pułapki.
     *
     * @var array<string, string>
     */
    private array $patterns;

    public function __construct()
    {
        $this->patterns = [
            TrapType::Negation->value => '/(?:^|\W)(?:nie|ani|bez)(?:\W|$)|z wyjątkiem|zabran|zakaz|niedozwolon|niezgodn/u',
            TrapType::Quantifier->value => '/\b(?:zawsze|nigdy|tylko|jedynie|wyłącznie|każd\w*|żaden|żadn\w*)\b/u',
            // Tylko strona „możliwości" — obowiązek („należy/musisz") to domyślne
            // sformułowanie większości pytań, więc nie jest rozróżniające.
            TrapType::Modality->value => '/\b(?:możesz|wolno|dozwol\w*|masz prawo|dopuszcza)\b/u',
            TrapType::Scope->value => '/\b(?:jeżeli|jeśli|o ile|chyba że|w przypadku)\b|pod warunkiem|z wyjątkiem/u',
            TrapType::Number->value => '/\d/u',
        ];
    }

    /**
     * Zwraca listę tagów pułapek wykrytych w tekście (wartości TrapType).
     *
     * @return string[]
     */
    public function tags(string $text): array
    {
        $normalized = ' '.mb_strtolower($text, 'UTF-8').' ';

        $tags = [];
        foreach ($this->patterns as $type => $pattern) {
            if (preg_match($pattern, $normalized) === 1) {
                $tags[] = $type;
            }
        }

        return $tags;
    }
}
