<?php

namespace App\Console\Commands;

use App\Models\AccoladeProgress;
use App\Models\Creator;
use App\Models\User;
use App\Models\UserAccolade;
use App\Services\Accolades\AccoladeDefinitionRepository;
use App\Services\Accolades\AccoladeEvaluationResult;
use App\Services\Accolades\AccoladeEvaluationService;
use Illuminate\Console\Command;

class TestAccoladeSubject extends Command
{
    protected $signature = 'accolades:test-subject
        {--email= : Guide email address}
        {--creator= : Creator ID or slug}
        {--evaluate : Persist progress and missing awards}
        {--show-source-records : List qualifying request, creator, or Guide IDs}
        {--show-earned : Include earned timestamps and award-time values}
        {--show-progress : Include the persisted progress snapshot}';

    protected $description = 'Inspect one Guide or Creator against authoritative accolade metrics';

    public function handle(AccoladeEvaluationService $evaluation, AccoladeDefinitionRepository $definitions): int
    {
        if ((bool) $this->option('email') === (bool) $this->option('creator')) {
            $this->error('Provide exactly one of --email or --creator.');

            return self::INVALID;
        }
        if ($this->option('evaluate') && app()->environment('production')) {
            if (! $this->input->isInteractive() || ! $this->confirm('This will persist missing awards and progress in production. Continue?')) {
                $this->error('Production evaluation cancelled. Run without --evaluate for a safe read-only inspection.');

                return self::FAILURE;
            }
        }

        if ($email = $this->option('email')) {
            $subject = User::query()->where('email', $email)->first();
            if (! $subject) {
                $this->error("No active Guide found for {$email}.");

                return self::FAILURE;
            }
            $subjectType = 'guide';
            $subjectId = $subject->id;
            $label = "Guide #{$subject->id} {$subject->publicName()} <{$subject->email}>";
            $result = $evaluation->evaluateGuide($subject, source: [
                'source' => 'test_subject', 'suppress_notifications' => true,
            ], persist: (bool) $this->option('evaluate'));
        } else {
            $value = (string) $this->option('creator');
            $subject = Creator::query()->where(is_numeric($value) ? 'id' : 'slug', $value)->first();
            if (! $subject) {
                $this->error("No active Creator found for {$value}.");

                return self::FAILURE;
            }
            $subjectType = 'creator';
            $subjectId = $subject->id;
            $label = "Creator #{$subject->id} {$subject->display_name} ({$subject->slug})";
            $result = $evaluation->evaluateCreator($subject, source: [
                'source' => 'test_subject', 'suppress_notifications' => true,
            ], persist: (bool) $this->option('evaluate'));
        }

        $this->newLine();
        $this->info($label);
        $this->line($this->option('evaluate') ? 'Mode: EVALUATE (mutations enabled)' : 'Mode: READ ONLY');
        $this->renderTracks($result, $definitions, $subjectType, $subjectId);

        return self::SUCCESS;
    }

    private function renderTracks(AccoladeEvaluationResult $result, AccoladeDefinitionRepository $definitions, string $subjectType, int $subjectId): void
    {
        $awards = UserAccolade::query()->where('subject_type', $subjectType)->where('subject_id', $subjectId)->get()->keyBy('accolade_key');
        $progress = AccoladeProgress::query()->where('subject_type', $subjectType)->where('subject_id', $subjectId)->get()->keyBy('track');

        foreach ($result->tracks as $track => $data) {
            $trackDefinitions = $definitions->forTrack($subjectType, $track);
            $earned = $trackDefinitions->filter(fn (array $definition) => $awards->has($definition['key']));
            $missing = $trackDefinitions->filter(fn (array $definition) => $definition['threshold'] <= $data['current_value'] && ! $awards->has($definition['key']));
            $next = $trackDefinitions->first(fn (array $definition) => $definition['threshold'] > $data['current_value']);

            $this->newLine();
            $this->line('<fg=cyan;options=bold>'.config("accolades.tracks.{$track}.label", $track)."</> [{$track}]");
            $this->line("  Authoritative metric value: {$data['current_value']}");
            $this->line('  Earned accolades: '.($earned->isEmpty() ? 'none' : $earned->pluck('name')->implode(', ')));
            $this->line('  Missing accolades at current value: '.($missing->isEmpty() ? 'none' : $missing->pluck('name')->implode(', ')));
            $this->line($next
                ? "  Next threshold: {$next['name']} at {$next['threshold']} (".max(0, $next['threshold'] - $data['current_value']).' remaining)'
                : '  Next threshold: track complete');

            if ($this->option('show-source-records')) {
                $ids = $data['qualifying_record_ids'] ?? [];
                $this->line('  Qualifying record IDs: '.($ids ? implode(', ', $ids) : 'none'));
                if ($months = data_get($data, 'metadata.qualifying_months')) {
                    $this->line('  Qualifying calendar months: '.implode(', ', $months));
                }
            }
            if ($this->option('show-earned')) {
                foreach ($earned as $definition) {
                    $award = $awards[$definition['key']];
                    $this->line("    - {$definition['name']}: {$award->awarded_at->toIso8601String()} (value {$award->progress_value_at_award}, threshold {$award->threshold_at_award})");
                }
            }
            if ($this->option('show-progress')) {
                $row = $progress->get($track);
                $this->line($row
                    ? "  Persisted progress: {$row->current_value}; next={$row->next_accolade_key}; evaluated={$row->evaluated_at->toIso8601String()}"
                    : '  Persisted progress: none');
            }
        }
    }
}
