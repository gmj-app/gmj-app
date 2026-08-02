<?php

namespace App\Services\CreatorIntelligence\Import;

use Generator;
use RuntimeException;

class AnalyticsCsvReader
{
    public function __construct(private readonly CsvHeaderNormalizer $headers) {}

    public function rows($stream): Generator
    {
        $header = fgetcsv($stream, config('creator_intelligence.max_csv_line_bytes'));
        if (! is_array($header) || $header === []) {
            throw new RuntimeException('The CSV header could not be read.');
        }
        $header = array_map(fn ($value) => $this->headers->normalize((string) $value), $header);
        foreach ($header as $value) {
            if (! mb_check_encoding($value, 'UTF-8') || str_contains($value, "\0")) {
                throw new RuntimeException('The CSV header contains binary or invalid text.');
            }
        }
        if (count(array_filter($header, fn ($value) => $value !== '')) === 0) {
            throw new RuntimeException('The CSV header is empty.');
        }

        $rowNumber = 1;
        while (($values = fgetcsv($stream, config('creator_intelligence.max_csv_line_bytes'))) !== false) {
            $rowNumber++;
            if (count(array_filter($values, fn ($value) => trim((string) $value) !== '')) === 0) {
                yield ['row_number' => $rowNumber, 'data' => [], 'error' => null];

                continue;
            }
            if (count($values) !== count($header)) {
                yield ['row_number' => $rowNumber, 'data' => null, 'error' => 'Column count does not match the header.'];

                continue;
            }
            yield ['row_number' => $rowNumber, 'data' => array_combine($header, $values), 'error' => null];
        }
    }

    public function header($stream): array
    {
        $header = fgetcsv($stream, config('creator_intelligence.max_csv_line_bytes'));
        if (! is_array($header)) {
            throw new RuntimeException('The CSV header could not be read.');
        }

        return array_map(fn ($value) => $this->headers->normalize((string) $value), $header);
    }
}
