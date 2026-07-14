<?php

namespace App\Services;

use App\Events\RequestPublished;
use App\Models\Recommendation;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Throwable;

class RecommendationStatusTransitionService
{
    public function __construct(
        private readonly YouTubeUrlService $youtubeUrls,
        private readonly YouTubePlaylistMetadataService $playlistMetadata,
        private readonly RequestCacheInvalidator $cache,
    ) {}

    /** @return array{recommendation:Recommendation,released_votes:int,affected_guides:int,before:array,after:array} */
    public function transition(Recommendation $recommendation, string $newStatus, User $actor, array $metadata = [], string $actorContext = 'creator'): array
    {
        if (! in_array($newStatus, Recommendation::STATUSES, true)) {
            throw ValidationException::withMessages(['status' => 'Choose a valid request status.']);
        }
        if ($newStatus === 'published' && $actorContext === 'super_admin') {
            if (blank($metadata['published_reaction_url'] ?? $recommendation->published_reaction_url)) {
                throw ValidationException::withMessages(['published_reaction_url' => 'A published URL is required.']);
            }
            if (blank($metadata['published_at'] ?? null)) {
                throw ValidationException::withMessages(['published_at' => 'A published date and time is required.']);
            }
        }

        $result = DB::transaction(function () use ($recommendation, $newStatus, $actor, $metadata): array {
            $locked = Recommendation::withTrashed()->lockForUpdate()->findOrFail($recommendation->id);
            $before = $locked->only(['status', 'scheduled_for', 'published_at', 'resolved_at', 'published_reaction_url', 'moderation_reason', 'moderation_note', 'public_resolution_note', 'private_resolution_reason', 'prior_coverage_url', 'prior_coverage_title']);
            $closesVoting = $locked->shouldClearUpvotesWhenStatusIs($newStatus);
            $releasedVotes = $closesVoting ? (int) $locked->userPicks()->sum('vote_count') : 0;
            $affectedGuides = $closesVoting ? $locked->userPicks()->distinct()->count('user_id') : 0;
            $attributes = [
                'status' => $newStatus,
                'scheduled_for' => $newStatus === 'scheduled' ? ($metadata['scheduled_for'] ?? $locked->scheduled_for) : $locked->scheduled_for,
                'published_at' => $newStatus === 'published' ? ($metadata['published_at'] ?? $locked->published_at ?? now()) : $locked->published_at,
                'resolved_at' => in_array($newStatus, Recommendation::CLOSED_PUBLIC_STATUSES, true)
                    ? ($metadata['resolved_at'] ?? $locked->resolved_at ?? now())
                    : null,
                'moderated_by' => $actor->id,
                'moderated_at' => now(),
            ];
            if ($closesVoting && $locked->voting_closed_at === null) {
                $attributes += [
                    'voting_closed_at' => now(),
                    'vote_total_at_close' => $releasedVotes,
                    'supporter_count_at_close' => $affectedGuides,
                ];
            }
            if (array_key_exists('moderation_reason', $metadata)) {
                $attributes['moderation_reason'] = $metadata['moderation_reason'];
            }
            if (array_key_exists('moderation_note', $metadata)) {
                $attributes['moderation_note'] = $metadata['moderation_note'];
            }
            if (array_key_exists('published_reaction_url', $metadata)) {
                $attributes += $this->publishedAttributes($metadata['published_reaction_url']);
            }
            foreach (['public_resolution_note', 'private_resolution_reason', 'prior_coverage_url', 'prior_coverage_title'] as $field) {
                if (array_key_exists($field, $metadata)) {
                    $attributes[$field] = $metadata[$field];
                }
            }
            $locked->update($attributes);

            if ($closesVoting) {
                $now = now();
                $reason = 'request_'.$newStatus;
                $locked->userPicks()->update(['released_at' => $now, 'release_reason' => $reason]);
                $locked->update([
                    'resource_released_at' => $locked->resource_released_at ?? $now,
                    'resource_release_reason' => $locked->resource_release_reason ?? $reason,
                ]);
            }

            return ['recommendation' => $locked->fresh(), 'released_votes' => $releasedVotes, 'affected_guides' => $affectedGuides, 'before' => $before, 'after' => $locked->fresh()->only(array_keys($before))];
        });
        $this->cache->forget($result['recommendation']);
        $this->dispatchPublicationIfNew($result['recommendation'], (string) $result['before']['status'], $actor, $actorContext);

        return $result;
    }

    public function dispatchPublicationIfNew(Recommendation $recommendation, string $previousStatus, User $actor, string $actorContext): void
    {
        if ($previousStatus === 'published' || $recommendation->status !== 'published') {
            return;
        }

        try {
            RequestPublished::dispatch(
                $recommendation->id,
                $recommendation->creator_id,
                $recommendation->submitted_by,
                $actor->id,
                $actorContext,
                ($recommendation->published_at ?? now())->toIso8601String(),
            );
        } catch (Throwable $exception) {
            Log::error('Unable to queue request publication workflows.', [
                'request_id' => $recommendation->id,
                'actor_id' => $actor->id,
                'exception' => $exception,
            ]);
        }
    }

    private function publishedAttributes(?string $url): array
    {
        if (blank($url)) {
            return ['published_reaction_url' => null, 'published_normalized_url' => null, 'published_title' => null, 'published_channel' => null, 'published_thumbnail_url' => null, 'published_video_id' => null, 'published_media_type' => null, 'published_playlist_id' => null, 'published_item_count' => null, 'published_metadata' => null];
        }
        $normalized = $this->youtubeUrls->normalize($url);
        $attributes = ['published_reaction_url' => $normalized['canonical_url'] ?: $url, 'published_normalized_url' => $normalized['canonical_url'], 'published_media_type' => $normalized['media_type'], 'published_video_id' => $normalized['youtube_video_id'], 'published_playlist_id' => $normalized['youtube_playlist_id'], 'published_item_count' => null];
        if ($normalized['media_type'] === 'playlist' && $normalized['youtube_playlist_id']) {
            $data = $this->playlistMetadata->fetch($normalized['youtube_playlist_id']);

            return [...$attributes, 'published_title' => $data['title'] ?? null, 'published_channel' => $data['channel_title'] ?? null, 'published_thumbnail_url' => $data['thumbnail_url'] ?? null, 'published_item_count' => $data['item_count'] ?? null, 'published_metadata' => $data];
        }
        if (! $normalized['youtube_video_id']) {
            return [...$attributes, 'published_title' => null, 'published_channel' => null, 'published_thumbnail_url' => null, 'published_metadata' => null];
        }
        $attributes['published_thumbnail_url'] = "https://img.youtube.com/vi/{$normalized['youtube_video_id']}/hqdefault.jpg";
        try {
            $data = Http::timeout(5)->acceptJson()->get('https://www.youtube.com/oembed', ['format' => 'json', 'url' => $url])->throw()->json();
        } catch (Throwable) {
            $data = ['metadata_unavailable' => true];
        }

        return [...$attributes, 'published_title' => $data['title'] ?? null, 'published_channel' => $data['author_name'] ?? null, 'published_metadata' => $data];
    }
}
