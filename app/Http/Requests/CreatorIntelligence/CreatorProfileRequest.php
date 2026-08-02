<?php

namespace App\Http\Requests\CreatorIntelligence;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('creator-intelligence.manage') === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['default_currency' => strtoupper((string) $this->input('default_currency'))]);
    }

    public function rules(): array
    {
        $profile = $this->route('profile');

        return [
            'user_id' => ['nullable', 'integer', 'exists:users,id'],
            'display_name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:255', 'alpha_dash', Rule::unique('creator_profiles')->ignore($profile)],
            'timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'default_currency' => ['required', 'string', 'regex:/^[A-Z]{3}$/'],
        ];
    }
}
