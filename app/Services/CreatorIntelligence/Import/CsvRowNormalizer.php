<?php

namespace App\Services\CreatorIntelligence\Import;

use App\Services\CreatorIntelligence\Import\Parsers\CurrencyParser;
use App\Services\CreatorIntelligence\Import\Parsers\DateTimeParser;
use App\Services\CreatorIntelligence\Import\Parsers\DecimalMetricParser;
use App\Services\CreatorIntelligence\Import\Parsers\DurationParser;
use App\Services\CreatorIntelligence\Import\Parsers\IntegerMetricParser;
use App\Services\CreatorIntelligence\Import\Parsers\PercentageParser;

class CsvRowNormalizer
{
    private const INTEGER_FIELDS = ['views', 'impressions', 'likes', 'comments', 'shares', 'hype_points', 'views_first_24_hours', 'views_first_7_days', 'views_first_28_days'];

    private const PERCENT_FIELDS = ['impressions_ctr', 'average_percentage_viewed'];

    private const CURRENCY_FIELDS = ['estimated_revenue', 'rpm', 'cpm'];

    public function __construct(private readonly IntegerMetricParser $integers, private readonly DecimalMetricParser $decimals, private readonly PercentageParser $percentages, private readonly CurrencyParser $currency, private readonly DurationParser $duration, private readonly DateTimeParser $dates) {}

    public function normalize(array $raw, array $mapping, string $timezone): array
    {
        $data = [];
        foreach ($mapping as $source => $target) {
            if ($target === null || $target === '' || ! array_key_exists($source, $raw)) {
                continue;
            }
            $value = $raw[$source];
            $data[$target] = match (true) {
                in_array($target, self::INTEGER_FIELDS, true) => $this->integers->parse($value),
                in_array($target, ['subscribers_gained', 'subscribers_lost'], true) => $this->integers->parse($value, true),
                in_array($target, self::PERCENT_FIELDS, true) => $this->percentages->parse($value),
                in_array($target, self::CURRENCY_FIELDS, true) => $this->currency->parse($value, true),
                $target === 'watch_time_minutes' => $this->decimals->parse($value),
                $target === 'watch_time_hours' => ($hours = $this->decimals->parse($value)) === null ? null : (string) ((float) $hours * 60),
                in_array($target, ['duration', 'average_view_duration_seconds'], true) => $this->duration->parse($value),
                $target === 'published_at' => $this->dates->parse($value, $timezone)?->toIso8601String(),
                default => $this->text($value),
            };
            if ($target === 'watch_time_hours') {
                $data['watch_time_minutes'] = $data[$target];
                unset($data[$target]);
            }
        }
        if (blank($data['title'] ?? null)) {
            throw new \InvalidArgumentException('Title is required.');
        }

        return $data;
    }

    private function text(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || in_array(strtolower($value), ['n/a', 'na', 'null', '—', '-'], true) ? null : $value;
    }
}
