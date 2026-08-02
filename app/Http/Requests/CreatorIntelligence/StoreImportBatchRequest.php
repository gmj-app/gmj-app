<?php

namespace App\Http\Requests\CreatorIntelligence;

use App\Enums\ImportBatchSource;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreImportBatchRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('creator-intelligence.manage') === true;
    }

    public function rules(): array
    {
        return [
            'creator_channel_id' => ['required', 'integer', 'exists:creator_channels,id'],
            'source' => ['required', Rule::enum(ImportBatchSource::class)],
            'snapshot_date' => ['required', 'date'],
            'file' => ['required', 'file', 'max:'.config('creator_intelligence.max_upload_kilobytes'), 'extensions:csv,zip', 'mimetypes:text/plain,text/csv,application/csv,application/zip,application/x-zip-compressed,application/octet-stream'],
        ];
    }
}
