<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatorIntelligence\BulkUpdateVideoMetadataRequest;
use App\Jobs\BulkUpdateVideoMetadata;
use Illuminate\Http\RedirectResponse;

class MetadataBulkUpdateController extends Controller
{
    public function __invoke(BulkUpdateVideoMetadataRequest $r): RedirectResponse
    {
        BulkUpdateVideoMetadata::dispatch($r->validated('video_ids'), $r->validated('operation'), $r->validated('value'), $r->validated('mode'), $r->user()->id);

        return back()->with('success', 'Bulk metadata update queued for '.count($r->validated('video_ids')).' videos.');
    }
}
