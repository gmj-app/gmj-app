<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SuperAdminRemoveRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'moderation_reason' => ['required', Rule::in(['spam', 'harassment', 'malicious_link', 'duplicate', 'inappropriate', 'invalid', 'creator_request', 'other'])],
            'moderation_note' => [Rule::requiredIf($this->input('moderation_reason') === 'other'), 'nullable', 'string', 'max:1000'],
        ];
    }
}
