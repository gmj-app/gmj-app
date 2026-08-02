<?php

namespace App\Services\CreatorIntelligence\Metadata;

use App\Models\CreatorVideo;

class TitleMetadataParser
{
    public function parse(string $title): array
    {
        preg_match_all('/[\p{L}\p{N}]+(?:[\'’\-][\p{L}\p{N}]+)*/u', $title, $matches);
        $tokens = $matches[0] ?? [];
        $caps = collect($tokens)->contains(function (string $token): bool {
            $letters = preg_replace('/[^\p{L}]/u', '', $token) ?? '';

            return mb_strlen($letters) >= 2 && $letters === mb_strtoupper($letters, 'UTF-8') && $letters !== mb_strtolower($letters, 'UTF-8');
        });

        return ['character_count' => mb_strlen($title, 'UTF-8'), 'word_count' => count($tokens), 'contains_question' => str_contains($title, '?'), 'contains_exclamation' => str_contains($title, '!'), 'contains_pipe' => str_contains($title, '|'), 'contains_parentheses' => preg_match('/[()]/u', $title) === 1, 'contains_all_caps' => $caps];
    }

    public function recalculate(CreatorVideo $video, bool $detectNames = false): void
    {
        $metadata = $video->titleMetadata()->firstOrNew();
        $metadata->fill($this->parse($video->title));
        if ($detectNames) {
            $fold = mb_strtolower($video->title, 'UTF-8');
            $metadata->subject_name_present = $video->subjects()->get()->contains(fn ($x) => $this->containsName($fold, $x->name));
            $metadata->content_item_name_present = $video->contentItems()->get()->contains(fn ($x) => $this->containsName($fold, $x->name));
        }
        $metadata->save();
    }

    private function containsName(string $title, string $name): bool
    {
        $name = mb_strtolower(trim($name), 'UTF-8');
        if (mb_strlen($name) < 3) {
            return preg_match('/(?<![\p{L}\p{N}])'.preg_quote($name, '/').'(?![\p{L}\p{N}])/u', $title) === 1;
        }

        return str_contains($title, $name);
    }
}
