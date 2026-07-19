<?php

namespace App\Console\Commands;

use App\Enums\TrapType;
use App\Models\Question;
use App\Services\TrapTagger;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Taguje pytania typami pułapek (App\Enums\TrapType) na podstawie treści.
 * Deterministyczne — można puszczać wielokrotnie (nadpisuje kolumnę traps).
 */
class TagTraps extends Command
{
    protected $signature = 'prawko:tag-traps';

    protected $description = 'Taguje pytania typami pułapek (negacja/kwantyfikator/obowiązek/warunek/liczba).';

    public function handle(TrapTagger $tagger): int
    {
        $counts = array_fill_keys(array_map(fn (TrapType $t) => $t->value, TrapType::cases()), 0);
        $tagged = 0;
        $none = 0;

        Question::query()->select('id', 'text')->chunkById(500, function ($chunk) use ($tagger, &$counts, &$tagged, &$none) {
            foreach ($chunk as $q) {
                $tags = $tagger->tags($q->text);

                DB::table('questions')->where('id', $q->id)
                    ->update(['traps' => $tags ? json_encode(array_values($tags)) : null]);

                if ($tags) {
                    $tagged++;
                    foreach ($tags as $t) {
                        $counts[$t]++;
                    }
                } else {
                    $none++;
                }
            }
        });

        $this->info("Otagowano {$tagged} pytań (bez pułapek: {$none}).");
        foreach (TrapType::cases() as $t) {
            $this->line(sprintf('  %-20s %5d', $t->label(), $counts[$t->value]));
        }

        return self::SUCCESS;
    }
}
