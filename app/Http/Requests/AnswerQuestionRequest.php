<?php

namespace App\Http\Requests;

use App\Enums\Confidence;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class AnswerQuestionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * answer_id jest opcjonalne — brak = pytanie pominięte (dozwolone na egzaminie).
     * confidence też — wysyła je tylko tryb nauki (patrz App\Enums\Confidence).
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'answer_id' => ['nullable', 'integer', 'exists:answers,id'],
            'time_spent_ms' => ['nullable', 'integer', 'min:0'],
            'confidence' => ['nullable', Rule::enum(Confidence::class)],
        ];
    }

    /** Zadeklarowana pewność albo null, gdy tryb jej nie zbiera. */
    public function confidence(): ?Confidence
    {
        return Confidence::tryFrom((string) $this->input('confidence'));
    }
}
