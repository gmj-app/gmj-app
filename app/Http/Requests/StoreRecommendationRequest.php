<?php

namespace App\Http\Requests;

use App\Models\Recommendation;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRecommendationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'recommendation_type' => $this->input('recommendation_type', 'youtube'),
        ]);
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'recommendation_type' => ['required', Rule::in(['youtube', 'topic'])],
            'youtube_url' => ['nullable', 'required_if:recommendation_type,youtube', 'url'],
            'title' => ['required', 'string', 'max:255'],
            'artist' => ['nullable', 'string', 'max:255'],
            'channel_title' => ['nullable', 'string', 'max:255'],
            'category' => ['nullable', Rule::in(Recommendation::CATEGORY_OPTIONS)],
            'description' => ['nullable', 'required_if:recommendation_type,topic', 'string', 'max:1000'],
            'reason' => ['nullable', 'string', 'max:1000'],
            'confirm_favorite' => ['nullable', 'boolean'],
        ];
    }
}
