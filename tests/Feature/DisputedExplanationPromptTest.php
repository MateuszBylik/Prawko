<?php

namespace Tests\Feature;

use App\Models\Answer;
use App\Models\Question;
use App\Services\ExplanationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * Prompt dla pytań spornych. Skonfabulowane wyjaśnienie do #10899 nie było fantazją
 * modelu — prompt dosłownie kazał mu „wyjaśnić, dlaczego poprawna odpowiedź jest
 * poprawna", więc postawiony przed odpowiedzią niezgodną z przepisami MUSIAŁ coś
 * wymyślić. Te testy pilnują, że takiego polecenia już nie dostaje.
 */
class DisputedExplanationPromptTest extends TestCase
{
    use RefreshDatabase;

    private function question(string $externalId): Question
    {
        $q = Question::create([
            'external_id' => $externalId, 'type' => 'specialist', 'points' => 2,
            'text' => 'Za które naruszenie przepisów policjant zatrzyma prawo jazdy?',
            'media_type' => 'none', 'is_active' => true, 'source' => 'catalog', 'irt_b' => 0,
        ]);
        Answer::create(['question_id' => $q->id, 'text' => 'Za wyprzedzanie…', 'is_correct' => false, 'label' => 'A', 'position' => 1]);
        Answer::create(['question_id' => $q->id, 'text' => 'Za przewożenie osób…o dwa…', 'is_correct' => true, 'label' => 'B', 'position' => 2]);
        Answer::create(['question_id' => $q->id, 'text' => 'Za wymijanie…', 'is_correct' => false, 'label' => 'C', 'position' => 3]);

        return $q->load('answers');
    }

    private function prompt(Question $q): string
    {
        $method = new ReflectionMethod(ExplanationService::class, 'userPrompt');

        return $method->invoke(
            app(ExplanationService::class),
            $q,
            $q->answers->firstWhere('is_correct', true),
            null,
            false
        );
    }

    public function test_prompt_spornego_pytania_nie_kaze_uzasadniac_odpowiedzi_katalogowej(): void
    {
        $prompt = $this->prompt($this->question('10898'));

        // To zdanie zrodziło halucynację — nie może paść przy pytaniu spornym.
        $this->assertStringNotContainsString('Wyjaśnij zwięźle, dlaczego poprawna odpowiedź jest poprawna', $prompt);
        $this->assertStringNotContainsString('Poprawna odpowiedź:', $prompt, 'Nie wolno nazywać tej odpowiedzi poprawną.');

        $this->assertStringContainsString('PYTANIE SPORNE', $prompt);
        $this->assertStringContainsString('NIEZGODNA Z PRZEPISAMI', $prompt);
        $this->assertStringContainsString('art. 135', $prompt, 'Model dostaje gotową podstawę prawną, zamiast jej szukać.');
        $this->assertStringContainsString('nie twórz własnej interpretacji przepisu', $prompt);
    }

    public function test_zwykle_pytanie_ma_prompt_bez_zmian(): void
    {
        $prompt = $this->prompt($this->question('99999'));

        $this->assertStringContainsString('Poprawna odpowiedź:', $prompt);
        $this->assertStringContainsString('Wyjaśnij zwięźle, dlaczego poprawna odpowiedź jest poprawna', $prompt);
        $this->assertStringNotContainsString('PYTANIE SPORNE', $prompt);
    }

    /**
     * Samoleczenie: dopisanie pytania do `prawko.disputed` musi unieważnić wyjaśnienie
     * wygenerowane wcześniej — inaczej stara, szkodliwa treść wisiałaby w nieskończoność,
     * bo `explain()` zwraca cache'owaną wartość bez pytania modelu.
     */
    public function test_stare_wyjasnienie_spornego_pytania_nie_jest_serwowane(): void
    {
        $q = $this->question('10898');
        $q->forceFill([
            'explanation' => 'Policjant zatrzymuje prawo jazdy za przewożenie zbyt wielu osób.',
            'explanation_source' => 'ai',
            'explanation_model' => 'claude-sonnet-5',
            'explanation_generated_at' => now(),
        ])->save();

        // Bez klucza API nie ma czym zastąpić — ale szkodliwej treści i tak nie podajemy.
        config(['services.anthropic.key' => null]);

        $this->assertNull(
            app(ExplanationService::class)->explain($q->fresh()),
            'Wyjaśnienie sprzed oznaczenia pytania jako sporne nie może trafić do użytkownika.'
        );
    }

    /** Wyjaśnienie wygenerowane JUŻ z wytycznymi jest serwowane z cache, bez ponownego pytania modelu. */
    public function test_wyjasnienie_z_wytycznymi_jest_cache_owane(): void
    {
        $q = $this->question('10898');
        $q->forceFill([
            'explanation' => 'Katalog i egzamin oczekują odpowiedzi B, choć jest niezgodna z art. 135 ust. 2.',
            'explanation_source' => 'ai-disputed',
            'explanation_model' => 'claude-sonnet-5',
            'explanation_generated_at' => now(),
        ])->save();

        config(['services.anthropic.key' => null]);

        $this->assertStringContainsString(
            'Katalog i egzamin oczekują odpowiedzi B',
            app(ExplanationService::class)->explain($q->fresh())
        );
    }

    /** Tekst pochodzący ze źródła (nie od modelu) nie jest przez nas unieważniany. */
    public function test_wyjasnienie_ze_zrodla_nie_jest_uniewazniane(): void
    {
        $q = $this->question('10899');
        $q->forceFill([
            'explanation' => 'Tekst z katalogu źródłowego.',
            'explanation_source' => 'source',
        ])->save();

        config(['services.anthropic.key' => null]);

        $this->assertSame('Tekst z katalogu źródłowego.', app(ExplanationService::class)->explain($q->fresh()));
    }
}
