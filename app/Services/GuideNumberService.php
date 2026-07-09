<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class GuideNumberService
{
    public function assignIfMissing(User $user): void
    {
        if (! $user->exists || $user->guide_number !== null) {
            return;
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
    }

    public function backfillMissingGuideNumbers(): int
    {
        $assigned = 0;

        User::query()
            ->whereNull('guide_number')
            ->orderBy('created_at')
            ->orderBy('id')
            ->each(function (User $user) use (&$assigned): void {
                $this->assignIfMissing($user);
                $assigned++;
            });

        return $assigned;
    }
}
