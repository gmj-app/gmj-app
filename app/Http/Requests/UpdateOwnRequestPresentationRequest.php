<?php

namespace App\Http\Requests;

use App\Services\RequestIdentityComparator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Support\Facades\Log;

class UpdateOwnRequestPresentationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('updateOwnPresentation', $this->route('recommendation')) === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'display_title_override' => $this->clean($this->input('display_title_override'), true),
            'request_context' => $this->clean($this->input('request_context'), false),
        ]);
    }

    public function rules(): array
    {
        return [
            'display_title_override' => ['nullable', 'string', 'max:160', 'not_regex:/[\x00-\x1F\x7F]/u'],
            'request_context' => ['nullable', 'string', 'max:2000', 'not_regex:/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u'],
        ];
    }

    public function withValidator($validator): void
    {
        if (app(RequestIdentityComparator::class)->containsIdentityInput($this->except(['_token', '_method', 'display_title_override', 'request_context']))) {
            Log::warning('Rejected attempted Guide request identity mutation.', [
                'user_id' => $this->user()?->id,
                'recommendation_id' => $this->route('recommendation')?->id,
                'ip' => $this->ip(),
            ]);
            $validator->after(fn ($validator) => $validator->errors()->add('request_identity', 'The linked request content cannot be changed after submission.'));
        }
    }

    private function clean(mixed $value, bool $singleLine): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : ($singleLine ? preg_replace('/\s+/u', ' ', $value) : $value);
    }
}
