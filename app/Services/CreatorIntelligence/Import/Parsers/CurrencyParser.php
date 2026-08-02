<?php

namespace App\Services\CreatorIntelligence\Import\Parsers;

class CurrencyParser extends DecimalMetricParser
{
    public function parse(mixed $value, bool $allowNegative = false): ?string
    {
        $clean = preg_replace('/^[A-Z]{3}\s*/i', '', trim((string) $value));
        $clean = preg_replace('/^[\$£€¥]/u', '', $clean ?? '');

        return parent::parse($clean, $allowNegative);
    }
}
