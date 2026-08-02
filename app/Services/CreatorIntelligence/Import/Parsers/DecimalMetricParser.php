<?php

namespace App\Services\CreatorIntelligence\Import\Parsers;

use InvalidArgumentException;

class DecimalMetricParser extends MetricParser
{
    public function parse(mixed $value, bool $allowNegative = false): ?string
    {
        if (($value = $this->nullable($value)) === null) {
            return null;
        }
        $numeric = $this->numeric($value);
        if (! $allowNegative && (float) $numeric < 0) {
            throw new InvalidArgumentException('Metric values may not be negative.');
        }

        return $numeric;
    }
}
