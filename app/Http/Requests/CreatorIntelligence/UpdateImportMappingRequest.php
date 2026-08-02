<?php

namespace App\Http\Requests\CreatorIntelligence;

use App\Enums\ImportBatchStatus;
use App\Services\CreatorIntelligence\Import\CsvColumnMapper;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class UpdateImportMappingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('creator-intelligence.manage') === true;
    }

    public function rules(): array
    {
        return ['mapping' => ['required', 'array'], 'mapping.*' => ['nullable', Rule::in(CsvColumnMapper::CANONICAL_FIELDS), 'distinct']];
    }

    public function after(): array
    {
        return [function (Validator $validator): void {
            $batch = $this->route('importBatch');
            if (! in_array($batch->status, [ImportBatchStatus::AwaitingMapping, ImportBatchStatus::Ready], true)) {
                $validator->errors()->add('mapping', 'Mapping cannot be changed after processing has started.');
            }
            $mapping = array_intersect_key($this->input('mapping', []), array_flip($batch->detected_columns ?? []));
            if (! in_array('title', $mapping, true)) {
                $validator->errors()->add('mapping', 'A source column must be mapped to Title.');
            }
        }];
    }

    public function validatedMapping(): array
    {
        return array_filter(array_intersect_key($this->validated('mapping'), array_flip($this->route('importBatch')->detected_columns ?? [])));
    }
}
