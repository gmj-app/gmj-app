<?php

namespace App\Support;

use App\Models\CreatorChannel;
use Illuminate\Support\Str;

final readonly class CreatorIntelligenceLabels
{
    public function __construct(
        public string $subject,
        public string $contentItem,
        public string $category,
    ) {}

    public static function for(?CreatorChannel $channel): self
    {
        return new self(
            self::clean($channel?->subject_label, 'Subject'),
            self::clean($channel?->content_item_label, 'Content Item'),
            self::clean($channel?->category_label, 'Category'),
        );
    }

    public function subjectPlural(): string
    {
        return Str::plural($this->subject);
    }

    public function contentItemPlural(): string
    {
        return Str::plural($this->contentItem);
    }

    public function categoryPlural(): string
    {
        return Str::plural($this->category);
    }

    public function subjectCount(int $count): string
    {
        return $this->count($count, $this->subject, $this->subjectPlural());
    }

    public function contentItemCount(int $count): string
    {
        return $this->count($count, $this->contentItem, $this->contentItemPlural());
    }

    public function videoCount(int $count): string
    {
        return $this->count($count, 'Video', 'Videos');
    }

    public function lowerSubject(): string
    {
        return Str::lower($this->subject);
    }

    public function lowerSubjects(): string
    {
        return Str::lower($this->subjectPlural());
    }

    public function lowerContentItem(): string
    {
        return Str::lower($this->contentItem);
    }

    public function lowerContentItems(): string
    {
        return Str::lower($this->contentItemPlural());
    }

    private function count(int $count, string $singular, string $plural): string
    {
        return number_format($count).' '.Str::lower($count === 1 ? $singular : $plural);
    }

    private static function clean(?string $label, string $fallback): string
    {
        $label = trim((string) $label);

        return $label === '' ? $fallback : $label;
    }
}
