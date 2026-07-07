<?php

namespace App\Http\Requests;

use App\Models\Creator;
use App\Models\User;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class PublicIdentityRequest extends FormRequest
{
    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'public_display_name' => [
                'required',
                'string',
                'min:2',
                'max:40',
                'not_regex:/[<>]/',
                'not_regex:/\b(?:https?:\/\/|www\.)/i',
            ],
            'public_handle' => [
                'required',
                'string',
                'min:3',
                'max:30',
                'regex:/\A[a-z0-9_-]+\z/',
                Rule::notIn(self::reservedHandles()),
                Rule::notIn(Creator::query()->pluck('slug')->all()),
                Rule::unique(User::class, 'public_handle')->ignore($this->user()?->id),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'public_display_name.not_regex' => 'Use a public display name without HTML or links.',
            'public_handle.regex' => 'Use 3 to 30 letters, numbers, underscores, or hyphens.',
            'public_handle.not_in' => 'That handle is reserved. Please choose another.',
            'public_handle.unique' => 'That handle is already taken.',
        ];
    }

    protected function prepareForValidation(): void
    {
        $displayName = trim((string) $this->input('public_display_name', ''));
        $displayName = preg_replace('/\s+/', ' ', $displayName) ?: $displayName;

        $handle = trim((string) $this->input('public_handle', ''));
        $handle = ltrim($handle, '@');
        $handle = strtolower($handle);

        $this->merge([
            'public_display_name' => $displayName,
            'public_handle' => $handle,
        ]);
    }

    /**
     * @return array<int, string>
     */
    public static function reservedHandles(): array
    {
        return [
            'admin',
            'administrator',
            'root',
            'support',
            'help',
            'login',
            'logout',
            'register',
            'dashboard',
            'profile',
            'settings',
            'faq',
            'about',
            'explore',
            'creator',
            'creators',
            'guide',
            'guides',
            'api',
            'auth',
            'internal',
            'beta-feedback',
            'feedback',
            'published',
            'recommendations',
            'submit',
            'jfragment',
        ];
    }
}
