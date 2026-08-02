<?php

namespace App\Services\CreatorIntelligence\Videos;

class MetricFormatter
{
    public function count(mixed $value): string
    {
        return $value === null ? '—' : number_format((float) $value, 0);
    }

    public function percentage(mixed $value): string
    {
        return $value === null ? '—' : rtrim(rtrim(number_format((float) $value, 2, '.', ''), '0'), '.').'%';
    }

    public function decimal(mixed $value, int $precision = 2): string
    {
        return $value === null ? '—' : number_format((float) $value, $precision);
    }

    public function currency(mixed $value, string $currency): string
    {
        if ($value === null) {
            return '—';
        }

        $currency = strtoupper($currency);
        $prefix = match ($currency) {
            'USD' => '$',
            'EUR' => '€',
            'GBP' => '£',
            'JPY', 'CNY' => '¥',
            'CAD' => 'CA$',
            'AUD' => 'A$',
            default => $currency.' ',
        };

        return $prefix.number_format((float) $value, 2);
    }

    public function duration(mixed $seconds): string
    {
        if ($seconds === null) {
            return '—';
        }
        $seconds = (int) $seconds;
        $hours = intdiv($seconds, 3600);
        $minutes = intdiv($seconds % 3600, 60);
        $remaining = $seconds % 60;

        return $hours > 0 ? sprintf('%02d:%02d:%02d', $hours, $minutes, $remaining) : sprintf('%02d:%02d', $minutes, $remaining);
    }

    public function boolean(?bool $value): string
    {
        return $value === null ? 'Unknown' : ($value ? 'Yes' : 'No');
    }
}
