<?php

namespace App\Services;

use App\Models\Creator;
use Illuminate\Support\Facades\Cache;

class CreatorCacheInvalidator
{
    public function forget(Creator $creator): void
    {
        foreach ([
            "creator:{$creator->id}:requests",
            "creator:{$creator->id}:metrics",
            "creator:{$creator->id}:profile",
            'search:creators',
            'home:creators',
        ] as $key) {
            Cache::forget($key);
        }
    }
}
