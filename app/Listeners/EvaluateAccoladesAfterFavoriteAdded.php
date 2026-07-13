<?php

namespace App\Listeners;

use App\Events\FavoriteCreatorAdded;
use App\Models\Creator;
use App\Models\User;
use App\Services\Accolades\AccoladeEvaluationService;
use Illuminate\Contracts\Queue\ShouldQueue;

class EvaluateAccoladesAfterFavoriteAdded implements ShouldQueue
{
    public bool $afterCommit = true;

    public function __construct(private readonly AccoladeEvaluationService $evaluation) {}

    public function handle(FavoriteCreatorAdded $event): void
    {
        $source = ['event_type' => FavoriteCreatorAdded::class, 'event_id' => $event->creatorId];
        if ($user = User::find($event->userId)) {
            $this->evaluation->evaluateGuide($user, ['guide_creator_exploration'], $source);
        }
        if ($creator = Creator::find($event->creatorId)) {
            $this->evaluation->evaluateCreator($creator, ['creator_community_reach'], $source);
        }
    }
}
