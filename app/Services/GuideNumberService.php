<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class GuideNumberService
{
    public function assignIfMissing(User $user): User
    {
        if (! $user->exists || $user->guide_number !== null) {
            return $user;
        }

        $attempts = 0;

        beginning:
        $attempts++;

        try {
            DB::transaction(function () use ($user): void {
                $lockedUser = User::query()
                    ->whereKey($user->id)
                    ->lockForUpdate()
                    ->first();

                if (! $lockedUser || $lockedUser->guide_number !== null) {
                    if ($lockedUser) {
                        $user->forceFill(['guide_number' => $lockedUser->guide_number]);
                    }

                    return;
                }

                $nextGuideNumber = ((int) User::query()
                    ->whereNotNull('guide_number')
                    ->lockForUpdate()
                    ->max('guide_number')) + 1;

                $lockedUser->forceFill(['guide_number' => $nextGuideNumber])->save();
                $user->forceFill(['guide_number' => $nextGuideNumber]);
            });
        } catch (QueryException $exception) {
            if ($attempts < 3) {
                goto beginning;
            }

            throw $exception;
        }

        return $user;
    }

    public function nextGuideNumber(): int
    {
        return ((int) User::query()
            ->whereNotNull('guide_number')
            ->max('guide_number')) + 1;
    }

    /**
     * @param  null|callable(User): void  $onAssigned
     */
    public function backfillMissingGuideNumbers(?callable $onAssigned = null): int
    {
        $assigned = 0;

        User::query()
            ->whereNull('guide_number')
            ->orderBy('created_at')
            ->orderBy('id')
            ->each(function (User $user) use (&$assigned, $onAssigned): void {
                $previousGuideNumber = $user->guide_number;
                $this->assignIfMissing($user);
                $user->refresh();

                if ($previousGuideNumber === null && $user->guide_number !== null) {
                    $assigned++;

                    if ($onAssigned !== null) {
                        $onAssigned($user);
                    }
                }
            });

        return $assigned;
    }
}
