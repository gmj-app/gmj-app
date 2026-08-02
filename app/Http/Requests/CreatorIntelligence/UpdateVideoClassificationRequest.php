<?php

namespace App\Http\Requests\CreatorIntelligence;

use App\Enums\SubjectRelationshipType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateVideoClassificationRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge([
            'subjects' => array_values(array_filter($this->input('subjects', []), fn (array $row) => filled($row['id'] ?? null))),
            'content_items' => array_values(array_filter($this->input('content_items', []), fn (array $row) => filled($row['id'] ?? null))),
        ]);
    }

    public function authorize(): bool
    {
        return $this->user()?->can('creator-intelligence.manage') === true;
    }

    public function rules(): array
    {
        return ['subjects' => ['array'], 'subjects.*.id' => ['required', 'integer', 'distinct', 'exists:subjects,id'], 'subjects.*.relationship_type' => ['required', Rule::enum(SubjectRelationshipType::class)], 'subjects.*.is_primary' => ['boolean'], 'content_items' => ['array'], 'content_items.*.id' => ['required', 'integer', 'distinct', 'exists:content_items,id'], 'content_items.*.is_primary' => ['boolean']];
    }
}
