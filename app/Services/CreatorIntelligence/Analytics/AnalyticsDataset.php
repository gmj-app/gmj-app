<?php

namespace App\Services\CreatorIntelligence\Analytics;

use App\Models\CreatorVideo;
use App\Models\VideoPerformanceSnapshot;
use App\Services\CreatorIntelligence\Videos\LatestSnapshotResolver;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class AnalyticsDataset
{
    public const METRICS = ['views', 'impressions', 'impressions_ctr', 'watch_time_minutes', 'average_view_duration_seconds', 'average_percentage_viewed', 'likes', 'comments', 'subscribers_gained', 'estimated_revenue', 'rpm', 'hype_points'];

    public function __construct(private readonly LatestSnapshotResolver $latest) {}

    public function query(AnalyticsContext $context): Builder
    {
        $filters = $context->filters;
        $query = CreatorVideo::query()->select('creator_videos.*')
            ->leftJoin('video_title_metadata as analytics_title', 'analytics_title.creator_video_id', '=', 'creator_videos.id')
            ->leftJoin('video_thumbnail_metadata as analytics_thumbnail', 'analytics_thumbnail.creator_video_id', '=', 'creator_videos.id')
            ->leftJoin('video_editorial_metadata as analytics_editorial', 'analytics_editorial.creator_video_id', '=', 'creator_videos.id')
            ->addSelect(['analytics_title.character_count as title_character_count', 'analytics_title.word_count as title_word_count', 'analytics_title.contains_question as title_contains_question', 'analytics_title.contains_exclamation as title_contains_exclamation', 'analytics_title.contains_pipe as title_contains_pipe', 'analytics_title.contains_parentheses as title_contains_parentheses', 'analytics_title.contains_all_caps as title_contains_all_caps', 'analytics_title.subject_name_present', 'analytics_title.content_item_name_present', 'analytics_title.negative_hook', 'analytics_title.curiosity_hook', 'analytics_title.emotional_hook', 'analytics_title.controversy_hook', 'analytics_title.technical_hook', 'analytics_title.discovery_hook', 'analytics_title.title_template', 'analytics_title.reviewed_at as title_reviewed_at', 'analytics_thumbnail.creator_expression', 'analytics_thumbnail.background_style', 'analytics_thumbnail.dominant_color_label', 'analytics_thumbnail.layout_style', 'analytics_thumbnail.text_position', 'analytics_thumbnail.text_word_count as thumbnail_text_word_count', 'analytics_thumbnail.face_count', 'analytics_thumbnail.creator_face_visible', 'analytics_thumbnail.subject_face_visible', 'analytics_thumbnail.contains_question as thumbnail_contains_question', 'analytics_thumbnail.contains_arrow', 'analytics_thumbnail.contains_circle', 'analytics_thumbnail.contains_flag', 'analytics_thumbnail.contains_logo', 'analytics_thumbnail.reviewed_at as thumbnail_reviewed_at', 'analytics_editorial.creator_sentiment', 'analytics_editorial.reaction_style', 'analytics_editorial.energy_level', 'analytics_editorial.technical_depth', 'analytics_editorial.humor_level', 'analytics_editorial.cultural_context_level', 'analytics_editorial.reviewed_at as editorial_reviewed_at']);
        foreach (LatestSnapshotResolver::FIELDS as $field) {
            $query->selectSub($this->latest->subquery($field, $filters['snapshot_source'] ?? null, $filters['snapshot_from'] ?? null, $filters['snapshot_to'] ?? null), 'metric_'.$field);
        }
        if ($context->channel) {
            $query->where('creator_videos.creator_channel_id', $context->channel->id);
        } elseif (isset($filters['creator_profile_id'])) {
            $query->whereHas('channel', fn (Builder $q) => $q->where('creator_profile_id', $filters['creator_profile_id']));
        }
        if (isset($filters['published_from'])) {
            $query->whereDate('creator_videos.published_at', '>=', $filters['published_from']);
        }
        if (isset($filters['published_to'])) {
            $query->whereDate('creator_videos.published_at', '<=', $filters['published_to']);
        }
        foreach (['video_format', 'content_type', 'copyright_status'] as $field) {
            if (isset($filters[$field])) {
                $query->where('creator_videos.'.$field, $filters[$field]);
            }
        }
        if (isset($filters['monetization_status'])) {
            $filters['monetization_status'] === 'unknown' ? $query->whereNull('creator_videos.is_monetized') : $query->where('creator_videos.is_monetized', $filters['monetization_status'] === '1');
        }
        $query->where('creator_videos.metadata_completion_percentage', '>=', $filters['minimum_metadata_completion'] ?? 0);
        if (! $filters['include_shorts']) {
            $query->where('creator_videos.is_short', false);
        }
        if (! $filters['include_lives']) {
            $query->where('creator_videos.is_live', false);
        }
        if (! $filters['include_without_performance']) {
            $query->whereExists(function ($snapshot) use ($filters) {
                $snapshot->selectRaw('1')->from((new VideoPerformanceSnapshot)->getTable())
                    ->whereColumn('creator_video_id', 'creator_videos.id');
                if (isset($filters['snapshot_source'])) {
                    $snapshot->where('source', $filters['snapshot_source']);
                }
                if (isset($filters['snapshot_from'])) {
                    $snapshot->whereDate('snapshot_date', '>=', $filters['snapshot_from']);
                }
                if (isset($filters['snapshot_to'])) {
                    $snapshot->whereDate('snapshot_date', '<=', $filters['snapshot_to']);
                }
            });
        }

        return $query;
    }

    public function rows(AnalyticsContext $context): Collection
    {
        return $this->query($context)->get();
    }
}
