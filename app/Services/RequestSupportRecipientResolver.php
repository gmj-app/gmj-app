<?php

namespace App\Services;

use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Support\Collection;

class RequestSupportRecipientResolver
{
    /** @return Collection<int, User> */
    public function resolve(Recommendation $request, ?int $excludeUserId = null): Collection
    {
        $userIds = $request->allUserPicks()
            ->where('vote_count', '>', 0)
            ->where(function ($query): void {
                $query->whereNull('release_reason')
                    ->orWhere('release_reason', '!=', 'request_removed');
            })
            ->when($excludeUserId, fn ($query) => $query->where('user_id', '!=', $excludeUserId))
            ->distinct()
            ->pluck('user_id');

        return User::query()->whereKey($userIds)->orderBy('id')->get();
    }
}
