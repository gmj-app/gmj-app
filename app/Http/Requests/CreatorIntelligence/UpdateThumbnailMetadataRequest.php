<?php

namespace App\Http\Requests\CreatorIntelligence;

use App\Enums\CreatorExpression;
use App\Enums\ThumbnailBackgroundStyle;
use App\Enums\ThumbnailLayoutStyle;
use App\Enums\ThumbnailTextPosition;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateThumbnailMetadataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('creator-intelligence.manage') === true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['creator_face_visible', 'subject_face_visible', 'contains_question', 'contains_arrow', 'contains_circle', 'contains_flag', 'contains_logo', 'mark_reviewed'] as $f) {
            $this->merge([$f => $this->boolean($f)]);
        }
    }

    public function rules(): array
    {
        return ['thumbnail_version' => ['nullable', 'string', 'max:100'], 'text_content' => ['nullable', 'string', 'max:1000'], 'face_count' => ['nullable', 'integer', 'min:0'], 'creator_face_visible' => ['required', 'boolean'], 'subject_face_visible' => ['required', 'boolean'], 'creator_expression' => ['nullable', Rule::enum(CreatorExpression::class)], 'background_style' => ['nullable', Rule::enum(ThumbnailBackgroundStyle::class)], 'dominant_color_label' => ['nullable', 'string', 'max:100'], 'layout_style' => ['nullable', Rule::enum(ThumbnailLayoutStyle::class)], 'contains_question' => ['required', 'boolean'], 'contains_arrow' => ['required', 'boolean'], 'contains_circle' => ['required', 'boolean'], 'contains_flag' => ['required', 'boolean'], 'contains_logo' => ['required', 'boolean'], 'text_position' => ['nullable', Rule::enum(ThumbnailTextPosition::class)], 'notes' => ['nullable', 'string', 'max:10000'], 'mark_reviewed' => ['boolean']];
    }
}
