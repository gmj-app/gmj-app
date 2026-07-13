<?php

namespace App\Listeners;

use App\Events\RequestPublished;
use App\Models\Creator;
use App\Models\Recommendation;
use App\Models\User;
use App\Services\Accolades\AccoladeEvaluationService;
use App\Services\RequestSupportRecipientResolver;
use Illuminate\Contracts\Queue\ShouldQueue;

class EvaluateAccoladesAfterRequestPublished implements ShouldQueue
{
    public bool $afterCommit = true;

    public function __construct(
        private readonly AccoladeEvaluationService $evaluation,
        private readonly RequestSupportRecipientResolver $supporters,
    ) {}

    public function handle(RequestPublished $event): void
    {
        $request = Recommendation::query()->find($event->requestId);
        if (! $request || $request->status !== 'published' || $request->moderation_status === 'removed') {
            return;
        }
        $source = ['event_type' => RequestPublished::class, 'event_id' => $event->requestId];

        if ($request->submission_source === Recommendation::SUBMISSION_SOURCE_FAN
            && $request->submitted_by && ($submitter = User::find($request->submitted_by))) {
            $this->evaluation->evaluateGuide($submitter, ['guide_requests_published', 'guide_influence'], $source);
        }
        foreach ($this->supporters->resolve($request, $request->submitted_by) as $supporter) {
            $this->evaluation->evaluateGuide($supporter, ['guide_supported_publications', 'guide_influence'], $source);
        }
        if ($creator = Creator::find($request->creator_id)) {
            $this->evaluation->evaluateCreator($creator, [
                'creator_community_publications', 'creator_consistency', 'creator_community_reach',
            ], $source);
        }
    }
}
