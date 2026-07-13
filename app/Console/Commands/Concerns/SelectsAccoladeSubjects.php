<?php

namespace App\Console\Commands\Concerns;

use App\Models\Creator;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

trait SelectsAccoladeSubjects
{
    /** @return Builder<User> */
    private function guideQuery(): Builder
    {
        return User::query()
            ->when($this->option('user'), fn (Builder $query, $id) => $query->whereKey($id))
            ->when($this->option('email'), fn (Builder $query, $email) => $query->where('email', $email));
    }

    /** @return Builder<Creator> */
    private function creatorQuery(): Builder
    {
        return Creator::query()->with('owners')
            ->when($this->option('creator'), function (Builder $query, $value): void {
                $query->where(is_numeric($value) ? 'id' : 'slug', $value);
            });
    }

    /** @return array<int, string>|null */
    private function selectedTracks(): ?array
    {
        return $this->option('track') ? [(string) $this->option('track')] : null;
    }

    /** @return array<int, string> */
    private function selectedSubjectTypes(): array
    {
        if (! $this->option('subject')) {
            if ($this->option('user') || $this->option('email')) {
                return ['guide'];
            }
            if ($this->option('creator')) {
                return ['creator'];
            }
            if ($this->option('track') && ($type = config('accolades.tracks.'.$this->option('track').'.subject_type'))) {
                return [$type];
            }
        }

        return match ($this->option('subject')) {
            'guides' => ['guide'],
            'creators' => ['creator'],
            null, '' => ['guide', 'creator'],
            default => ['guide', 'creator'],
        };
    }
}
