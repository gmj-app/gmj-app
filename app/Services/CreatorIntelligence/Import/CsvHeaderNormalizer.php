<?php

namespace App\Services\CreatorIntelligence\Import;

class CsvHeaderNormalizer
{
    public function normalize(string $header): string
    {
        return trim(preg_replace('/\s+/u', ' ', preg_replace('/^\xEF\xBB\xBF/', '', $header)) ?? $header);
    }
}
