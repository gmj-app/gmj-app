<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatorIntelligence\UpdateThumbnailMetadataRequest;
use App\Models\CreatorVideo;
use App\Services\CreatorIntelligence\Metadata\MetadataCompletionService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class CreatorVideoThumbnailMetadataController extends Controller
{
    public function update(UpdateThumbnailMetadataRequest $r, CreatorVideo $creatorVideo, MetadataCompletionService $completion): RedirectResponse
    {
        $data = $r->safe()->except('mark_reviewed');
        $text = trim((string) ($data['text_content'] ?? ''));
        $data['text_word_count'] = $text === '' ? 0 : preg_match_all('/[\p{L}\p{N}]+/u', $text);
        $data += ['classified_by_user_id' => $r->user()->id, 'classified_at' => now()];
        if ($r->boolean('mark_reviewed')) {
            $data += ['reviewed_by_user_id' => $r->user()->id, 'reviewed_at' => now()];
        }$creatorVideo->thumbnailMetadata()->updateOrCreate([], $data);
        $completion->recalculate($creatorVideo->fresh());
        Log::info('Creator Intelligence thumbnail metadata updated.', ['creator_video_id' => $creatorVideo->id, 'user_id' => $r->user()->id, 'metadata_section' => 'thumbnail', 'changed_fields' => array_keys($data)]);

        return back()->with('success', 'Thumbnail metadata saved.');
    }
}
