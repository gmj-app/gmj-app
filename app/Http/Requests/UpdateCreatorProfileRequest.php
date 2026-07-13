<?php

namespace App\Http\Requests;

use App\Models\Creator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCreatorProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $creator = $this->route('creator');

        return [
            'display_name' => ['required', 'string', 'max:255'],
            'slug' => ['required', 'string', 'max:100', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/', Rule::unique('creators', 'slug')->ignore($creator?->id)],
            'youtube_channel_url' => ['nullable', 'url', 'max:2048'],
            'bio' => ['nullable', 'string', 'max:2000'],
            'submission_instructions' => ['nullable', 'string', 'max:2000'],
            'submissions_open' => ['required', 'boolean'],
            'recommendation_approval_mode' => ['required', Rule::in(Creator::RECOMMENDATION_APPROVAL_MODES)],
            'tags' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hero' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'remove_avatar' => ['nullable', 'boolean'],
            'remove_hero' => ['nullable', 'boolean'],
            'save_action' => ['nullable', Rule::in(['save', 'preview'])],
        ];
    }
}
