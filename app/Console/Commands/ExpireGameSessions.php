<?php

namespace App\Console\Commands;

use App\Models\GameRunSession;
use Illuminate\Console\Command;

class ExpireGameSessions extends Command
{
    protected $signature = 'game:expire-sessions';

    protected $description = 'Expire stale unsubmitted Daily Journey sessions';

    public function handle(): int
    {
        $count = GameRunSession::query()->whereIn('status', ['issued', 'started'])->where('expires_at', '<', now())->update(['status' => 'expired']);
        $this->info("Expired {$count} session(s).");

        return self::SUCCESS;
    }
}
