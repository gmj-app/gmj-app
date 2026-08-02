<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Enums\PerformanceSnapshotSource;
use App\Enums\VideoContentType;
use App\Enums\VideoCopyrightStatus;
use App\Enums\VideoFormat;
use App\Http\Controllers\Controller;
use App\Models\CreatorChannel;
use App\Models\CreatorProfile;
use App\Models\Subject;
use App\Services\CreatorIntelligence\Analytics\AnalyticsCache;
use App\Services\CreatorIntelligence\Analytics\AnalyticsContext;
use App\Services\CreatorIntelligence\Analytics\AnalyticsMetricRegistry;
use App\Services\CreatorIntelligence\Analytics\AnalyticsReportService;
use App\Services\CreatorIntelligence\Videos\LatestSnapshotResolver;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AnalyticsController extends Controller
{
    public const REPORTS = ['channel', 'subjects', 'content-items', 'timing', 'titles', 'thumbnails', 'editorial', 'hype'];

    public function __invoke(Request $request, AnalyticsReportService $reports, AnalyticsCache $cache, string $report = 'channel'): View
    {
        abort_unless(in_array($report, self::REPORTS, true), 404);
        $context = AnalyticsContext::fromRequest($request);
        $data = $cache->remember($report, $context, fn () => $reports->report($report, $context));

        return view('super-admin.creator-intelligence.analytics.index', compact('report', 'context', 'data') + [
            'profiles' => CreatorProfile::orderBy('display_name')->get(), 'channels' => CreatorChannel::with('profile')->orderBy('channel_name')->get(),
            'subjects' => $context->channel ? Subject::where('creator_channel_id', $context->channel->id)->orderBy('name')->get() : collect(),
            'formats' => VideoFormat::cases(), 'types' => VideoContentType::cases(), 'copyrights' => VideoCopyrightStatus::cases(), 'sources' => PerformanceSnapshotSource::cases(),
            'metricRegistry' => app(AnalyticsMetricRegistry::class), 'sourcePriority' => LatestSnapshotResolver::SOURCE_PRIORITY,
        ]);
    }
}
