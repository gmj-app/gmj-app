<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RequestCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $requestId,
        public readonly int $creatorId,
        public readonly ?int $submitterUserId,
        public readonly string $moderationState,
        public readonly ?int $actorUserId,
        public readonly string $actorContext = 'guide',
    ) {}
}
