<?php

namespace App\Services\Accolades;

use Illuminate\Support\Carbon;

final readonly class TrackMetric
{
    /** @param array<string, mixed> $metadata */
    public function __construct(
        public int $value,
        public array $metadata = [],
        public ?Carbon $evaluatedAt = null,
    ) {}
}
