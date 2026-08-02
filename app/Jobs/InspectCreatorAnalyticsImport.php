<?php

namespace App\Jobs;

use App\Enums\ImportBatchStatus;
use App\Models\ImportBatch;
use App\Services\CreatorIntelligence\Import\AnalyticsFileInspector;
use App\Services\CreatorIntelligence\Import\ImportProcessingDispatcher;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class InspectCreatorAnalyticsImport implements ShouldQueue
{
    use Queueable;

    public function __construct(public int $batchId) {}

    public function handle(AnalyticsFileInspector $inspector, ImportProcessingDispatcher $dispatcher): void
    {
        $batch = ImportBatch::find($this->batchId);
        if (! $batch || ! in_array($batch->status, [ImportBatchStatus::Uploaded, ImportBatchStatus::Inspecting], true)) {
            return;
        }
        $batch->update(['status' => ImportBatchStatus::Inspecting, 'error_summary' => null]);
        try {
            $result = $inspector->inspect($batch);
            $ready = in_array('title', $result['mapping'], true);
            $batch->update(['detected_csv_filename' => $result['selected'], 'detected_columns' => $result['columns'], 'preview_rows' => $result['preview'], 'column_mapping' => $result['mapping'], 'status' => $ready ? ImportBatchStatus::Ready : ImportBatchStatus::AwaitingMapping]);
            if ($ready) {
                $dispatcher->dispatch($batch);
            }
        } catch (\Throwable $exception) {
            report($exception);
            $batch->update(['status' => ImportBatchStatus::Failed, 'error_summary' => $exception->getMessage(), 'completed_at' => now()]);
        }
    }
}
