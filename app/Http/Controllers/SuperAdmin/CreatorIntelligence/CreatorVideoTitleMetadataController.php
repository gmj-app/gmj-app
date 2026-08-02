<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatorIntelligence\UpdateTitleMetadataRequest;
use App\Models\CreatorVideo;
use App\Services\CreatorIntelligence\Metadata\MetadataCompletionService;
use App\Services\CreatorIntelligence\Metadata\TitleMetadataParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class CreatorVideoTitleMetadataController extends Controller
{
    public function update(UpdateTitleMetadataRequest $r, CreatorVideo $creatorVideo, TitleMetadataParser $parser, MetadataCompletionService $completion): RedirectResponse
    {
        $parser->recalculate($creatorVideo, $r->boolean('recalculate_names'));
        $data = $r->safe()->except(['mark_reviewed', 'recalculate_names']);
        $data += ['classified_by_user_id' => $r->user()->id, 'classified_at' => now()];
        if ($r->boolean('mark_reviewed')) {
            $data += ['reviewed_by_user_id' => $r->user()->id, 'reviewed_at' => now()];
        }$creatorVideo->titleMetadata()->updateOrCreate([], $data);
        $completion->recalculate($creatorVideo->fresh());
        Log::info('Creator Intelligence title metadata updated.', ['creator_video_id' => $creatorVideo->id, 'user_id' => $r->user()->id, 'metadata_section' => 'title', 'changed_fields' => array_keys($data)]);

        return back()->with('success', 'Title metadata saved.');
    }
}
