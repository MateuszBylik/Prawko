<?php

namespace App\Http\Requests;

use App\Enums\ExamMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StartSessionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        // `driving_category_id` CELOWO nie ma na tej liście — kategoria jest cechą
        // konta (users.driving_category_id), a nie czymś, co wybiera się przy każdym
        // starcie. Kontroler bierze ją z zalogowanego użytkownika, więc dosłanie
        // tego pola nic nie zmienia.
        return [
            'mode' => ['required', Rule::enum(ExamMode::class)],
            // Opcjonalna wielkość sesji nauki (dzienna dawka z planu).
            'size' => ['nullable', 'integer', 'min:1', 'max:100'],
            // Zawężenie sesji nauki: „new" = tylko nowe pytania; „express" = tryb
            // ekspresowy (pytania jeszcze nie utrwalone ani odpowiedziane poprawnie).
            'scope' => ['nullable', Rule::in(['new', 'express'])],
        ];
    }
}
