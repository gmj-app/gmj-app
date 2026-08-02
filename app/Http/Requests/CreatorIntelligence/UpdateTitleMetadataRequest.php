<?php

namespace App\Http\Requests\CreatorIntelligence;

use App\Enums\TitleTemplate;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateTitleMetadataRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('creator-intelligence.manage') === true;
    }

    protected function prepareForValidation(): void
    {
        foreach (['subject_name_present', 'content_item_name_present', 'negative_hook', 'curiosity_hook', 'emotional_hook', 'controversy_hook', 'technical_hook', 'discovery_hook'] as $f) {
            $this->merge([$f => $this->boolean($f)]);
        }$this->merge(['mark_reviewed' => $this->boolean('mark_reviewed')]);
    }

    public function rules(): array
    {
        return ['subject_name_present' => ['required', 'boolean'], 'content_item_name_present' => ['required', 'boolean'], 'negative_hook' => ['required', 'boolean'], 'curiosity_hook' => ['required', 'boolean'], 'emotional_hook' => ['required', 'boolean'], 'controversy_hook' => ['required', 'boolean'], 'technical_hook' => ['required', 'boolean'], 'discovery_hook' => ['required', 'boolean'], 'title_template' => ['nullable', Rule::enum(TitleTemplate::class)], 'notes' => ['nullable', 'string', 'max:10000'], 'mark_reviewed' => ['boolean'], 'recalculate_names' => ['nullable', 'boolean']];
    }
}
