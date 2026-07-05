<?php

namespace App\Services\Youtube;

class DescriptionUpdateOptions
{
    public function __construct(
        public readonly string $appendText = '',
        public readonly ?string $findText = null,
        public readonly ?string $replaceText = null,
        public readonly bool $appendOnlyIfMissing = true,
        public readonly bool $addSeparator = true,
    ) {}

    public function action(): string
    {
        return $this->isReplacement() ? 'replace' : 'append';
    }

    public function isReplacement(): bool
    {
        return filled($this->findText);
    }
}
