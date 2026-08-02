<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Models\CreatorChannel;
use App\Models\CreatorProfile;
use App\Services\CreatorIntelligence\Analytics\AnalyticsContext;
use App\Services\CreatorIntelligence\Analytics\AnalyticsReportService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class OverviewController extends Controller
{
    public function __invoke(Request $request, AnalyticsReportService $reports): View
    {
        $context = AnalyticsContext::fromRequest($request);

        return view('super-admin.creator-intelligence.overview', ['profileCount' => CreatorProfile::count(), 'channelCount' => CreatorChannel::count(), 'context' => $context, 'analytics' => $reports->report('channel', $context)]);
    }
}
