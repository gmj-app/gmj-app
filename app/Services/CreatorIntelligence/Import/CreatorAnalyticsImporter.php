<?php

namespace App\Services\CreatorIntelligence\Import;

use App\Enums\ImportBatchStatus;
use App\Enums\ImportRowStatus;
use App\Models\CreatorVideo;
use App\Models\ImportBatch;
use App\Models\ImportBatchRow;
use App\Models\VideoPerformanceSnapshot;
use App\Services\CreatorIntelligence\Metadata\MetadataReviewInvalidator;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use InvalidArgumentException;

class CreatorAnalyticsImporter
{
    private const VIDEO_FIELDS = ['title', 'description', 'video_url', 'thumbnail_url', 'published_at'];

    private const METRIC_FIELDS = ['views', 'impressions', 'impressions_ctr', 'watch_time_minutes', 'average_view_duration_seconds', 'average_percentage_viewed', 'likes', 'comments', 'shares', 'subscribers_gained', 'subscribers_lost', 'estimated_revenue', 'rpm', 'cpm', 'hype_points', 'views_first_24_hours', 'views_first_7_days', 'views_first_28_days'];

    public function __construct(private readonly AnalyticsFileInspector $files, private readonly AnalyticsCsvReader $reader, private readonly CsvRowNormalizer $normalizer, private readonly MetadataReviewInvalidator $reviewInvalidator) {}

    public function import(ImportBatch $batch): void
    {
        $batch->update(['status' => ImportBatchStatus::Processing, 'started_at' => $batch->started_at ?? now(), 'completed_at' => null, 'error_summary' => null]);
        try {
            $this->files->withCsvStream($batch, function ($stream) use ($batch): void {
                foreach ($this->reader->rows($stream) as $row) {
                    if (ImportBatchRow::where('import_batch_id', $batch->id)->where('row_number', $row['row_number'])->where('status', '!=', ImportRowStatus::Pending->value)->exists()) {
                        continue;
                    }
                    $this->processRow($batch, $row);
                }
            });
            $this->finalize($batch);
        } catch (\Throwable $exception) {
            report($exception);
            $batch->update(['status' => ImportBatchStatus::Failed, 'error_summary' => $this->safeMessage($exception, 'The import could not be processed.'), 'completed_at' => now()]);
        }
    }

    private function processRow(ImportBatch $batch, array $row): void
    {
        $raw = $row['data'] ?? [];
        if ($row['error']) {
            $this->record($batch, $row['row_number'], $raw, null, ImportRowStatus::Failed, $row['error']);

            return;
        }
        if ($this->empty($raw)) {
            $this->record($batch, $row['row_number'], $raw, null, ImportRowStatus::Skipped, 'Empty row.');

            return;
        }
        if ($this->aggregate($raw)) {
            $this->record($batch, $row['row_number'], $raw, null, ImportRowStatus::Skipped, 'Aggregate total row.');

            return;
        }

        try {
            $normalized = $this->normalizer->normalize($raw, $batch->column_mapping ?? [], $batch->channel->default_publish_timezone);
            DB::transaction(function () use ($batch, $row, $raw, $normalized): void {
                [$video, $videoCreated] = $this->upsertVideo($batch, $normalized);
                $snapshot = VideoPerformanceSnapshot::where('creator_video_id', $video->id)
                    ->whereDate('snapshot_date', $batch->snapshot_date->toDateString())
                    ->where('source', $batch->source->value)
                    ->first();
                $snapshotCreated = $snapshot === null;
                $snapshot ??= new VideoPerformanceSnapshot(['creator_video_id' => $video->id, 'snapshot_date' => $batch->snapshot_date, 'source' => $batch->source->value]);
                foreach (self::METRIC_FIELDS as $field) {
                    if (array_key_exists($field, $normalized) && $normalized[$field] !== null) {
                        $snapshot->{$field} = $normalized[$field];
                    }
                }
                $snapshot->save();
                $status = $snapshotCreated ? ImportRowStatus::Created : ImportRowStatus::Updated;
                $this->record($batch, $row['row_number'], $raw, $normalized, $status, $videoCreated ? 'Created video and snapshot.' : ($snapshotCreated ? 'Created snapshot.' : 'Updated snapshot.'), $video, $snapshot);
            });
        } catch (\Throwable $exception) {
            report($exception);
            $this->record($batch, $row['row_number'], $raw, $normalized ?? null, ImportRowStatus::Failed, $this->safeMessage($exception, 'The row could not be imported.'));
        }
    }

    private function upsertVideo(ImportBatch $batch, array $data): array
    {
        $platformId = $data['platform_video_id'] ?? null;
        $published = isset($data['published_at']) ? CarbonImmutable::parse($data['published_at']) : null;
        if ($platformId) {
            $video = CreatorVideo::where('creator_channel_id', $batch->creator_channel_id)->where('platform_video_id', $platformId)->first();
        } else {
            if (! $published) {
                throw new InvalidArgumentException('A platform video ID or published date is required for safe duplicate matching.');
            }
            $normalizedTitle = $this->normalizedTitle($data['title']);
            $matches = CreatorVideo::where('creator_channel_id', $batch->creator_channel_id)->whereDate('published_at', $published->toDateString())->get()->filter(fn ($candidate) => $this->normalizedTitle($candidate->title) === $normalizedTitle);
            if ($matches->count() > 1) {
                throw new InvalidArgumentException('Multiple videos match the title and published date; the row is ambiguous.');
            }
            $video = $matches->first();
            $platformId = 'fingerprint:'.hash('sha256', $batch->creator_channel_id.'|'.$normalizedTitle.'|'.$published->toDateString());
        }
        $created = $video === null;
        $video ??= new CreatorVideo(['creator_channel_id' => $batch->creator_channel_id, 'platform_video_id' => $platformId]);
        foreach (self::VIDEO_FIELDS as $field) {
            if (array_key_exists($field, $data) && $data[$field] !== null) {
                $video->{$field === 'duration' ? 'duration_seconds' : $field} = $data[$field];
            }
        }
        if (array_key_exists('duration', $data) && $data['duration'] !== null) {
            $video->duration_seconds = $data['duration'];
        }
        $titleChanged = $video->isDirty('title');
        $thumbnailChanged = $video->isDirty('thumbnail_url');
        $video->save();
        $this->reviewInvalidator->apply($video, $titleChanged, $thumbnailChanged);

        return [$video, $created];
    }

    private function record(ImportBatch $batch, int $rowNumber, array $raw, ?array $normalized, ImportRowStatus $status, string $message, ?CreatorVideo $video = null, ?VideoPerformanceSnapshot $snapshot = null): void
    {
        ImportBatchRow::updateOrCreate(['import_batch_id' => $batch->id, 'row_number' => $rowNumber], ['raw_data' => $raw, 'normalized_data' => $normalized, 'status' => $status, 'creator_video_id' => $video?->id, 'video_performance_snapshot_id' => $snapshot?->id, 'message' => Str::limit($message, 65000, '')]);
    }

    private function finalize(ImportBatch $batch): void
    {
        $counts = $batch->rows()->selectRaw('status, count(*) as aggregate')->groupBy('status')->pluck('aggregate', 'status');
        $created = (int) ($counts[ImportRowStatus::Created->value] ?? 0);
        $updated = (int) ($counts[ImportRowStatus::Updated->value] ?? 0);
        $failed = (int) ($counts[ImportRowStatus::Failed->value] ?? 0);
        $batch->update(['total_rows' => (int) $counts->sum(), 'successful_rows' => $created + $updated, 'created_rows' => $created, 'updated_rows' => $updated, 'skipped_rows' => (int) ($counts[ImportRowStatus::Skipped->value] ?? 0), 'failed_rows' => $failed, 'status' => $failed ? ImportBatchStatus::CompletedWithErrors : ImportBatchStatus::Completed, 'completed_at' => now()]);
    }

    private function normalizedTitle(string $title): string
    {
        return Str::lower(trim(preg_replace('/\s+/u', ' ', $title) ?? $title));
    }

    private function empty(array $row): bool
    {
        return count(array_filter($row, fn ($value) => trim((string) $value) !== '')) === 0;
    }

    private function aggregate(array $row): bool
    {
        $first = trim((string) reset($row));

        return strcasecmp($first, 'total') === 0 || strcasecmp(trim((string) ($row['Video title'] ?? '')), 'total') === 0;
    }

    private function safeMessage(\Throwable $exception, string $fallback): string
    {
        return $exception instanceof InvalidArgumentException || $exception instanceof \RuntimeException ? $exception->getMessage() : $fallback;
    }
}
