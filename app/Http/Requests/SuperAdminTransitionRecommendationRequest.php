<?php

namespace App\Http\Requests;

use App\Models\Recommendation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SuperAdminTransitionRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::in(Recommendation::STATUSES)],
            'scheduled_for' => ['nullable', 'date'],
            'published_reaction_url' => [Rule::requiredIf($this->input('status') === 'published'), 'nullable', 'url', 'starts_with:https://', 'max:2048'],
            'published_at' => [Rule::requiredIf($this->input('status') === 'published'), 'nullable', 'date', 'after:2000-01-01', 'before:'.now()->addYear()->toDateString()],
            'reason' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
