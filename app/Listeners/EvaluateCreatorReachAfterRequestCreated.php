<?php

namespace App\Listeners;

use App\Events\RequestCreated;
use App\Models\Creator;
use App\Services\Accolades\AccoladeEvaluationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class EvaluateCreatorReachAfterRequestCreated implements ShouldQueue
{
    public bool $afterCommit = true;

    public function __construct(private readonly AccoladeEvaluationService $evaluation) {}

    public function handle(RequestCreated $event): void
    {
        if ($event->actorContext !== 'guide' || ! ($creator = Creator::find($event->creatorId))) {
            return;
        }
        $this->evaluation->evaluateCreator($creator, ['creator_community_reach'], [
            'event_type' => RequestCreated::class, 'event_id' => $event->requestId,
        ]);
    }
}
