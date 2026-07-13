<?php

namespace App\Services\Accolades;

use Illuminate\Support\Carbon;

final readonly class TrackMetric
{
    /** @param array<string, mixed> $metadata @param array<int, int|string> $qualifyingRecordIds */
    public function __construct(
        public int $value,
        public array $metadata = [],
        public ?Carbon $evaluatedAt = null,
        public array $qualifyingRecordIds = [],
    ) {}
}
