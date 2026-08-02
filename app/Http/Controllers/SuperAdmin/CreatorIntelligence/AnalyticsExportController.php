<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Services\CreatorIntelligence\Analytics\AnalyticsContext;
use App\Services\CreatorIntelligence\Analytics\AnalyticsReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsExportController extends Controller
{
    public function __invoke(Request $request, AnalyticsReportService $reports, string $report): StreamedResponse
    {
        abort_unless(in_array($report, AnalyticsController::REPORTS, true), 404);
        $data = $reports->report($report, AnalyticsContext::fromRequest($request));

        return response()->streamDownload(function () use ($data): void {
            $out = fopen('php://output', 'wb');
            fputcsv($out, ['Group', 'Video Count', 'Average Views', 'Median Views', 'Views Eligible', 'Views Missing', 'Average CTR', 'Median CTR', 'CTR Eligible', 'CTR Missing', 'Total Subscribers Gained', 'Total Revenue', 'Total Hype Points', 'Average Hype Points', 'Median Hype Points', 'Hype Eligible', 'Hype Missing', 'Top Video', 'Top Video Share', 'Sample Strength']);
            foreach ($data['groups'] as $group) {
                $cell = fn ($value) => is_string($value) && preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
                fputcsv($out, array_map($cell, [$group['label'], $group['video_count'], $group['metrics']['views']['mean'], $group['metrics']['views']['median'], $group['metrics']['views']['eligible_video_count'], $group['metrics']['views']['missing_value_count'], $group['metrics']['impressions_ctr']['mean'], $group['metrics']['impressions_ctr']['median'], $group['metrics']['impressions_ctr']['eligible_video_count'], $group['metrics']['impressions_ctr']['missing_value_count'], $group['metrics']['subscribers_gained']['sum'], $group['metrics']['estimated_revenue']['sum'], $group['metrics']['hype_points']['sum'], $group['metrics']['hype_points']['mean'], $group['metrics']['hype_points']['median'], $group['metrics']['hype_points']['eligible_video_count'], $group['metrics']['hype_points']['missing_value_count'], $group['top_video']?->title, $group['top_video_share'], $group['sample_strength']]));
            }
            fclose($out);
        }, 'creator-intelligence-'.$report.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
