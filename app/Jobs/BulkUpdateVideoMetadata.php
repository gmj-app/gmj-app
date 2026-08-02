<?php

namespace App\Jobs;

use App\Services\CreatorIntelligence\Metadata\MetadataBulkUpdateService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\Middleware\WithoutOverlapping;

class BulkUpdateVideoMetadata implements ShouldQueue
{
    use Queueable;

    public function __construct(public array $videoIds, public string $operation, public mixed $value, public string $mode, public int $userId) {}

    public function middleware(): array
    {
        return [(new WithoutOverlapping('metadata-bulk-'.$this->userId))->expireAfter(600)];
    }

    public function handle(MetadataBulkUpdateService $service): void
    {
        $service->apply($this->videoIds, $this->operation, $this->value, $this->mode, $this->userId);
    }
}
