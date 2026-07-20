<?php

namespace App\Enums;

/**
 * Tryb sesji rozwiązywania pytań.
 *
 *  - Practice (nauka)     → natychmiastowy feedback po każdym pytaniu.
 *  - Exam (egzamin)       → symulacja egzaminu państwowego, wynik na końcu, z limitem czasu.
 *  - Diagnosis (diagnoza) → onboarding: bez feedbacku i bez limitu, na końcu szacuje θ (EAP).
 */
enum ExamMode: string
{
    case Practice = 'practice';
    case Exam = 'exam';
    case Diagnosis = 'diagnosis';

    public function label(): string
    {
        return __('enums.exam_mode.'.$this->value);
    }

    /** Czy w tym trybie pokazujemy poprawność od razu po odpowiedzi. */
    public function givesInstantFeedback(): bool
    {
        return $this === self::Practice;
    }

    /**
     * Czy w tym trybie pytamy o deklarowaną pewność („wiem" / „strzelam").
     *
     * Tylko nauka. Egzamin próbny musi zostać 1:1 z arkuszem WORD — na prawdziwym
     * egzaminie nikt nie pyta o pewność. Diagnoza to kalibrowany instrument pomiaru
     * θ (EAP z całego wzorca odpowiedzi) i nie zmieniamy jej przebiegu.
     */
    public function collectsConfidence(): bool
    {
        return $this === self::Practice;
    }

    public function isDiagnosis(): bool
    {
        return $this === self::Diagnosis;
    }
}
