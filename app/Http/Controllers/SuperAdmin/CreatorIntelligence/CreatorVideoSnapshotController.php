<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Enums\PerformanceSnapshotSource;
use App\Http\Controllers\Controller;
use App\Models\CreatorVideo;
use App\Services\CreatorIntelligence\Videos\MetricFormatter;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CreatorVideoSnapshotController extends Controller
{
    public function __invoke(Request $request, CreatorVideo $creatorVideo): View
    {
        $query = $creatorVideo->performanceSnapshots();
        if (PerformanceSnapshotSource::tryFrom((string) $request->input('source'))) {
            $query->where('source', $request->input('source'));
        }
        $direction = $request->input('direction') === 'asc' ? 'asc' : 'desc';
        $sort = $request->input('sort') === 'source' ? 'source' : 'snapshot_date';

        return view('super-admin.creator-intelligence.videos.snapshots', ['video' => $creatorVideo, 'snapshots' => $query->orderBy($sort, $direction)->orderByDesc('id')->paginate(50)->withQueryString(), 'format' => app(MetricFormatter::class)]);
    }
}
