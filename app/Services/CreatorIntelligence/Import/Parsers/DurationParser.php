<?php

namespace App\Services\CreatorIntelligence\Import\Parsers;

use DateInterval;
use InvalidArgumentException;

class DurationParser extends MetricParser
{
    public function parse(mixed $value): ?int
    {
        if (($value = $this->nullable($value)) === null) {
            return null;
        }
        if (ctype_digit($value)) {
            return (int) $value;
        }
        if (preg_match('/^(?:(\d+):)?(\d{1,2}):(\d{2})$/', $value, $matches)) {
            return ((int) ($matches[1] ?: 0) * 3600) + ((int) $matches[2] * 60) + (int) $matches[3];
        }
        if (str_starts_with(strtoupper($value), 'P')) {
            try {
                $interval = new DateInterval(strtoupper($value));

                return ($interval->d * 86400) + ($interval->h * 3600) + ($interval->i * 60) + $interval->s;
            } catch (\Throwable) {
            }
        }
        throw new InvalidArgumentException("Invalid duration value: {$value}");
    }
}
