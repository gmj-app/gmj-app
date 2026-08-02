<?php

namespace App\Http\Requests\CreatorIntelligence;

use App\Models\Subject;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class ContentItemRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('creator-intelligence.manage') === true;
    }

    protected function prepareForValidation(): void
    {
        $aliases = collect(preg_split('/[\r\n,]+/u', (string) $this->input('aliases')))->map(fn ($alias) => trim($alias))->filter()->unique()->values()->all();
        $this->merge(['aliases' => $aliases, 'is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        return ['creator_channel_id' => ['required', 'integer', 'exists:creator_channels,id'], 'subject_id' => ['nullable', 'integer', 'exists:subjects,id'], 'name' => ['required', 'string', 'max:255'], 'aliases' => ['array', 'max:100'], 'aliases.*' => ['string', 'max:255'], 'content_item_type' => ['nullable', 'string', 'max:100'], 'release_date' => ['nullable', 'date'], 'notes' => ['nullable', 'string', 'max:10000'], 'is_active' => ['required', 'boolean']];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $item = $this->route('contentItem');
            if ($item && $item->creator_channel_id !== $this->integer('creator_channel_id') && $item->videos()->exists()) {
                $v->errors()->add('creator_channel_id', 'A related content item cannot be moved between channels.');
            }$subject = Subject::find($this->input('subject_id'));
            if ($subject && $subject->creator_channel_id !== $this->integer('creator_channel_id')) {
                $v->errors()->add('subject_id', 'The subject must belong to the selected channel.');
            }
        }];
    }
}
