<?php

namespace App\Services\CreatorIntelligence\Import\Parsers;

use InvalidArgumentException;

abstract class MetricParser
{
    protected function nullable(mixed $value): ?string
    {
        $value = trim((string) $value);

        return $value === '' || in_array(strtolower($value), ['n/a', 'na', 'null', '—', '-'], true) ? null : $value;
    }

    protected function numeric(string $value): string
    {
        $normalized = str_replace([',', ' '], '', $value);
        if (! is_numeric($normalized)) {
            throw new InvalidArgumentException("Invalid numeric value: {$value}");
        }

        return $normalized;
    }
}
