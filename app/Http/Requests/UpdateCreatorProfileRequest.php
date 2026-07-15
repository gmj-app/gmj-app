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

        $slugRules = ['required', 'string', 'max:100', Rule::unique('creators', 'slug')->ignore($creator?->id)];
        if (! $creator || $this->input('slug') !== $creator->slug) {
            $slugRules[] = 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/';
        }

        $submittedChannelUrl = $this->input('youtube_channel_url');
        $currentChannelUrl = $creator?->youtube_channel_url ?: $creator?->channel_url;
        $channelUrlRules = ['nullable', 'max:2048'];
        if (! $creator || $submittedChannelUrl !== $currentChannelUrl) {
            $channelUrlRules[] = 'url';
        }

        return [
            'display_name' => ['required', 'string', 'max:255'],
            // Unchanged legacy values must not prevent an administrator from saving
            // unrelated settings. New values still receive the current validation.
            'slug' => $slugRules,
            'youtube_channel_url' => $channelUrlRules,
            'bio' => ['nullable', 'string', 'max:2000'],
            'submission_instructions' => ['nullable', 'string', 'max:2000'],
            'submissions_open' => ['required', 'boolean'],
            'recommendation_approval_mode' => ['required', Rule::in(Creator::RECOMMENDATION_APPROVAL_MODES)],
            'tags' => ['nullable', 'string', 'max:1000'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'hero' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'save_action' => ['nullable', Rule::in(['save', 'preview'])],
        ];
    }
}
