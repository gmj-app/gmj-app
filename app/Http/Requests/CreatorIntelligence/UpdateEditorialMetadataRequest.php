<?php

namespace App\Http\Requests\CreatorIntelligence;

use App\Enums\CreatorSentiment;
use App\Enums\MetadataScale;
use App\Enums\ReactionStyle;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateEditorialMetadataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('creator-intelligence.manage') === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['mark_reviewed' => $this->boolean('mark_reviewed')]);
    }

    public function rules(): array
    {
        return ['creator_sentiment' => ['nullable', Rule::enum(CreatorSentiment::class)], 'reaction_style' => ['nullable', Rule::enum(ReactionStyle::class)], 'energy_level' => ['nullable', Rule::enum(MetadataScale::class)], 'technical_depth' => ['nullable', Rule::enum(MetadataScale::class)], 'humor_level' => ['nullable', Rule::enum(MetadataScale::class)], 'cultural_context_level' => ['nullable', Rule::enum(MetadataScale::class)], 'production_notes' => ['nullable', 'string', 'max:10000'], 'mark_reviewed' => ['boolean']];
    }
}
