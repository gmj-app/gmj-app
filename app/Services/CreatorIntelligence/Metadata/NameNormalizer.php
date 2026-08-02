<?php

namespace App\Services\CreatorIntelligence\Metadata;

use Illuminate\Support\Str;

class NameNormalizer
{
    public function display(string $name): string
    {
        return trim(preg_replace('/\s+/u', ' ', $name) ?? $name);
    }

    public function normalize(string $name): string
    {
        return mb_strtolower($this->display($name), 'UTF-8');
    }

    public function slug(string $name): string
    {
        return Str::slug($this->display($name));
    }
}
