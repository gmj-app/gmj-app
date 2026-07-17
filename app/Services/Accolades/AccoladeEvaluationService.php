<?php

namespace App\Services\Accolades;

use App\Models\AccoladeProgress;
use App\Models\Creator;
use App\Models\User;
use App\Models\UserAccolade;
use App\Services\Accolades\Contracts\TrackEvaluator;
use App\Services\Accolades\Evaluators\CreatorCommunityPublicationEvaluator;
use App\Services\Accolades\Evaluators\CreatorCommunityReachEvaluator;
use App\Services\Accolades\Evaluators\CreatorConsistencyEvaluator;
use App\Services\Accolades\Evaluators\GuideCreatorExplorationEvaluator;
use App\Services\Accolades\Evaluators\GuideInfluenceEvaluator;
use App\Services\Accolades\Evaluators\GuidePublishedRequestEvaluator;
use App\Services\Accolades\Evaluators\GuideRequestSubmissionEvaluator;
use App\Services\Accolades\Evaluators\GuideSupportedPublicationEvaluator;
use InvalidArgumentException;

class AccoladeEvaluationService
{
    public function __construct(
        private readonly AccoladeDefinitionRepository $definitions,
        private readonly AccoladeAwardService $awards,
    ) {}

    /** @param array<int, string>|null $tracks @param array<string, mixed> $source */
    public function evaluateGuide(User $user, ?array $tracks = null, array $source = [], bool $persist = true, bool $award = true): AccoladeEvaluationResult
    {
        return $this->evaluateSubject('guide', $user->id, $tracks, $source, $persist, $award);
    }

    /** @param array<int, string>|null $tracks @param array<string, mixed> $source */
    public function evaluateCreator(Creator $creator, ?array $tracks = null, array $source = [], bool $persist = true, bool $award = true): AccoladeEvaluationResult
    {
        return $this->evaluateSubject('creator', $creator->id, $tracks, $source, $persist, $award);
    }

    /** @param array<int, string>|null $tracks @param array<string, mixed> $source */
    public function evaluateSubject(string $subjectType, int $subjectId, ?array $tracks = null, array $source = [], bool $persist = true, bool $award = true): AccoladeEvaluationResult
    {
        $map = $this->evaluators();
        $available = array_filter(array_keys($map), fn (string $key) => str_starts_with($key, $subjectType.':'));
        $selected = $tracks ?: array_map(fn (string $key) => str($key)->after(':')->toString(), $available);
        $userId = $subjectType === 'guide'
            ? User::query()->findOrFail($subjectId)->id
            : Creator::query()->findOrFail($subjectId)->owners()->wherePivot('role', 'owner')->value('users.id');

        if (! $userId) {
            return new AccoladeEvaluationResult($subjectType, $subjectId, collect(), []);
        }

        $newAwards = collect();
        $trackResults = [];
        foreach ($selected as $track) {
            /** @var TrackEvaluator|null $evaluator */
            $evaluator = $map[$subjectType.':'.$track] ?? null;
            if (! $evaluator) {
                throw new InvalidArgumentException("Unknown {$subjectType} accolade track: {$track}");
            }
            $metric = $evaluator->evaluate($subjectId);
            $definitions = $this->definitions->forTrack($subjectType, $track);
            $existingKeys = UserAccolade::query()->where('subject_type', $subjectType)->where('subject_id', $subjectId)
                ->where('track', $track)->pluck('accolade_key');
            $eligible = $definitions->filter(fn (array $definition) => $metric->value >= $definition['threshold'] && ! $existingKeys->contains($definition['key']));
            $next = $definitions->first(fn (array $definition) => $metric->value < $definition['threshold']);

            if ($persist) {
                AccoladeProgress::query()->updateOrCreate(
                    ['subject_type' => $subjectType, 'subject_id' => $subjectId, 'track' => $track],
                    ['current_value' => $metric->value,
                        'next_accolade_key' => $next['key'] ?? null, 'evaluated_at' => $metric->evaluatedAt ?? now(), 'metadata' => $metric->metadata],
                );
                if ($award) {
                    foreach ($eligible as $definition) {
                        $result = $this->awards->award($userId, $subjectType, $subjectId, $definition, $metric->value, $source + ['metric' => $metric->metadata]);
                        if ($result['created']) {
                            $newAwards->push($result['award']);
                        }
                    }
                }
            }

            $trackResults[$track] = ['current_value' => $metric->value, 'existing' => $existingKeys->all(),
                'would_award' => $eligible->pluck('key')->all(), 'next_accolade_key' => $next['key'] ?? null,
                'qualifying_record_ids' => $metric->qualifyingRecordIds, 'metadata' => $metric->metadata];
        }

        return new AccoladeEvaluationResult($subjectType, $subjectId, $newAwards, $trackResults);
    }

    /** @return array<string, TrackEvaluator> */
    private function evaluators(): array
    {
        return [
            'guide:guide_requests_submitted' => app(GuideRequestSubmissionEvaluator::class),
            'guide:guide_requests_published' => app(GuidePublishedRequestEvaluator::class),
            'guide:guide_supported_publications' => app(GuideSupportedPublicationEvaluator::class),
            'guide:guide_creator_exploration' => app(GuideCreatorExplorationEvaluator::class),
            'guide:guide_influence' => app(GuideInfluenceEvaluator::class),
            'creator:creator_community_publications' => app(CreatorCommunityPublicationEvaluator::class),
            'creator:creator_consistency' => app(CreatorConsistencyEvaluator::class),
            'creator:creator_community_reach' => app(CreatorCommunityReachEvaluator::class),
        ];
    }
}
