<?php

namespace Tests\Feature;

use App\Models\Question;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImportQuestionBankTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Import jest idempotentny po `external_id` i nadpisuje istniejące rekordy —
     * ale NIE wolno mu skasować wyjaśnień wygenerowanych przez model. Wcześniej
     * `updateOrCreate` wpisywał `'explanation' => null` w payload aktualizacji,
     * więc każdy ponowny import (np. po nowym wydaniu arkusza od ministerstwa)
     * kasował wszystkie wyjaśnienia, zostawiając osierocone metadane.
     */
    public function test_pole_explanation_nie_jest_czescia_payloadu_aktualizacji(): void
    {
        $source = file_get_contents(app_path('Console/Commands/ImportQuestionBank.php'));

        $this->assertStringNotContainsString(
            "'explanation' => null",
            $source,
            'Import nie może zerować wyjaśnień przy ponownym przebiegu.'
        );
    }

    /** Model musi pozwalać na zapis wyjaśnienia niezależnie od importu. */
    public function test_wyjasnienie_przetrwa_aktualizacje_pytania_po_external_id(): void
    {
        $q = Question::create([
            'external_id' => '10816', 'type' => 'specialist', 'points' => 2,
            'text' => 'Stara treść', 'media_type' => 'none', 'is_active' => true,
            'source' => 'catalog', 'irt_b' => 0,
        ]);
        $q->forceFill([
            'explanation' => 'Wyjaśnienie wygenerowane przez model.',
            'explanation_source' => 'ai',
            'explanation_model' => 'claude-sonnet-5',
            'explanation_generated_at' => now(),
        ])->save();

        // Symulacja tego, co robi import: updateOrCreate po external_id.
        Question::updateOrCreate(
            ['external_id' => '10816'],
            ['type' => 'specialist', 'points' => 2, 'text' => 'Nowa treść z arkusza',
                'media_type' => 'none', 'is_active' => true, 'source' => 'catalog'],
        );

        $fresh = $q->fresh();

        $this->assertSame('Nowa treść z arkusza', $fresh->text, 'Treść ma się zaktualizować…');
        $this->assertSame('Wyjaśnienie wygenerowane przez model.', $fresh->explanation, '…ale wyjaśnienie ma przetrwać.');
        $this->assertSame('claude-sonnet-5', $fresh->explanation_model);
    }
}
