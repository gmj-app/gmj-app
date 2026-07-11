<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class HomepageAdvertisementRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isSuperAdmin() === true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge(['is_active' => $this->boolean('is_active')]);
    }

    public function rules(): array
    {
        return [
            'internal_name' => ['required', 'string', 'max:255'],
            'advertiser_name' => ['nullable', 'string', 'max:255'],
            'image' => [$this->route('advertisement') ? 'nullable' : 'required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
            'destination_url' => ['required', 'url:https', 'max:2048'],
            'alt_text' => ['required', 'string', 'max:255'],
            'cta_label' => ['nullable', 'string', 'max:80'],
            'placement' => ['required', 'integer', 'min:1'],
            'is_active' => ['required', 'boolean'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after:starts_at'],
        ];
    }
}
