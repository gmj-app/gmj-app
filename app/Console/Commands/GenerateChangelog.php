<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Symfony\Component\Process\Process;

class GenerateChangelog extends Command
{
    protected $signature = 'changelog:generate {--limit= : Maximum public entries} {--since= : Git-compatible date boundary} {--dry-run : Print JSON without writing it}';

    protected $description = 'Generate the public beta changelog from safe Git commit fields';

    public function handle(): int
    {
        $limit = filter_var($this->option('limit') ?: config('changelog.limit', 50), FILTER_VALIDATE_INT, [
            'options' => ['min_range' => 1, 'max_range' => 200],
        ]);

        if ($limit === false) {
            $this->error('The limit must be between 1 and 200.');

            return self::INVALID;
        }

        $arguments = ['git', 'log', '--no-merges', '--pretty=format:%h%x09%cI%x09%s', '-n', (string) min(500, $limit * 5)];
        $since = trim((string) $this->option('since'));

        if ($since !== '') {
            $arguments[] = '--since='.$since;
        }

        $process = new Process($arguments, base_path());
        $process->setTimeout(30);
        $process->run();

        if (! $process->isSuccessful()) {
            $message = 'Git metadata was unavailable; the existing changelog was preserved.';
            $this->warn($message);
            Log::warning($message);

            return self::SUCCESS;
        }

        $entries = collect(preg_split('/\R/', trim($process->getOutput())) ?: [])
            ->map(function (string $line): ?array {
                $parts = explode("\t", $line, 3);

                if (count($parts) !== 3 || $this->excluded($parts[2])) {
                    return null;
                }

                return [
                    'hash' => mb_substr(trim($parts[0]), 0, 12),
                    'date' => trim($parts[1]),
                    'subject' => str($parts[2])->squish()->limit((int) config('changelog.subject_max_length', 180))->toString(),
                ];
            })
            ->filter()
            ->take($limit)
            ->values()
            ->all();

        $json = json_encode($entries, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR).PHP_EOL;

        if ($this->option('dry-run')) {
            $this->line($json);
        } else {
            $path = (string) config('changelog.path', storage_path('app/changelog.json'));
            File::ensureDirectoryExists(dirname($path));
            File::put($path, $json);
            $this->info('Generated '.count($entries).' public changelog entries.');
        }

        return self::SUCCESS;
    }

    private function excluded(string $subject): bool
    {
        $normalized = mb_strtolower(trim($subject));

        foreach (config('changelog.excluded_prefixes', []) as $prefix) {
            if (str_starts_with($normalized, mb_strtolower((string) $prefix))) {
                return true;
            }
        }

        foreach (config('changelog.excluded_patterns', []) as $pattern) {
            if (@preg_match($pattern, $subject) === 1) {
                return true;
            }
        }

        return false;
    }
}
