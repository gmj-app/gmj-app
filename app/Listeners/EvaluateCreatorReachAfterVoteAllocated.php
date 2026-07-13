<?php

namespace App\Listeners;

use App\Events\VoteAllocated;
use App\Models\Creator;
use App\Services\Accolades\AccoladeEvaluationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class EvaluateCreatorReachAfterVoteAllocated implements ShouldQueue
{
    public bool $afterCommit = true;

    public function __construct(private readonly AccoladeEvaluationService $evaluation) {}

    public function handle(VoteAllocated $event): void
    {
        if ($creator = Creator::find($event->creatorId)) {
            $this->evaluation->evaluateCreator($creator, ['creator_community_reach'], [
                'event_type' => VoteAllocated::class, 'event_id' => $event->requestId,
            ]);
        }
    }
}
