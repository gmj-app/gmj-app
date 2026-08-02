<?php

namespace App\Http\Requests\CreatorIntelligence;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BulkUpdateVideoMetadataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('creator-intelligence.manage') === true;
    }

    public function rules(): array
    {
        return ['video_ids' => ['required', 'array', 'min:1', 'max:500'], 'video_ids.*' => ['integer', 'distinct', 'exists:creator_videos,id'], 'operation' => ['required', Rule::in(['assign_subject', 'assign_primary_subject', 'content_type', 'creator_sentiment', 'reaction_style', 'copyright_status', 'is_monetized', 'review_title', 'review_thumbnail', 'review_editorial'])], 'value' => ['nullable'], 'mode' => ['required', Rule::in(['fill', 'replace'])], 'confirmed' => ['accepted']];
    }
}
