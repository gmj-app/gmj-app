<?php

namespace App\Console\Commands;

use App\Enums\ImportBatchStatus;
use App\Models\ImportBatch;
use App\Services\CreatorIntelligence\Import\ImportProcessingDispatcher;
use Illuminate\Console\Command;

class ProcessReadyCreatorIntelligenceImports extends Command
{
    protected $signature = 'creator-intelligence:process-ready-imports {--batch= : Dispatch only this import batch ID}';

    protected $description = 'Safely dispatch Creator Intelligence imports that are ready for processing';

    public function handle(ImportProcessingDispatcher $dispatcher): int
    {
        $query = ImportBatch::query()->where('status', ImportBatchStatus::Ready->value)->orderBy('id');
        if ($this->option('batch') !== null) {
            if (! ctype_digit((string) $this->option('batch')) || (int) $this->option('batch') < 1) {
                $this->error('The --batch option must be a positive batch ID.');

                return self::INVALID;
            }
            $query->whereKey((int) $this->option('batch'));
        }

        $dispatched = 0;
        $query->pluck('id')->each(function (int $batchId) use ($dispatcher, &$dispatched): void {
            if ($dispatcher->dispatch($batchId)) {
                $dispatched++;
                $this->line("Dispatched import batch #{$batchId}.");
            }
        });
        $this->info("Dispatched {$dispatched} ready import batch(es).");

        return self::SUCCESS;
    }
}
