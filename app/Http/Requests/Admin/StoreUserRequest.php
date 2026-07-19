<?php

namespace App\Http\Requests\Admin;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class StoreUserRequest extends FormRequest
{
    /** Dostęp pilnuje middleware `admin`; tu potwierdzamy autoryzację. */
    public function authorize(): bool
    {
        return (bool) $this->user()?->isAdmin();
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)],
            'password' => ['required', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::enum(UserRole::class)],
            // Wymagana dla kont uczących się; admin kategorii nie ma. Bez niej konto
            // wpadłoby przy pierwszym logowaniu na ekran wyboru — co jest poprawne,
            // ale skoro admin i tak wie, na co ktoś zdaje, lepiej ustawić od razu.
            'driving_category_id' => [
                Rule::requiredIf(fn () => $this->input('role') !== UserRole::Admin->value),
                'nullable', 'integer',
                Rule::exists('driving_categories', 'id')->where('is_active', true),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return __('app.attributes');
    }
}
