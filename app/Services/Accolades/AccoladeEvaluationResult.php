<?php

namespace App\Services\Accolades;

use App\Models\UserAccolade;
use Illuminate\Support\Collection;

final readonly class AccoladeEvaluationResult
{
    /**
     * @param  Collection<int, UserAccolade>  $newAwards
     * @param  array<string, array<string, mixed>>  $tracks
     */
    public function __construct(
        public string $subjectType,
        public int $subjectId,
        public Collection $newAwards,
        public array $tracks,
    ) {}
}
