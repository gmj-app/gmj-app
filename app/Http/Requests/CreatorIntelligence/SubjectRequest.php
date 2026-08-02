<?php

namespace App\Http\Requests\CreatorIntelligence;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubjectRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('creator-intelligence.manage') === true;
    }

    protected function prepareForValidation(): void
    {
        $aliases = collect(preg_split('/[\r\n,]+/u', (string) $this->input('aliases')))->map(fn ($alias) => trim($alias))->filter()->unique()->values()->all();
        $this->merge(['aliases' => $aliases, 'is_active' => $this->boolean('is_active'), 'country_code' => filled($this->input('country_code')) ? strtoupper($this->input('country_code')) : null]);
    }

    public function rules(): array
    {
        return ['creator_channel_id' => ['required', 'integer', 'exists:creator_channels,id'], 'name' => ['required', 'string', 'max:255'], 'aliases' => ['array', 'max:100'], 'aliases.*' => ['string', 'max:255'], 'subject_type' => ['nullable', 'string', 'max:100'], 'country_code' => ['nullable', 'string', 'size:2', 'regex:/^[A-Z]{2}$/'], 'notes' => ['nullable', 'string', 'max:10000'], 'is_active' => ['required', 'boolean']];
    }

    public function after(): array
    {
        return [function (Validator $v) {
            $subject = $this->route('subject');
            if ($subject && $subject->creator_channel_id !== $this->integer('creator_channel_id') && ($subject->videos()->exists() || $subject->contentItems()->exists())) {
                $v->errors()->add('creator_channel_id', 'A related subject cannot be moved between channels.');
            }
        }];
    }
}
