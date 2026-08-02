<?php

namespace App\Services\CreatorIntelligence\Import;

use App\Enums\ImportBatchStatus;
use App\Jobs\ProcessCreatorAnalyticsImport;
use App\Models\ImportBatch;
use Illuminate\Support\Facades\Log;

class ImportProcessingDispatcher
{
    public function dispatch(ImportBatch|int $batch): bool
    {
        $batchId = $batch instanceof ImportBatch ? $batch->id : $batch;
        $claimed = ImportBatch::query()->whereKey($batchId)
            ->where('status', ImportBatchStatus::Ready->value)
            ->update(['status' => ImportBatchStatus::Queued->value, 'updated_at' => now()]);

        if ($claimed !== 1) {
            return false;
        }

        try {
            ProcessCreatorAnalyticsImport::dispatch($batchId);
        } catch (\Throwable $exception) {
            ImportBatch::query()->whereKey($batchId)->where('status', ImportBatchStatus::Queued->value)
                ->update(['status' => ImportBatchStatus::Ready->value, 'error_summary' => 'Processing could not be queued. Try again after checking the queue connection.']);
            report($exception);
            Log::warning('Creator Intelligence import dispatch failed and was returned to ready.', ['import_batch_id' => $batchId, 'exception' => $exception::class]);

            return false;
        }

        Log::info('Creator Intelligence import processing dispatched.', ['import_batch_id' => $batchId]);

        return true;
    }
}
