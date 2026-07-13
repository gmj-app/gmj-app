<?php

namespace App\Services;

use App\Models\Creator;

class CreatorSetupCompletenessService
{
    /** @return array{percentage:int,completed:array<string,string>,missing:array<string,string>} */
    public function evaluate(Creator $creator): array
    {
        $checks = [
            'display_name' => ['Public name', filled($creator->display_name)],
            'slug' => ['Handle', filled($creator->slug)],
            'youtube_channel_url' => ['YouTube channel URL', filled($creator->youtube_channel_url ?: $creator->channel_url)],
            'bio' => ['Biography', filled($creator->bio)],
            'avatar' => ['Avatar image', filled($creator->avatar_path ?: $creator->youtube_thumbnail_url)],
            'banner' => ['Banner image', filled($creator->hero_path ?: $creator->youtube_banner_url)],
            'tags' => ['Creator tags', $creator->relationLoaded('creatorTags') ? $creator->creatorTags->isNotEmpty() : $creator->creatorTags()->exists()],
            'instructions' => ['Request instructions', filled($creator->submission_instructions)],
            'moderation' => ['Moderation preference', in_array($creator->recommendation_approval_mode, Creator::RECOMMENDATION_APPROVAL_MODES, true)],
            'public' => ['Page publicly enabled', $creator->isAvailableForGuides()],
        ];

        $completed = $missing = [];
        foreach ($checks as $key => [$label, $done]) {
            if ($done) {
                $completed[$key] = $label;
            } else {
                $missing[$key] = $label;
            }
        }

        return ['percentage' => (int) round(count($completed) / count($checks) * 100), 'completed' => $completed, 'missing' => $missing];
    }
}
