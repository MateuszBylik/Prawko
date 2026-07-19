<?php

namespace App\Http\Requests;

use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'string',
                'lowercase',
                'email',
                'max:255',
                Rule::unique(User::class)->ignore($this->user()->id),
            ],
            // Kategorię edytują tylko konta uczące się — admin jej nie ma i nie
            // pokazujemy mu tego pola, więc `sometimes` zamiast `required`.
            // `exists` ograniczone do aktywnych: nieaktywna kategoria nie ma pytań.
            'driving_category_id' => [
                'sometimes', 'required', 'integer',
                Rule::exists('driving_categories', 'id')->where('is_active', true),
            ],
        ];
    }
}
