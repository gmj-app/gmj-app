<?php

namespace App\Console\Commands;

use App\Models\Creator;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Collection;

class SoftDeleteCreator extends Command
{
    protected $signature = 'creators:soft-delete {slugOrId : Creator id, slug, or exact display name}';

    protected $description = 'Soft-delete a creator profile without deleting users, recommendations, votes, or uploads.';

    public function handle(): int
    {
        $slugOrId = trim((string) $this->argument('slugOrId'));

        if ($slugOrId === '') {
            $this->error('Provide a creator id, slug, or exact display name.');

            return self::FAILURE;
        }

        $creators = $this->matchingCreators($slugOrId);

        if ($creators->isEmpty()) {
            $this->error("No creator found for [{$slugOrId}].");

            return self::FAILURE;
        }

        if ($creators->count() > 1) {
            $this->error("Multiple creators matched [{$slugOrId}]. Use the creator id instead.");

            return self::FAILURE;
        }

        $creator = $creators->first();

        if ($creator->trashed()) {
            $this->line("Creator #{$creator->id} ({$creator->display_name}) is already soft-deleted.");

            return self::SUCCESS;
        }

        $creator->delete();

        $this->info("Soft-deleted creator #{$creator->id} ({$creator->display_name}).");
        $this->line('User accounts, recommendations, votes, and uploads were not deleted.');

        return self::SUCCESS;
    }

    /**
     * @return Collection<int, Creator>
     */
    private function matchingCreators(string $slugOrId): Collection
    {
        return Creator::withTrashed()
            ->when(ctype_digit($slugOrId), fn ($query) => $query->where('id', (int) $slugOrId))
            ->when(! ctype_digit($slugOrId), fn ($query) => $query
                ->where('slug', $slugOrId)
                ->orWhere('display_name', $slugOrId))
            ->get();
    }
}
