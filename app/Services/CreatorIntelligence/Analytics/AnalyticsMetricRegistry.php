<?php

namespace App\Services\CreatorIntelligence\Analytics;

use App\Services\CreatorIntelligence\Videos\MetricFormatter;

class AnalyticsMetricRegistry
{
    public const DEFINITIONS = [
        'views' => ['label' => 'Views', 'summable' => true, 'format' => 'number'],
        'impressions' => ['label' => 'Impressions', 'summable' => true, 'format' => 'number'],
        'impressions_ctr' => ['label' => 'CTR', 'summable' => false, 'format' => 'percentage', 'missing_label' => 'Missing CTR'],
        'watch_time_minutes' => ['label' => 'Watch Time', 'summable' => true, 'format' => 'hours'],
        'average_view_duration_seconds' => ['label' => 'Average View Duration', 'summable' => false, 'format' => 'seconds'],
        'average_percentage_viewed' => ['label' => 'Average Percentage Viewed', 'summable' => false, 'format' => 'percentage'],
        'likes' => ['label' => 'Likes', 'summable' => true, 'format' => 'number'],
        'comments' => ['label' => 'Comments', 'summable' => true, 'format' => 'number'],
        'subscribers_gained' => ['label' => 'Subscribers Gained', 'summable' => true, 'format' => 'number'],
        'estimated_revenue' => ['label' => 'Revenue', 'summable' => true, 'format' => 'currency'],
        'rpm' => ['label' => 'RPM', 'summable' => false, 'format' => 'currency'],
        'cpm' => ['label' => 'CPM', 'summable' => false, 'format' => 'currency'],
        'hype_points' => ['label' => 'Hype Points', 'summable' => true, 'format' => 'number', 'average_precision' => 2],
        'metadata_completion_percentage' => ['label' => 'Metadata Completion', 'summable' => false, 'format' => 'percentage'],
        'consistency_score' => ['label' => 'Consistency Score', 'summable' => false, 'format' => 'percentage'],
    ];

    public function __construct(private readonly MetricFormatter $formatter) {}

    public function label(string $metric): string
    {
        return self::DEFINITIONS[$metric]['label'] ?? str($metric)->headline()->toString();
    }

    public function summable(string $metric): bool
    {
        return self::DEFINITIONS[$metric]['summable'] ?? false;
    }

    public function percentage(string $metric): bool
    {
        return (self::DEFINITIONS[$metric]['format'] ?? null) === 'percentage';
    }

    public function currency(string $metric): bool
    {
        return (self::DEFINITIONS[$metric]['format'] ?? null) === 'currency';
    }

    /**
     * @param  array<string, float|int|null>  $statistics
     * @param  array<string, int>  $metadataStatuses
     * @return array<int, array{label: string, value: string, title: ?string}>
     */
    public function summaryRows(string $metric, array $statistics, array $metadataStatuses = [], string $currency = 'USD'): array
    {
        if ($metric === 'metadata_completion_percentage') {
            return [
                $this->row('Average Completion', $metric, $statistics['mean'] ?? null, 'mean', $currency),
                $this->row('Median Completion', $metric, $statistics['median'] ?? null, 'median', $currency),
                $this->countRow('Complete videos', $metadataStatuses['complete'] ?? 0),
                $this->countRow('In-progress videos', $metadataStatuses['in_progress'] ?? 0),
                $this->countRow('Not-started videos', $metadataStatuses['not_started'] ?? 0),
            ];
        }

        $rows = [];
        if ($this->summable($metric)) {
            $rows[] = $this->row($this->aggregateLabel($metric, 'Total'), $metric, $statistics['sum'] ?? null, 'total', $currency);
        }

        $rows[] = $this->row($this->aggregateLabel($metric, 'Average'), $metric, $statistics['mean'] ?? null, 'mean', $currency);
        $rows[] = $this->row($this->aggregateLabel($metric, 'Median'), $metric, $statistics['median'] ?? null, 'median', $currency);
        $rows[] = $this->countRow('Eligible videos', (int) ($statistics['eligible_video_count'] ?? 0));
        $rows[] = $this->countRow(self::DEFINITIONS[$metric]['missing_label'] ?? 'Missing values', (int) ($statistics['missing_value_count'] ?? 0));

        return $rows;
    }

    private function aggregateLabel(string $metric, string $aggregate): string
    {
        if ($metric === 'watch_time_minutes') {
            return $aggregate === 'Total' ? 'Total watch time in hours' : $aggregate.' watch time per video in hours';
        }

        if ($metric === 'average_percentage_viewed') {
            return $aggregate.' Percentage Viewed';
        }

        return $aggregate.' '.$this->label($metric);
    }

    /** @return array{label: string, value: string, title: ?string} */
    private function row(string $label, string $metric, float|int|null $value, string $aggregate, string $currency): array
    {
        $format = self::DEFINITIONS[$metric]['format'] ?? 'number';

        return [
            'label' => $label,
            'value' => $value === null ? 'No data' : $this->formatValue($metric, $value, $aggregate, $currency),
            'title' => $format === 'hours' && $value !== null ? number_format((float) $value, 2).' raw minutes' : null,
        ];
    }

    public function formatValue(string $metric, float|int|null $value, string $aggregate = 'value', string $currency = 'USD'): string
    {
        if ($value === null) {
            return 'No data';
        }

        return match (self::DEFINITIONS[$metric]['format'] ?? 'number') {
            'percentage' => $this->formatter->percentage($value),
            'currency' => $this->formatter->currency($value, $currency),
            'hours' => $this->formatter->decimal((float) $value / 60, 2),
            'seconds' => $this->formatter->decimal($value, 2).' seconds',
            default => $aggregate === 'mean' && isset(self::DEFINITIONS[$metric]['average_precision'])
                ? $this->formatter->decimal($value, self::DEFINITIONS[$metric]['average_precision'])
                : $this->formatter->count($value),
        };
    }

    /** @param array<string, float|int|null> $coverage */
    public function summaryNote(string $metric, array $statistics, array $coverage): ?string
    {
        if ($metric !== 'hype_points' || (float) ($statistics['median'] ?? -1) !== 0.0) {
            return null;
        }

        $reported = (int) ($coverage['hype_reported'] ?? 0);
        $positive = (int) ($coverage['hype_positive'] ?? 0);

        return $reported > 0 && ($positive / $reported) < 0.5
            ? 'More than half of eligible videos had zero reported Hype Points.'
            : null;
    }

    /** @return array{label: string, value: string, title: null} */
    private function countRow(string $label, int $value): array
    {
        return ['label' => $label, 'value' => number_format($value), 'title' => null];
    }
}
