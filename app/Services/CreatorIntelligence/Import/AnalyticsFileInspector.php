<?php

namespace App\Services\CreatorIntelligence\Import;

use App\Models\ImportBatch;
use RuntimeException;
use ZipArchive;

class AnalyticsFileInspector
{
    public function __construct(private readonly AnalyticsCsvReader $reader, private readonly CsvColumnMapper $mapper) {}

    public function inspect(ImportBatch $batch): array
    {
        return $this->withCsvStream($batch, function ($stream, ?string $selected): array {
            $columns = [];
            $preview = [];
            foreach ($this->reader->rows($stream) as $row) {
                if ($row['data'] !== null && ! $this->emptyRow($row['data'])) {
                    $columns = $columns ?: array_keys($row['data']);
                    $preview[] = $row['data'];
                }
                if (count($preview) === 10) {
                    break;
                }
            }
            if ($preview === []) {
                throw new RuntimeException('The selected CSV has no usable data rows.');
            }
            $mapping = $this->mapper->automatic($columns);

            return compact('columns', 'preview', 'mapping', 'selected');
        });
    }

    public function withCsvStream(ImportBatch $batch, callable $callback): mixed
    {
        $disk = \Storage::disk($batch->storage_disk);
        if (! $disk->exists($batch->storage_path)) {
            throw new RuntimeException('The stored import file is missing.');
        }
        $source = $disk->readStream($batch->storage_path);
        if (! is_resource($source)) {
            throw new RuntimeException('The stored import file cannot be read.');
        }
        $temporary = tempnam(sys_get_temp_dir(), 'gmj-import-');
        if ($temporary === false) {
            throw new RuntimeException('A temporary import file could not be created.');
        }
        $target = fopen($temporary, 'wb');
        stream_copy_to_stream($source, $target);
        fclose($source);
        fclose($target);

        try {
            if (strtolower(pathinfo($batch->original_filename, PATHINFO_EXTENSION)) === 'csv') {
                $stream = fopen($temporary, 'rb');
                try {
                    return $callback($stream, $batch->detected_csv_filename);
                } finally {
                    fclose($stream);
                }
            }
            $zip = new ZipArchive;
            if ($zip->open($temporary) !== true) {
                throw new RuntimeException('The ZIP archive is unreadable or invalid.');
            }
            try {
                $selected = $batch->detected_csv_filename ?: $this->selectCsv($zip);
                $stream = $zip->getStream($selected);
                if (! is_resource($stream)) {
                    throw new RuntimeException('The selected CSV cannot be read from the ZIP archive.');
                }
                try {
                    return $callback($stream, $selected);
                } finally {
                    fclose($stream);
                }
            } finally {
                $zip->close();
            }
        } finally {
            @unlink($temporary);
        }
    }

    private function selectCsv(ZipArchive $zip): string
    {
        if ($zip->numFiles > config('creator_intelligence.max_archive_entries')) {
            throw new RuntimeException('The ZIP archive contains too many entries.');
        }
        $candidates = [];
        $totalSize = 0;
        for ($i = 0; $i < $zip->numFiles; $i++) {
            $stat = $zip->statIndex($i);
            $name = (string) ($stat['name'] ?? '');
            if ($this->unsafePath($name)) {
                throw new RuntimeException('The ZIP archive contains an unsafe path.');
            }
            $totalSize += (int) ($stat['size'] ?? 0);
            if ($totalSize > config('creator_intelligence.max_archive_uncompressed_bytes')) {
                throw new RuntimeException('The ZIP archive expands beyond the allowed size.');
            }
            if (method_exists($zip, 'getEncryptionMethod') && $zip->getEncryptionMethod($i) !== ZipArchive::EM_NONE) {
                throw new RuntimeException('Password-protected ZIP archives are not supported.');
            }
            if (strtolower(pathinfo($name, PATHINFO_EXTENSION)) !== 'csv' || (int) ($stat['size'] ?? 0) === 0) {
                continue;
            }
            $stream = $zip->getStream($name);
            if (! is_resource($stream)) {
                continue;
            }
            try {
                $header = $this->reader->header($stream);
            } catch (\Throwable) {
                fclose($stream);

                continue;
            }
            fclose($stream);
            $dataStream = $zip->getStream($name);
            if (! is_resource($dataStream)) {
                continue;
            }
            try {
                if (! $this->hasUsableData($dataStream)) {
                    continue;
                }
            } finally {
                fclose($dataStream);
            }
            $mapping = $this->mapper->automatic($header);
            $base = strtolower(basename($name));
            if (in_array($base, ['totals.csv', 'total.csv'], true)) {
                continue;
            }
            $score = $base === 'table data.csv' ? 10000 : ((in_array('platform_video_id', $mapping, true) && in_array('title', $mapping, true)) ? 5000 : count($mapping) * 100);
            if ($base === 'chart data.csv') {
                $score -= 1000;
            }
            $candidates[] = ['name' => $name, 'score' => $score, 'size' => (int) $stat['size']];
        }
        if ($candidates === []) {
            throw new RuntimeException('No usable analytics CSV was found in the ZIP archive.');
        }
        usort($candidates, fn ($a, $b) => [$b['score'], $b['size']] <=> [$a['score'], $a['size']]);

        return $candidates[0]['name'];
    }

    private function unsafePath(string $path): bool
    {
        return str_contains(str_replace('\\', '/', $path), '../') || str_starts_with($path, '/') || preg_match('/^[A-Za-z]:[\\\\\/]/', $path) === 1;
    }

    private function emptyRow(array $row): bool
    {
        return count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0;
    }

    private function hasUsableData($stream): bool
    {
        foreach ($this->reader->rows($stream) as $row) {
            if ($row['data'] === null || $this->emptyRow($row['data'])) {
                continue;
            }
            if (strcasecmp(trim((string) reset($row['data'])), 'total') !== 0) {
                return true;
            }
        }

        return false;
    }
}
