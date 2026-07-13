<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\SelectsAccoladeSubjects;
use App\Services\Accolades\AccoladeEvaluationService;
use Illuminate\Console\Command;

class RebuildAccoladeProgress extends Command
{
    use SelectsAccoladeSubjects;

    protected $signature = 'accolades:rebuild-progress
        {--subject= : guides or creators}
        {--user= : Guide user ID}
        {--email= : Guide email}
        {--creator= : Creator ID or slug}
        {--track= : One track key}
        {--dry-run : Preview only}
        {--apply : Persist rebuilt progress}
        {--chunk=100 : Chunk size}';

    protected $description = 'Recalculate accolade progress without removing or granting earned accolades';

    public function handle(AccoladeEvaluationService $evaluation): int
    {
        if ($this->option('subject') && ! in_array($this->option('subject'), ['guides', 'creators'], true)) {
            $this->error('--subject must be guides or creators.');

            return self::INVALID;
        }
        if ($this->option('apply') && $this->option('dry-run')) {
            $this->error('Choose either --apply or --dry-run.');

            return self::INVALID;
        }
        $apply = (bool) $this->option('apply');
        if (! $apply) {
            $this->warn('Dry run: progress will not be changed. Use --apply to persist.');
        }
        $chunk = max(1, (int) $this->option('chunk'));
        $types = $this->selectedSubjectTypes();

        if (in_array('guide', $types, true)) {
            $this->guideQuery()->orderBy('id')->chunkById($chunk, function ($users) use ($evaluation, $apply): void {
                foreach ($users as $user) {
                    $result = $evaluation->evaluateGuide($user, $this->selectedTracks(), ['source' => 'progress_rebuild'], $apply, false);
                    $this->line("Guide #{$user->id}: ".collect($result->tracks)->map(fn ($data, $track) => "{$track}={$data['current_value']}")->implode(', '));
                }
            });
        }
        if (in_array('creator', $types, true)) {
            $this->creatorQuery()->orderBy('id')->chunkById($chunk, function ($creators) use ($evaluation, $apply): void {
                foreach ($creators as $creator) {
                    $result = $evaluation->evaluateCreator($creator, $this->selectedTracks(), ['source' => 'progress_rebuild'], $apply, false);
                    $this->line("Creator #{$creator->id}: ".collect($result->tracks)->map(fn ($data, $track) => "{$track}={$data['current_value']}")->implode(', '));
                }
            });
        }

        $this->info($apply ? 'Accolade progress rebuild complete.' : 'Accolade progress preview complete.');

        return self::SUCCESS;
    }
}
