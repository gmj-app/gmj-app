<?php

namespace App\Services\CreatorIntelligence\Import\Parsers;

class PercentageParser extends DecimalMetricParser
{
    public function parse(mixed $value, bool $allowNegative = false): ?string
    {
        return parent::parse(rtrim(trim((string) $value), "% \t\n\r\0\x0B"), $allowNegative);
    }
}
