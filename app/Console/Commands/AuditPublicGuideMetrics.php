<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\PublicGuideMetricsService;
use Illuminate\Console\Command;

class AuditPublicGuideMetrics extends Command
{
    protected $signature = 'guides:audit-public-metrics
        {--user= : User ID}
        {--email= : User email}
        {--handle= : Public handle, with or without @}
        {--all : Inspect every Guide}';

    protected $description = 'Read-only audit of authoritative public Guide activity metrics';

    public function handle(PublicGuideMetricsService $metrics): int
    {
        if (! $this->option('user') && ! $this->option('email') && ! $this->option('handle') && ! $this->option('all')) {
            $this->error('Choose --user, --email, --handle, or --all.');

            return self::INVALID;
        }

        $query = User::query()->orderBy('id')
            ->when($this->option('user'), fn ($query, $id) => $query->whereKey($id))
            ->when($this->option('email'), fn ($query, $email) => $query->where('email', $email))
            ->when($this->option('handle'), fn ($query, $handle) => $query->where('public_handle', ltrim(strtolower($handle), '@')));
        $inspected = 0;
        $mismatches = 0;

        $query->each(function (User $guide) use ($metrics, &$inspected, &$mismatches): void {
            $inspected++;
            $values = $metrics->forGuide($guide);
            $issues = collect([
                $values['active_requests_supported_count'] > 0 && $values['votes_cast_count'] === 0 ? 'active-requests-with-zero-lifetime-votes' : null,
                $values['active_requests_supported_count'] > 0 && $values['creators_supported_count'] === 0 ? 'active-requests-with-zero-creators' : null,
                $values['published_requests_count'] > $values['requests_count'] ? 'published-exceeds-requests' : null,
                $values['votes_cast_count'] !== $values['active_vote_quantity'] + $values['historical_vote_quantity'] ? 'lifetime-quantity-does-not-reconcile' : null,
            ])->filter()->values();
            $mismatches += $issues->isNotEmpty() ? 1 : 0;

            $this->newLine();
            $this->line("Guide #{$guide->id} @".($guide->public_handle ?: 'none'));
            $this->line("Submitted/published requests: {$values['requests_count']} / {$values['published_requests_count']}");
            $this->line("Active quantity/requests/creators: {$values['active_vote_quantity']} / {$values['active_requests_supported_count']} / {$values['active_creators_supported_count']}");
            $this->line("Historical/lifetime vote quantity: {$values['historical_vote_quantity']} / {$values['votes_cast_count']}");
            $this->line("Lifetime requests/creators supported: {$values['requests_supported_count']} / {$values['creators_supported_count']}");
            $this->line('Public metric cache: not configured; values are authoritative queries');
            $this->line('Mismatch: '.($issues->isEmpty() ? 'none' : $issues->implode(', ')));
        }, 100);

        $this->newLine();
        $this->info("Summary: inspected={$inspected}, mismatches={$mismatches}, mutations=0");

        return self::SUCCESS;
    }
}
