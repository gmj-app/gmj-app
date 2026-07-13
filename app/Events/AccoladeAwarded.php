<?php

namespace App\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AccoladeAwarded implements ShouldDispatchAfterCommit
{
    use Dispatchable, SerializesModels;

    /** @param array<string, mixed> $sourceContext */
    public function __construct(
        public readonly int $earnedAccoladeId,
        public readonly string $accoladeKey,
        public readonly int $userId,
        public readonly string $subjectType,
        public readonly int $subjectId,
        public readonly string $track,
        public readonly int $level,
        public readonly string $awardedAt,
        public readonly array $sourceContext = [],
    ) {}
}
