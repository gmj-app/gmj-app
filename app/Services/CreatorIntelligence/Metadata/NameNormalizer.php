<?php

namespace App\Services\CreatorIntelligence\Metadata;

use Illuminate\Support\Str;

class NameNormalizer
{
    public function normalize(string $name): string
    {
        return mb_strtolower(trim(preg_replace('/\s+/u', ' ', $name) ?? $name), 'UTF-8');
    }

    public function slug(string $name): string
    {
        return Str::slug(trim(preg_replace('/\s+/u', ' ', $name) ?? $name));
    }
}
