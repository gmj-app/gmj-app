<?php

namespace App\Jobs;

use App\Enums\ImportBatchStatus;
use App\Models\ImportBatch;
use App\Services\CreatorIntelligence\Import\CreatorAnalyticsImporter;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class ProcessCreatorAnalyticsImport implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;

    public int $timeout = 1200;

    public function __construct(public int $batchId) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping('creator-import-'.$this->batchId))->releaseAfter(30)->expireAfter(1300)];
    }

    public function handle(CreatorAnalyticsImporter $importer): void
    {
        $batch = ImportBatch::find($this->batchId);
        if (! $batch || ! in_array($batch->status, [ImportBatchStatus::Queued, ImportBatchStatus::Processing], true)) {
            return;
        }
        $importer->import($batch);
    }
}
