<?php

namespace App\Http\Requests;

use App\Models\Recommendation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class SuperAdminUpdateRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'], 'youtube_url' => ['nullable', 'url:http,https', 'max:2048'],
            'artist' => ['nullable', 'string', 'max:255'], 'channel_title' => ['nullable', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'], 'reason' => ['nullable', 'string', 'max:2000'],
            'category' => ['nullable', Rule::in(Recommendation::CATEGORY_OPTIONS)], 'tags' => ['nullable', 'string', 'max:300'],
            'updated_at' => ['required', 'date'],
        ];
    }
}
