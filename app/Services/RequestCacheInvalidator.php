<?php

namespace App\Services;

use App\Models\Recommendation;
use Illuminate\Support\Facades\Cache;

class RequestCacheInvalidator
{
    public function forget(Recommendation $request): void
    {
        foreach (array_filter([
            "recommendation:{$request->id}",
            "creator:{$request->creator_id}:requests",
            "creator:{$request->creator_id}:metrics",
            $request->submitted_by ? "user:{$request->submitted_by}:activity" : null,
            $request->submitted_by ? "guide:{$request->submitted_by}:profile" : null,
            'search:recommendations',
            'home:top-requests',
        ]) as $key) {
            Cache::forget($key);
        }
    }
}
