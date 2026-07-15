<?php

namespace App\Services;

use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Support\Collection;

class RequestSupportRecipientResolver
{
    public function __construct(private readonly RequestSupportService $support) {}

    /** @return Collection<int, User> */
    public function resolve(Recommendation $request, ?int $excludeUserId = null): Collection
    {
        $userIds = $this->support->historicalSupport($request)
            ->when($excludeUserId, fn ($query) => $query->where('user_id', '!=', $excludeUserId))
            ->distinct()
            ->pluck('user_id');

        return User::query()->whereKey($userIds)->orderBy('id')->get();
    }
}
