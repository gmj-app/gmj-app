<?php

namespace App\Http\Controllers\SuperAdmin\CreatorIntelligence;

use App\Http\Controllers\Controller;
use App\Services\CreatorIntelligence\Analytics\AnalyticsContext;
use App\Services\CreatorIntelligence\Analytics\AnalyticsDataset;
use App\Services\CreatorIntelligence\Analytics\AnalyticsMetricRegistry;
use App\Services\CreatorIntelligence\Analytics\AnalyticsReportService;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AnalyticsExportController extends Controller
{
    public function __invoke(Request $request, AnalyticsReportService $reports, AnalyticsMetricRegistry $metrics, string $report): StreamedResponse
    {
        abort_unless(in_array($report, AnalyticsController::REPORTS, true), 404);
        $data = $reports->report($report, AnalyticsContext::fromRequest($request));
        $exportMetrics = array_merge(AnalyticsDataset::METRICS, ['metadata_completion_percentage']);

        return response()->streamDownload(function () use ($data, $exportMetrics, $metrics): void {
            $out = fopen('php://output', 'wb');
            $headers = ['Group', 'Video Count'];
            foreach ($exportMetrics as $metric) {
                $label = $this->exportLabel($metric, $metrics);
                if ($metrics->summable($metric)) {
                    $headers[] = 'Total '.$label;
                }
                array_push($headers, 'Average '.$label, 'Median '.$label, $label.' Eligible', $label.' Missing');
            }
            array_push($headers, 'Consistency Score', 'Top Video', 'Top Video Share', 'Sample Strength');
            fputcsv($out, $headers);

            foreach ($data['groups'] as $group) {
                $values = [$group['label'], $group['video_count']];
                foreach ($exportMetrics as $metric) {
                    $summary = $group['metrics'][$metric];
                    if ($metrics->summable($metric)) {
                        $values[] = $summary['sum'];
                    }
                    array_push($values, $summary['mean'], $summary['median'], $summary['eligible_video_count'], $summary['missing_value_count']);
                }
                array_push($values, $group['metrics']['views']['consistency_score'], $group['top_video']?->title, $group['top_video_share'], $group['sample_strength']);
                fputcsv($out, array_map($this->escape(...), $values));
            }
            fclose($out);
        }, 'creator-intelligence-'.$report.'-'.now()->format('Ymd-His').'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function exportLabel(string $metric, AnalyticsMetricRegistry $metrics): string
    {
        return $metric === 'watch_time_minutes' ? 'Watch Time Minutes' : $metrics->label($metric);
    }

    private function escape(mixed $value): mixed
    {
        return is_string($value) && preg_match('/^[=+\-@]/', $value) ? "'".$value : $value;
    }
}
