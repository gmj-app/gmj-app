<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatorIntelligence\UpdateVideoClassificationRequest;
use App\Models\CreatorVideo;
use App\Services\CreatorIntelligence\Metadata\VideoClassificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Log;

class CreatorVideoClassificationController extends Controller
{
    public function update(UpdateVideoClassificationRequest $r, CreatorVideo $creatorVideo, VideoClassificationService $service): RedirectResponse
    {
        $service->subjects($creatorVideo, $r->validated('subjects', []));
        $service->contentItems($creatorVideo, $r->validated('content_items', []));
        Log::info('Creator Intelligence classification updated.', ['creator_video_id' => $creatorVideo->id, 'user_id' => $r->user()->id, 'metadata_section' => 'classification', 'changed_fields' => ['subjects', 'content_items']]);

        return back()->with('success', 'Video classification updated.');
    }
}
