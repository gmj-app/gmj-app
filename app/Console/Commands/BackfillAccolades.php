<?php

namespace App\Console\Commands;

use App\Console\Commands\Concerns\SelectsAccoladeSubjects;
use App\Services\Accolades\AccoladeDefinitionRepository;
use App\Services\Accolades\AccoladeEvaluationResult;
use App\Services\Accolades\AccoladeEvaluationService;
use Illuminate\Console\Command;

class BackfillAccolades extends Command
{
    use SelectsAccoladeSubjects;

    protected $signature = 'accolades:backfill
        {--subject= : guides or creators}
        {--user= : Guide user ID}
        {--email= : Guide email}
        {--creator= : Creator ID or slug}
        {--track= : One track key}
        {--dry-run : Preview only}
        {--apply : Persist progress and missing awards}
        {--chunk=100 : Chunk size}';

    protected $description = 'Evaluate historical data and backfill missing accolades';

    public function handle(AccoladeEvaluationService $evaluation, AccoladeDefinitionRepository $definitions): int
    {
        $definitions->validate();
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
            $this->warn('Dry run: no accolade or progress data will be changed. Use --apply to persist.');
        }
        $chunk = max(1, (int) $this->option('chunk'));
        $types = $this->selectedSubjectTypes();

        if (in_array('guide', $types, true)) {
            $this->guideQuery()->orderBy('id')->chunkById($chunk, function ($users) use ($evaluation, $apply): void {
                foreach ($users as $user) {
                    $result = $evaluation->evaluateGuide($user, $this->selectedTracks(), [
                        'source' => 'backfill', 'suppress_notifications' => true,
                    ], $apply);
                    $this->renderResult("Guide #{$user->id} {$user->publicName()}", $result);
                }
            });
        }
        if (in_array('creator', $types, true)) {
            $this->creatorQuery()->orderBy('id')->chunkById($chunk, function ($creators) use ($evaluation, $apply): void {
                foreach ($creators as $creator) {
                    $result = $evaluation->evaluateCreator($creator, $this->selectedTracks(), [
                        'source' => 'backfill', 'suppress_notifications' => true,
                    ], $apply);
                    $this->renderResult("Creator #{$creator->id} {$creator->display_name}", $result);
                }
            });
        }

        $this->info($apply ? 'Accolade backfill complete.' : 'Accolade backfill preview complete.');

        return self::SUCCESS;
    }

    private function renderResult(string $heading, AccoladeEvaluationResult $result): void
    {
        $this->newLine();
        $this->line($heading);
        foreach ($result->tracks as $track => $data) {
            $this->line("  {$track}: {$data['current_value']}");
            if ($data['existing']) {
                $this->line('    Existing: '.implode(', ', $data['existing']));
            }
            if ($data['would_award']) {
                $this->line('    Would award: '.implode(', ', $data['would_award']));
            }
        }
    }
}
