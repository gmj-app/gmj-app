<?php

namespace App\Services\Accolades;

use App\Events\AccoladeAwarded;
use App\Models\UserAccolade;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class AccoladeAwardService
{
    /**
     * @param  array<string, mixed>  $definition
     * @param  array<string, mixed>  $source
     * @return array{award: UserAccolade, created: bool}
     */
    public function award(int $userId, string $subjectType, int $subjectId, array $definition, int $value, array $source = []): array
    {
        try {
            [$award, $created] = DB::transaction(function () use ($userId, $subjectType, $subjectId, $definition, $value, $source): array {
                $existing = UserAccolade::query()->where([
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                    'accolade_key' => $definition['key'],
                ])->lockForUpdate()->first();

                if ($existing) {
                    return [$existing, false];
                }

                $currentFeature = $subjectType === 'guide'
                    ? UserAccolade::query()->where('subject_type', 'guide')->where('subject_id', $subjectId)->where('is_featured', true)->first()
                    : null;
                $feature = $subjectType === 'guide' && ! data_get($currentFeature?->metadata, 'manual_featured', false);
                if ($feature && $currentFeature) {
                    $currentFeature->update(['is_featured' => false, 'featured_order' => null]);
                }

                return [UserAccolade::query()->create([
                    'user_id' => $userId,
                    'accolade_key' => $definition['key'],
                    'subject_type' => $subjectType,
                    'subject_id' => $subjectId,
                    'track' => $definition['track'],
                    'level' => $definition['level'],
                    'progress_value_at_award' => $value,
                    'threshold_at_award' => $definition['threshold'],
                    'awarded_at' => now(),
                    'source_event_type' => $source['event_type'] ?? null,
                    'source_event_id' => isset($source['event_id']) ? (string) $source['event_id'] : null,
                    'metadata' => $source,
                    'is_featured' => $feature,
                    'featured_order' => $feature ? 1 : null,
                    'is_public' => $definition['public_visibility_default'],
                ]), true];
            }, 3);
        } catch (QueryException $exception) {
            $award = UserAccolade::query()->where([
                'subject_type' => $subjectType, 'subject_id' => $subjectId, 'accolade_key' => $definition['key'],
            ])->first();
            if (! $award) {
                throw $exception;
            }
            $created = false;
        }

        if ($created) {
            Cache::forget("accolades:{$subjectType}:{$subjectId}");
            if (! ($source['suppress_notifications'] ?? false)) {
                AccoladeAwarded::dispatch(
                    $award->id, $award->accolade_key, $award->user_id, $award->subject_type,
                    $award->subject_id, $award->track, $award->level, $award->awarded_at->toIso8601String(), $source,
                );
            }
        }

        return compact('award', 'created');
    }
}
