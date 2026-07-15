<?php

namespace App\Console\Commands;

use App\Models\Recommendation;
use App\Services\RequestSupportService;
use Illuminate\Console\Command;

class AuditRequestSupportDisplay extends Command
{
    protected $signature = 'requests:audit-support-display
        {--request= : Request ID}
        {--creator= : Creator ID}
        {--status= : Request status}
        {--all : Inspect all requests}
        {--dry-run : Explicitly confirm this read-only audit}';

    protected $description = 'Audit vote totals, supporter previews, and active/historical display scope';

    public function handle(RequestSupportService $support): int
    {
        if (! $this->option('request') && ! $this->option('creator') && ! $this->option('status') && ! $this->option('all')) {
            $this->error('Choose --request, --creator, --status, or --all.');

            return self::INVALID;
        }

        $query = Recommendation::query()->withTrashed()->orderBy('id')
            ->when($this->option('request'), fn ($query, $id) => $query->whereKey($id))
            ->when($this->option('creator'), fn ($query, $id) => $query->where('creator_id', $id))
            ->when($this->option('status'), fn ($query, $status) => $query->where('status', $status));
        $inspected = 0;
        $mismatches = 0;

        $query->each(function (Recommendation $request) use ($support, &$inspected, &$mismatches): void {
            $inspected++;
            $activeVotes = $support->activeVoteQuantity($request);
            $historicalVotes = $support->historicalVoteQuantity($request);
            $activeSupporters = $support->activeSupporterCount($request);
            $historicalSupporters = $support->historicalSupporterCount($request);
            $displayVotes = $support->displayVoteQuantity($request);
            $displaySupporters = $support->displaySupporterCount($request, $request->submitted_by);
            $preview = $support->displaySupporterPreview($request, 6, $request->submitted_by);
            $remaining = max(0, $displaySupporters - $preview->count());
            $scope = $support->displaySupportScope($request);
            $expectedScope = $request->isVotingOpen() ? 'active' : 'historical';
            $issues = collect([
                $displayVotes > 0 && $displaySupporters === 0 ? 'votes-without-visible-supporters' : null,
                $preview->count() > $displaySupporters ? 'preview-exceeds-total' : null,
                $remaining !== max(0, $displaySupporters - $preview->count()) ? 'incorrect-remaining-count' : null,
                $scope !== $expectedScope ? 'incorrect-display-scope' : null,
            ])->filter()->values();
            $mismatches += $issues->isNotEmpty() ? 1 : 0;

            $this->newLine();
            $this->line("Request #{$request->id} | {$request->status} | voting=".($request->isVotingOpen() ? 'open' : 'closed'));
            $this->line("Votes display/active/historical: {$displayVotes} / {$activeVotes} / {$historicalVotes}");
            $this->line("Supporters display/active/historical: {$displaySupporters} / {$activeSupporters} / {$historicalSupporters}");
            $this->line("Preview/remaining/scope: {$preview->count()} / {$remaining} / {$scope}");
            $this->line('Mismatch: '.($issues->isEmpty() ? 'none' : $issues->implode(', ')));
        }, 100);

        $this->newLine();
        $this->info("Summary: inspected={$inspected}, mismatches={$mismatches}, mutations=0");

        return self::SUCCESS;
    }
}
