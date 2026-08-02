<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatorIntelligence\UpdateEditorialMetadataRequest;
use App\Models\CreatorVideo;
use App\Services\CreatorIntelligence\Metadata\MetadataCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class CreatorVideoEditorialMetadataController extends Controller
{
    public function update(UpdateEditorialMetadataRequest $r, CreatorVideo $creatorVideo, MetadataCompletionService $completion): RedirectResponse
    {
        $data = $r->safe()->except('mark_reviewed');
        $data += ['classified_by_user_id' => $r->user()->id, 'classified_at' => now()];
        if ($r->boolean('mark_reviewed')) {
            $data += ['reviewed_by_user_id' => $r->user()->id, 'reviewed_at' => now()];
        }$creatorVideo->editorialMetadata()->updateOrCreate([], $data);
        $completion->recalculate($creatorVideo->fresh());
        Log::info('Creator Intelligence editorial metadata updated.', ['creator_video_id' => $creatorVideo->id, 'user_id' => $r->user()->id, 'metadata_section' => 'editorial', 'changed_fields' => array_keys($data)]);

        return back()->with('success', 'Editorial metadata saved.');
    }
}
