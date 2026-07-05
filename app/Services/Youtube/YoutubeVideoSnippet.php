<?php

namespace App\Services\Youtube;

class YoutubeVideoSnippet
{
    /**
     * @param  array<string, mixed>  $snippet
     */
    public function __construct(
        public readonly string $id,
        public readonly array $snippet,
    ) {}

    public function title(): string
    {
        return (string) ($this->snippet['title'] ?? '');
    }

    public function description(): string
    {
        return (string) ($this->snippet['description'] ?? '');
    }

    public function categoryId(): string
    {
        return (string) ($this->snippet['categoryId'] ?? '');
    }
}
