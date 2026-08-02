<?php

namespace App\Services\CreatorIntelligence\Analytics;

use App\Enums\MetadataCompletionStatus;
use App\Models\ContentItem;
use App\Models\Subject;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class AnalyticsReportService
{
    public function __construct(private readonly AnalyticsDataset $dataset, private readonly StatisticsService $statistics) {}

    public function report(string $report, AnalyticsContext $context): array
    {
        $rows = $this->dataset->rows($context);
        $coverage = $this->coverage($rows);
        $groups = match ($report) {
            'subjects' => $this->relationshipGroups($rows, $context, 'subject'),
            'content-items' => $this->relationshipGroups($rows, $context, 'content_item'),
            'timing' => $this->timingGroups($rows, $context),
            'titles' => $this->metadataGroups($rows, $context, 'title'),
            'thumbnails' => $this->metadataGroups($rows, $context, 'thumbnail'),
            'editorial' => $this->metadataGroups($rows, $context, 'editorial'),
            'hype' => $this->hypeGroups($rows, $context),
            default => $this->periodGroups($rows, $context),
        };

        return [
            'rows' => $rows,
            'coverage' => $coverage,
            'summary' => $this->metrics($rows),
            'metadata_status_counts' => $this->metadataStatusCounts($rows),
            'groups' => $groups,
            'empty_state' => $report === 'subjects' ? $this->subjectEmptyState($rows, $groups, $context) : null,
            'comparison' => $report === 'subjects' ? $this->subjectComparison($rows, $context) : null,
        ];
    }

    public function metrics(Collection $rows): array
    {
        $result = ['video_count' => $rows->count()];
        foreach (array_merge(AnalyticsDataset::METRICS, ['metadata_completion_percentage']) as $metric) {
            $column = $metric === 'metadata_completion_percentage' ? $metric : 'metric_'.$metric;
            $result[$metric] = $this->statistics->summarize($rows->pluck($column), $rows->count());
        }

        return $result;
    }

    private function coverage(Collection $rows): array
    {
        $hypeReported = $rows->whereNotNull('metric_hype_points');
        $hypePositive = $hypeReported->filter(fn ($row) => (float) $row->metric_hype_points > 0)->count();

        return [
            'videos' => $rows->count(),
            'views' => $rows->whereNotNull('metric_views')->count(),
            'ctr' => $rows->whereNotNull('metric_impressions_ctr')->count(),
            'hype' => $hypeReported->count(),
            'hype_reported' => $hypeReported->count(),
            'hype_positive' => $hypePositive,
            'hype_receiving_percentage' => $hypeReported->isEmpty() ? null : ($hypePositive / $hypeReported->count()) * 100,
            'reviewed_titles' => $rows->whereNotNull('title_reviewed_at')->count(),
            'reviewed_thumbnails' => $rows->whereNotNull('thumbnail_reviewed_at')->count(),
            'reviewed_editorial' => $rows->whereNotNull('editorial_reviewed_at')->count(),
        ];
    }

    /** @return array{complete: int, in_progress: int, not_started: int} */
    private function metadataStatusCounts(Collection $rows): array
    {
        $counts = ['complete' => 0, 'in_progress' => 0, 'not_started' => 0];

        foreach ($rows as $row) {
            $status = $row->metadata_completion_status;
            $value = $status instanceof MetadataCompletionStatus ? $status->value : (string) $status;
            if (array_key_exists($value, $counts)) {
                $counts[$value]++;
            }
        }

        return $counts;
    }

    private function finishGroups(Collection $groups, AnalyticsContext $context): Collection
    {
        $channelMedian = $this->statistics->median($groups->flatten(1)->unique('id')->pluck('metric_views'));

        return $groups->map(function (Collection $videos, string $label) use ($context, $channelMedian) {
            $metrics = $this->metrics($videos);
            $top = $videos->whereNotNull('metric_views')->sortByDesc('metric_views')->first();
            $totalViews = $metrics['views']['sum'];
            $eligibleViews = $videos->whereNotNull('metric_views');

            return ['label' => $label, 'video_count' => $videos->count(), 'metrics' => $metrics, 'top_video' => $top, 'top_video_share' => $top && $totalViews > 0 ? ((float) $top->metric_views / $totalViews) * 100 : null, 'percentage_above_channel_median' => $channelMedian === null || $eligibleViews->isEmpty() ? null : ($eligibleViews->filter(fn ($video) => (float) $video->metric_views > $channelMedian)->count() / $eligibleViews->count()) * 100, 'sample_strength' => $this->statistics->sampleStrength($videos->count(), $context->sampleMinimum())];
        })->filter(fn ($group) => $context->filters['show_low_sample'] || $group['video_count'] >= $context->sampleMinimum())->sortByDesc(fn ($group) => $group['metrics']['views']['median'] ?? -INF)->values();
    }

    private function relationshipGroups(Collection $rows, AnalyticsContext $context, string $kind): Collection
    {
        if ($rows->isEmpty()) {
            return collect();
        }
        $pivot = $kind === 'subject' ? 'creator_video_subject' : 'creator_video_content_item';
        $foreign = $kind === 'subject' ? 'subject_id' : 'content_item_id';
        $model = $kind === 'subject' ? Subject::class : ContentItem::class;
        $links = DB::table($pivot)->whereIn('creator_video_id', $rows->pluck('id'))->when(! $context->filters['include_secondary'] && ! $context->filters['primary_only'], fn ($q) => $q->where('is_primary', true))->when($context->filters['primary_only'] ?? false, fn ($q) => $q->where('is_primary', true))->get();
        $names = $model::whereIn('id', $links->pluck($foreign))->pluck('name', 'id');
        $groups = collect();
        foreach ($links as $link) {
            if ($video = $rows->firstWhere('id', $link->creator_video_id)) {
                $groups->push(['label' => $names[$link->{$foreign}] ?? 'Deleted', 'video' => $video]);
            }
        }

        return $this->finishGroups($groups->groupBy('label')->map(fn ($items) => $items->pluck('video')), $context);
    }

    private function subjectEmptyState(Collection $rows, Collection $groups, AnalyticsContext $context): ?string
    {
        if ($rows->isEmpty()) {
            return 'no_videos';
        }

        $relationships = DB::table('creator_video_subject')->whereIn('creator_video_id', $rows->pluck('id'))->get();
        if ($relationships->isEmpty()) {
            return 'no_relationships';
        }

        $primaryOnly = ($context->filters['primary_only'] ?? false) || ! ($context->filters['include_secondary'] ?? false);
        if ($primaryOnly && $relationships->where('is_primary', true)->isEmpty()) {
            return 'no_primary_relationships';
        }

        return $groups->isEmpty() ? 'below_minimum' : null;
    }

    private function periodGroups(Collection $rows, AnalyticsContext $context): Collection
    {
        $grouping = $context->filters['grouping'] ?? 'month';

        return $this->finishGroups($rows->filter->published_at->groupBy(function ($video) use ($grouping) {
            $date = CarbonImmutable::parse($video->published_at);

            return match ($grouping) {
                'week' => $date->startOfWeek()->toDateString(), 'quarter' => $date->format('Y').' Q'.$date->quarter, 'year' => $date->format('Y'), default => $date->format('Y-m')
            };
        }), $context);
    }

    private function timingGroups(Collection $rows, AnalyticsContext $context): Collection
    {
        $timezone = $context->channel?->default_publish_timezone ?? 'UTC';
        $dimension = $context->filters['dimension'] ?? 'weekday';
        $groups = $rows->filter->published_at->groupBy(function ($video) use ($timezone, $dimension) {
            $date = CarbonImmutable::parse($video->published_at)->timezone($timezone);

            return match ($dimension) {
                'hour' => $date->format('H:00'), 'day_hour' => $date->format('l H:00'), 'daypart' => match (intdiv((int) $date->format('G'), 6)) {
                    0 => '12:00 AM–5:59 AM', 1 => '6:00 AM–11:59 AM', 2 => '12:00 PM–5:59 PM', default => '6:00 PM–11:59 PM'
                }, 'month' => $date->format('Y-m'), default => $date->format('l')
            };
        });

        return $this->finishGroups($groups, $context);
    }

    private function metadataGroups(Collection $rows, AnalyticsContext $context, string $type): Collection
    {
        $dimension = $context->filters['dimension'] ?? match ($type) {
            'title' => 'title_template', 'thumbnail' => 'creator_expression', default => 'creator_sentiment'
        };
        $manual = str_starts_with($dimension, 'title_') ? false : in_array($dimension, ['subject_name_present', 'content_item_name_present', 'negative_hook', 'curiosity_hook', 'emotional_hook', 'controversy_hook', 'technical_hook', 'discovery_hook'], true);
        $reviewColumn = $type.'_reviewed_at';
        if (! $context->filters['include_unreviewed'] && ($type !== 'title' || $manual)) {
            $rows = $rows->whereNotNull($reviewColumn);
        }
        $column = $dimension;
        $groups = $rows->filter(fn ($row) => $row->{$column} !== null)->groupBy(function ($row) use ($column) {
            $value = $row->{$column};
            if ($column === 'title_character_count') {
                return match (true) {
                    $value < 40 => '0–39', $value < 50 => '40–49', $value < 60 => '50–59', $value < 70 => '60–69', default => '70+'
                };
            }
            if ($column === 'title_word_count') {
                return match (true) {
                    $value <= 5 => '1–5', $value <= 8 => '6–8', $value <= 11 => '9–11', $value <= 14 => '12–14', default => '15+'
                };
            }
            if ($column === 'thumbnail_text_word_count') {
                return match (true) {
                    $value == 0 => '0', $value <= 2 => '1–2', $value <= 4 => '3–4', $value <= 6 => '5–6', default => '7+'
                };
            }
            if ($column === 'face_count') {
                return match (true) {
                    $value == 0 => '0', $value == 1 => '1', $value == 2 => '2', default => '3+'
                };
            }

            return is_bool($value) || in_array($value, [0, 1, '0', '1'], true) ? ((bool) $value ? 'Yes' : 'No') : str($value)->headline()->toString();
        });

        return $this->finishGroups($groups, $context);
    }

    private function hypeGroups(Collection $rows, AnalyticsContext $context): Collection
    {
        $rows = $rows->whereNotNull('metric_hype_points');
        if (($context->filters['dimension'] ?? 'videos') === 'subjects') {
            return $this->relationshipGroups($rows, $context, 'subject');
        }

        return $this->finishGroups($rows->groupBy(fn ($video) => $video->title), $context)->sortByDesc(fn ($group) => $group['metrics']['hype_points']['median'] ?? -INF)->values();
    }

    private function subjectComparison(Collection $rows, AnalyticsContext $context): ?array
    {
        $subjectId = $context->filters['subject_id'] ?? null;
        if (! $subjectId) {
            return null;
        }
        $selectedIds = DB::table('creator_video_subject')->where('subject_id', $subjectId)->pluck('creator_video_id');
        $otherId = $context->filters['compare_subject_id'] ?? null;
        $comparisonIds = $otherId ? DB::table('creator_video_subject')->where('subject_id', $otherId)->pluck('creator_video_id') : null;
        $left = $this->metrics($rows->whereIn('id', $selectedIds));
        $right = $this->metrics($comparisonIds ? $rows->whereIn('id', $comparisonIds) : $rows->whereNotIn('id', $selectedIds));
        $differences = [];
        foreach (['views', 'impressions_ctr', 'watch_time_minutes', 'subscribers_gained', 'estimated_revenue', 'hype_points'] as $metric) {
            $differences[$metric] = $this->statistics->difference($left[$metric]['mean'], $right[$metric]['mean'], $metric === 'impressions_ctr');
        }

        return ['left' => $left, 'right' => $right, 'differences' => $differences, 'left_label' => Subject::find($subjectId)?->name, 'right_label' => $otherId ? Subject::find($otherId)?->name : 'All non-selected videos'];
    }
}
