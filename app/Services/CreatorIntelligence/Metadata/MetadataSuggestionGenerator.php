<?php

namespace App\Services\CreatorIntelligence\Metadata;

use App\Models\CreatorChannel;
use App\Models\CreatorVideo;
use App\Models\MetadataSuggestion;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class MetadataSuggestionGenerator
{
    public const CONFIDENCE_SCORES = ['high' => 0.95, 'medium' => 0.75, 'low' => 0.45];

    public function generate(Builder $query, bool $dryRun = false, string $minimumConfidence = 'low', int $chunk = 100): array
    {
        $result = ['processed' => 0, 'created' => 0, 'updated' => 0, 'skipped' => 0, 'subject' => ['high' => 0, 'medium' => 0, 'low' => 0], 'content_item' => ['high' => 0, 'medium' => 0, 'low' => 0], 'unresolved' => 0];
        $minimum = self::CONFIDENCE_SCORES[$minimumConfidence] ?? 0.45;
        $query->with(['channel.subjects', 'channel.contentItems', 'primarySubject', 'primaryContentItem'])->chunkById($chunk, function (Collection $videos) use (&$result, $dryRun, $minimum): void {
            foreach ($videos as $video) {
                $result['processed']++;
                $suggestions = array_filter([$this->subjectSuggestion($video), $this->contentItemSuggestion($video)]);
                if ($suggestions === []) {
                    $result['unresolved']++;
                }
                foreach ($suggestions as $data) {
                    if ($data['confidence_score'] < $minimum) {
                        $result['skipped']++;

                        continue;
                    }
                    $result[$data['suggestion_type']][$data['confidence']]++;
                    $fingerprint = $this->fingerprint($video, $data['suggestion_type']);
                    $existing = MetadataSuggestion::where('creator_video_id', $video->id)->where('suggestion_type', $data['suggestion_type'])->first();
                    if ($existing && $existing->status !== 'pending' && hash_equals($existing->source_fingerprint, $fingerprint)) {
                        $result['skipped']++;

                        continue;
                    }
                    if ($dryRun) {
                        $result[$existing ? 'updated' : 'created']++;

                        continue;
                    }
                    MetadataSuggestion::updateOrCreate(
                        ['creator_video_id' => $video->id, 'suggestion_type' => $data['suggestion_type']],
                        [...$data, 'source_fingerprint' => $fingerprint, 'status' => 'pending', 'reviewed_by_user_id' => null, 'reviewed_at' => null]
                    );
                    $result[$existing ? 'updated' : 'created']++;
                }
            }
        });

        return $result;
    }

    public function fingerprint(CreatorVideo $video, string $type): string
    {
        return hash('sha256', json_encode([$video->title, $type, $this->settings($video->channel), $video->channel->subjects->map->only(['id', 'name', 'aliases']), $video->channel->contentItems->map->only(['id', 'name', 'aliases'])], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR));
    }

    private function subjectSuggestion(CreatorVideo $video): ?array
    {
        $matches = [];
        foreach ($video->channel->subjects as $subject) {
            foreach ([$subject->name, ...($subject->aliases ?? [])] as $name) {
                $match = $this->matchName($video->title, $name);
                if ($match) {
                    $matches[] = ['subject' => $subject, ...$match, 'name' => $name];
                }
            }
        }
        usort($matches, fn ($a, $b) => $b['score'] <=> $a['score'] ?: mb_strlen($b['name']) <=> mb_strlen($a['name']));
        if (! isset($matches[0]) || (isset($matches[1]) && $matches[0]['score'] === $matches[1]['score'] && $matches[0]['subject']->id !== $matches[1]['subject']->id)) {
            return null;
        }
        $match = $matches[0];

        return ['suggestion_type' => 'subject', 'suggested_subject_id' => $match['subject']->id, 'suggested_content_item_id' => null, 'suggested_display_value' => $match['subject']->name, 'confidence' => $match['confidence'], 'confidence_score' => $match['score'], 'rule_name' => $match['rule'], 'evidence' => ['matched' => $match['name'], 'title' => $video->title]];
    }

    private function contentItemSuggestion(CreatorVideo $video): ?array
    {
        $candidate = $this->extractContentItem($video->title, $video->channel);
        if (! $candidate) {
            return null;
        }
        foreach ($video->channel->contentItems as $item) {
            foreach ([$item->name, ...($item->aliases ?? [])] as $name) {
                if ($this->normalized($candidate['value']) === $this->normalized($name)) {
                    return ['suggestion_type' => 'content_item', 'suggested_subject_id' => null, 'suggested_content_item_id' => $item->id, 'suggested_display_value' => $item->name, 'confidence' => 'high', 'confidence_score' => 0.96, 'rule_name' => 'existing_content_item', 'evidence' => ['candidate' => $candidate['value'], 'pattern' => $candidate['rule']]];
                }
            }
        }

        return ['suggestion_type' => 'content_item', 'suggested_subject_id' => null, 'suggested_content_item_id' => null, 'suggested_display_value' => $candidate['value'], 'confidence' => $candidate['confidence'], 'confidence_score' => self::CONFIDENCE_SCORES[$candidate['confidence']], 'rule_name' => $candidate['rule'], 'evidence' => ['candidate' => $candidate['value'], 'title' => $video->title]];
    }

    public function extractContentItem(string $title, CreatorChannel $channel): ?array
    {
        if (preg_match('/["“”‘’\']([^"“”‘’\']{2,100})["“”‘’\']/u', $title, $match)) {
            return ['value' => trim($match[1]), 'confidence' => 'high', 'rule' => 'quoted_title'];
        }
        $parts = array_values(array_filter(array_map('trim', preg_split('/\s*\|\s*/u', $title) ?: [])));
        if (count($parts) >= 2) {
            return ['value' => $this->stripPackaging($parts[1], $channel), 'confidence' => 'medium', 'rule' => 'pipe_segment'];
        }
        if (preg_match('/^.+?\s+[–—-]\s+(.+)$/u', $title, $match)) {
            $value = $this->stripPackaging($match[1], $channel);
            if ($value !== '') {
                return ['value' => $value, 'confidence' => 'medium', 'rule' => 'artist_separator'];
            }
        }
        $stripped = $this->stripPackaging($title, $channel);
        if ($stripped !== $title && $stripped !== '') {
            return ['value' => $stripped, 'confidence' => 'low', 'rule' => 'packaging_suffix'];
        }

        return null;
    }

    private function matchName(string $title, string $name): ?array
    {
        $name = trim($name);
        if ($name === '') {
            return null;
        }
        if (preg_match('/(?<![\p{L}\p{N}])'.preg_quote($name, '/').'(?![\p{L}\p{N}])/iu', $title) === 1) {
            return ['confidence' => 'high', 'score' => 0.98, 'rule' => 'token_boundary'];
        }
        $normalizedTitle = $this->normalized($title);
        $normalizedName = $this->normalized($name);
        if (mb_strlen($normalizedName) >= 4 && preg_match('/(?:^| )'.preg_quote($normalizedName, '/').'(?: |$)/u', $normalizedTitle) === 1) {
            return ['confidence' => 'medium', 'score' => 0.78, 'rule' => 'punctuation_normalized'];
        }

        return null;
    }

    private function stripPackaging(string $value, CreatorChannel $channel): string
    {
        foreach ($this->settings($channel)['packaging_suffixes'] ?? [] as $suffix) {
            $value = preg_replace('/(?:\s*[|:–—-]\s*|\s+)'.preg_quote($suffix, '/').'\s*$/iu', '', trim($value)) ?? $value;
        }

        return trim($value, " \t\n\r\0\x0B|:-–—");
    }

    private function settings(CreatorChannel $channel): array
    {
        $key = ltrim(mb_strtolower((string) ($channel->handle ?: $channel->channel_name), 'UTF-8'), '@');
        $configured = config('creator-intelligence.channel_parsers.'.$key, []);

        return array_replace_recursive($configured, $channel->metadata_parser_settings ?? []);
    }

    private function normalized(string $value): string
    {
        return trim(preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower($value, 'UTF-8')) ?? $value);
    }
}
