<?php

namespace App\Http\Requests\CreatorIntelligence;

use App\Enums\VideoContentType;
use App\Enums\VideoCopyrightStatus;
use App\Enums\VideoFormat;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCreatorVideoRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('creator-intelligence.manage') === true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['is_premiere', 'is_live', 'is_short', 'is_documentary', 'is_interview'] as $field) {
            $this->merge([$field => $this->boolean($field)]);
        }
        $this->merge(['is_monetized' => $this->input('is_monetized') === '' || $this->input('is_monetized') === null ? null : $this->boolean('is_monetized')]);
    }

    public function rules(): array
    {
        return ['title' => ['required', 'string', 'max:255'], 'description' => ['nullable', 'string', 'max:100000'], 'video_url' => ['nullable', 'url', 'max:2048'], 'thumbnail_url' => ['nullable', 'url', 'max:2048'], 'published_at' => ['nullable', 'date'], 'duration_seconds' => ['nullable', 'integer', 'min:0'], 'video_format' => ['required', Rule::enum(VideoFormat::class)], 'content_type' => ['required', Rule::enum(VideoContentType::class)], 'is_premiere' => ['required', 'boolean'], 'is_live' => ['required', 'boolean'], 'is_short' => ['required', 'boolean'], 'is_documentary' => ['required', 'boolean'], 'is_interview' => ['required', 'boolean'], 'is_monetized' => ['nullable', 'boolean'], 'copyright_status' => ['required', Rule::enum(VideoCopyrightStatus::class)]];
    }
}
