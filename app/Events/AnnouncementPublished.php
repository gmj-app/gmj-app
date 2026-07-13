<?php

namespace App\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AnnouncementPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly int $announcementId,
        public readonly string $audience,
        public readonly string $publishedAt,
    ) {}
}
