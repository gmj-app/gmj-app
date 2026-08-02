<?php

namespace App\Console\Commands;

use App\Models\ContentItem;
use App\Models\CreatorChannel;
use App\Models\Subject;
use App\Models\SuperAdminAuditLog;
use App\Services\CreatorIntelligence\Metadata\NameNormalizer;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

class RepairCreatorIntelligenceDisplayNames extends Command
{
    protected $signature = 'creator-intelligence:repair-display-names
        {--channel= : Creator channel ID, platform channel ID, or handle}
        {--subject= : Repair one subject ID}
        {--content-item= : Repair one content item ID}
        {--dry-run : Report recoverable changes without writing them}';

    protected $description = 'Safely recover Creator Intelligence display capitalization from audit data or deterministic linked video titles';

    public function handle(NameNormalizer $normalizer): int
    {
        $channelId = $this->resolveChannelId();
        if ($this->option('channel') !== null && $channelId === null) {
            $this->error('The requested creator channel was not found.');

            return self::FAILURE;
        }

        $stats = ['updated' => 0, 'skipped' => 0, 'ambiguous' => 0, 'manual' => 0];
        $subjectId = $this->option('subject');
        $contentItemId = $this->option('content-item');

        if ($subjectId !== null || $contentItemId === null) {
            foreach ($this->subjectQuery($channelId, $subjectId)->cursor() as $subject) {
                $this->repair($subject, $normalizer, $stats);
            }
        }
        if ($contentItemId !== null || $subjectId === null) {
            foreach ($this->contentItemQuery($channelId, $contentItemId)->cursor() as $item) {
                $this->repair($item, $normalizer, $stats);
            }
        }

        $verb = $this->option('dry-run') ? 'Recoverable' : 'Updated';
        $this->info("{$verb}: {$stats['updated']}");
        $this->line("Skipped: {$stats['skipped']}");
        $this->line("Ambiguous: {$stats['ambiguous']}");
        $this->line("Manual review: {$stats['manual']}");

        return self::SUCCESS;
    }

    private function repair(Subject|ContentItem $record, NameNormalizer $normalizer, array &$stats): void
    {
        $auditCandidates = $this->auditCandidates($record, $normalizer);
        $candidates = $auditCandidates->isNotEmpty() ? $auditCandidates : $this->titleCandidates($record, $normalizer);
        $type = $record instanceof Subject ? 'subject' : 'content item';

        if ($candidates->count() > 1) {
            $stats['ambiguous']++;
            $this->warn(ucfirst($type)." #{$record->getKey()} is ambiguous: ".$candidates->implode(', '));

            return;
        }

        $candidate = $candidates->first();
        if ($candidate === null) {
            if ($record->name === $record->normalized_name) {
                $stats['manual']++;
                $this->warn(ucfirst($type)." #{$record->getKey()} ({$record->name}) needs manual review.");
            } else {
                $stats['skipped']++;
            }

            return;
        }

        if ($candidate === $record->name) {
            $stats['skipped']++;

            return;
        }

        $stats['updated']++;
        $this->line(($this->option('dry-run') ? 'Would update' : 'Updated')." {$type} #{$record->getKey()}: {$record->name} -> {$candidate}");
        if (! $this->option('dry-run')) {
            $record->update(['name' => $candidate]);
        }
    }

    private function auditCandidates(Model $record, NameNormalizer $normalizer): Collection
    {
        return SuperAdminAuditLog::query()
            ->where('auditable_type', $record->getMorphClass())
            ->where('auditable_id', $record->getKey())
            ->latest('id')
            ->get()
            ->flatMap(fn (SuperAdminAuditLog $log) => [
                data_get($log->after_data, 'name'),
                data_get($log->before_data, 'name'),
                data_get($log->metadata, 'submitted_name'),
                data_get($log->metadata, 'original_name'),
            ])
            ->filter(fn ($candidate) => is_string($candidate) && $normalizer->normalize($candidate) === $record->normalized_name)
            ->map(fn (string $candidate) => $normalizer->display($candidate))
            ->reject(fn (string $candidate) => $candidate === $record->name)
            ->uniqueStrict()
            ->values();
    }

    private function titleCandidates(Subject|ContentItem $record, NameNormalizer $normalizer): Collection
    {
        $words = preg_split('/\s+/u', preg_quote($record->normalized_name, '/')) ?: [];
        $pattern = '/(?<![\pL\pN])('.implode('\\s+', $words).')(?![\pL\pN])/iu';

        return $record->videos()->pluck('title')->flatMap(function (string $title) use ($pattern): array {
            preg_match_all($pattern, $title, $matches);

            return $matches[1] ?? [];
        })->filter(fn (string $candidate) => $normalizer->normalize($candidate) === $record->normalized_name)
            ->map(fn (string $candidate) => $normalizer->display($candidate))
            ->uniqueStrict()
            ->values();
    }

    private function resolveChannelId(): ?int
    {
        $channel = $this->option('channel');
        if ($channel === null) {
            return null;
        }

        return CreatorChannel::query()
            ->where(fn (Builder $query) => $query->whereKey($channel)->orWhere('platform_channel_id', $channel)->orWhere('handle', $channel))
            ->value('id');
    }

    private function subjectQuery(?int $channelId, mixed $subjectId): Builder
    {
        return Subject::query()->when($channelId, fn (Builder $query) => $query->where('creator_channel_id', $channelId))->when($subjectId, fn (Builder $query) => $query->whereKey($subjectId));
    }

    private function contentItemQuery(?int $channelId, mixed $contentItemId): Builder
    {
        return ContentItem::query()->when($channelId, fn (Builder $query) => $query->where('creator_channel_id', $channelId))->when($contentItemId, fn (Builder $query) => $query->whereKey($contentItemId));
    }
}
