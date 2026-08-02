<?php

namespace App\Services\CreatorIntelligence\Import\Parsers;

use Carbon\CarbonImmutable;
use InvalidArgumentException;

class DateTimeParser extends MetricParser
{
    public function parse(mixed $value, string $timezone): ?CarbonImmutable
    {
        if (($value = $this->nullable($value)) === null) {
            return null;
        }
        try {
            return CarbonImmutable::parse($value, $timezone)->utc();
        } catch (\Throwable) {
            throw new InvalidArgumentException("Invalid date value: {$value}");
        }
    }
}
