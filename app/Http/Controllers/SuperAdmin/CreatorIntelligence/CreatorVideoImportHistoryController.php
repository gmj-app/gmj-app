<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Models\CreatorVideo;
use Illuminate\View\View;

class CreatorVideoImportHistoryController extends Controller
{
    public function __invoke(CreatorVideo $creatorVideo): View
    {
        return view('super-admin.creator-intelligence.videos.imports', ['video' => $creatorVideo, 'rows' => $creatorVideo->importRows()->with(['batch.uploadedBy', 'snapshot'])->latest()->paginate(50)]);
    }
}
