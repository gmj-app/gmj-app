<?php

namespace App\Http\Requests;

use App\Models\Recommendation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreStarterSuggestionsRequest extends FormRequest
{
    public function rules(): array
    {
        return [
            'suggestions' => ['nullable', 'array', 'max:20'],
            'suggestions.*' => ['array:title,url,category,note'],
            'suggestions.*.title' => [
                'nullable',
                'required_with:suggestions.*.url,suggestions.*.category,suggestions.*.note',
                'string',
                'max:255',
            ],
            'suggestions.*.url' => ['nullable', 'url', 'max:500'],
            'suggestions.*.category' => ['nullable', Rule::in(Recommendation::CATEGORY_OPTIONS)],
            'suggestions.*.note' => ['nullable', 'string', 'max:1000'],
        ];
    }

    /**
     * @return array<int, array{title: string, url: ?string, category: ?string, note: ?string}>
     */
    public function filledSuggestions(): array
    {
        return collect($this->validated('suggestions', []))
            ->map(fn (array $suggestion): array => [
                'title' => trim((string) ($suggestion['title'] ?? '')),
                'url' => filled($suggestion['url'] ?? null) ? trim((string) $suggestion['url']) : null,
                'category' => filled($suggestion['category'] ?? null) ? $suggestion['category'] : null,
                'note' => filled($suggestion['note'] ?? null) ? trim((string) $suggestion['note']) : null,
            ])
            ->filter(fn (array $suggestion): bool => collect($suggestion)->contains(fn ($value) => filled($value)))
            ->values()
            ->all();
    }
}
