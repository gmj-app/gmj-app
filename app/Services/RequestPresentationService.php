<?php

namespace App\Services;

use App\Models\Recommendation;
use App\Models\RequestPresentationRevision;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RequestPresentationService
{
    /** @param array{display_title_override?: string|null, request_context?: string|null} $attributes */
    public function update(Recommendation $recommendation, User $actor, array $attributes, string $context = 'guide', string $action = 'request.guide_presentation_updated'): ?RequestPresentationRevision
    {
        return DB::transaction(function () use ($recommendation, $actor, $attributes, $context, $action): ?RequestPresentationRevision {
            $item = Recommendation::query()->lockForUpdate()->findOrFail($recommendation->id);
            $before = $item->only(['display_title_override', 'request_context']);
            $after = [
                'display_title_override' => $attributes['display_title_override'] ?? null,
                'request_context' => $attributes['request_context'] ?? null,
            ];
            $changed = collect($after)->filter(fn ($value, string $key): bool => $value !== $before[$key])->keys()->values()->all();

            if ($changed === []) {
                return null;
            }

            $item->update($after);
            $revision = $item->presentationRevisions()->create([
                'actor_id' => $actor->id,
                'actor_context' => $context,
                'action' => $action,
                'previous_display_title_override' => $before['display_title_override'],
                'new_display_title_override' => $after['display_title_override'],
                'previous_request_context' => $before['request_context'],
                'new_request_context' => $after['request_context'],
                'changed_fields' => $changed,
            ]);
            Cache::forget("recommendation:{$item->id}");
            Cache::forget("creator:{$item->creator_id}:requests");
            Cache::forget("user:{$item->submitted_by}:activity");
            Cache::forget("guide:{$item->submitted_by}:profile");
            Cache::forget('search:recommendations');
            Cache::forget('home:top-requests');

            return $revision;
        });
    }

    public function revert(Recommendation $recommendation, RequestPresentationRevision $revision, User $actor, string $context): ?RequestPresentationRevision
    {
        abort_unless((int) $revision->recommendation_id === (int) $recommendation->id, 404);

        return $this->update($recommendation, $actor, [
            'display_title_override' => $revision->previous_display_title_override,
            'request_context' => $revision->previous_request_context,
        ], $context, 'request.display_title_override_reverted');
    }
}
