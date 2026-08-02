<?php

namespace App\Http\Requests\CreatorIntelligence;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class CreatorChannelRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('creator-intelligence.manage') === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        $channel = $this->route('channel');

        return [
            'creator_profile_id' => ['required', 'integer', 'exists:creator_profiles,id'],
            'platform' => ['required', 'string', 'max:80'],
            'platform_channel_id' => ['nullable', 'string', 'max:255', Rule::unique('creator_channels')->where(fn ($query) => $query->where('platform', $this->input('platform')))->ignore($channel)],
            'handle' => ['nullable', 'string', 'max:255'],
            'channel_name' => ['required', 'string', 'max:255'],
            'subject_label' => ['required', 'string', 'max:80'],
            'content_item_label' => ['required', 'string', 'max:80'],
            'category_label' => ['required', 'string', 'max:80'],
            'default_publish_timezone' => ['required', 'string', Rule::in(timezone_identifiers_list())],
            'is_active' => ['required', 'boolean'],
        ];
    }
}
