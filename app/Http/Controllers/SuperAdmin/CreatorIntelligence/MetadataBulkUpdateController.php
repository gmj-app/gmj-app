<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Http\Requests\CreatorIntelligence\BulkUpdateVideoMetadataRequest;
use App\Services\CreatorIntelligence\Metadata\MetadataBulkUpdateService;
use Illuminate\Http\RedirectResponse;

class MetadataBulkUpdateController extends Controller
{
    public function __invoke(BulkUpdateVideoMetadataRequest $request, MetadataBulkUpdateService $updates): RedirectResponse
    {
        $result = $updates->apply(
            $request->validated('video_ids'),
            $request->validated('operation'),
            $request->validated('value'),
            $request->validated('mode'),
            $request->user()->id,
        );

        return redirect()->route('superadmin.creator-intelligence.metadata-queue.index', $request->query())
            ->with('success', "Bulk update complete: {$result['updated']} videos updated; {$result['skipped']} skipped.");
    }
}
