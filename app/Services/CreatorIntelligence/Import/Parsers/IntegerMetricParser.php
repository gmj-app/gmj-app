<?php

namespace App\Services\CreatorIntelligence\Import\Parsers;

use InvalidArgumentException;

class IntegerMetricParser extends MetricParser
{
    public function parse(mixed $value, bool $allowNegative = false): ?int
    {
        if (($value = $this->nullable($value)) === null) {
            return null;
        }
        $numeric = $this->numeric($value);
        if (filter_var($numeric, FILTER_VALIDATE_INT) === false) {
            throw new InvalidArgumentException("Invalid integer value: {$value}");
        }
        $integer = (int) $numeric;
        if (! $allowNegative && $integer < 0) {
            throw new InvalidArgumentException('Metric values may not be negative.');
        }

        return $integer;
    }
}
