<?php

namespace App\Listeners;

use App\Events\RequestCreated;
use App\Models\User;
use App\Services\Accolades\AccoladeEvaluationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class EvaluateGuideAccoladesAfterRequestCreated implements ShouldQueue
{
    public bool $afterCommit = true;

    public function __construct(private readonly AccoladeEvaluationService $evaluation) {}

    public function handle(RequestCreated $event): void
    {
        if ($event->actorContext !== 'guide' || ! $event->submitterUserId || ! ($user = User::find($event->submitterUserId))) {
            return;
        }
        $this->evaluation->evaluateGuide($user, ['guide_requests_submitted'], [
            'event_type' => RequestCreated::class, 'event_id' => $event->requestId,
        ]);
    }
}
