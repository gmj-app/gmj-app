<?php

namespace App\Services;

use App\Models\Recommendation;
use App\Models\RequestPresentationRevision;
use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RequestPresentationService
{
    /** @param array{display_title_override?: string|null, reason?: string|null, request_context?: string|null} $attributes */
    public function update(Recommendation $recommendation, User $actor, array $attributes, string $context = 'guide', string $action = 'request.guide_presentation_updated'): ?RequestPresentationRevision
    {
        return DB::transaction(function () use ($recommendation, $actor, $attributes, $context, $action): ?RequestPresentationRevision {
            $item = Recommendation::query()->lockForUpdate()->findOrFail($recommendation->id);
            $before = $item->only(['display_title_override', 'reason', 'request_context']);
            $after = [
                'display_title_override' => $attributes['display_title_override'] ?? null,
                'reason' => array_key_exists('reason', $attributes) ? $attributes['reason'] : $before['reason'],
                // Only legacy moderation/revert actions still write this historical field.
                'request_context' => array_key_exists('request_context', $attributes) ? $attributes['request_context'] : $before['request_context'],
            ];
            $changed = collect($after)->filter(fn ($value, string $key): bool => $value !== $before[$key])->keys()->values()->all();

            if ($changed === []) {
                return null;
            }

            $item->update($after);
            // Reuse the existing text snapshots; changed_fields identifies which column they describe.
            $contextField = in_array('reason', $changed, true) ? 'reason' : 'request_context';
            $revision = $item->presentationRevisions()->create([
                'actor_id' => $actor->id,
                'actor_context' => $context,
                'action' => $action,
                'previous_display_title_override' => $before['display_title_override'],
                'new_display_title_override' => $after['display_title_override'],
                'previous_request_context' => $before[$contextField],
                'new_request_context' => $after[$contextField],
                'changed_fields' => $changed,
            ]);
            Cache::forget("recommendation:{$item->id}");
            Cache::forget("creator:{$item->creator_id}:requests");
            Cache::forget("user:{$item->submitted_by}:activity");
            Cache::forget("guide:{$item->submitted_by}:profile");
            Cache::forget('search:recommendations');
            if (in_array('display_title_override', $changed, true)) {
                Cache::forget('home:top-requests');
            }

            return $revision;
        });
    }

    public function revert(Recommendation $recommendation, RequestPresentationRevision $revision, User $actor, string $context): ?RequestPresentationRevision
    {
        abort_unless((int) $revision->recommendation_id === (int) $recommendation->id, 404);

        $attributes = [
            'display_title_override' => $revision->previous_display_title_override,
        ];
        if (in_array('reason', $revision->changed_fields, true)) {
            $attributes['reason'] = $revision->previous_request_context;
        } elseif (in_array('request_context', $revision->changed_fields, true)) {
            $attributes['request_context'] = $revision->previous_request_context;
        }

        return $this->update($recommendation, $actor, $attributes, $context, 'request.display_title_override_reverted');
    }
}
