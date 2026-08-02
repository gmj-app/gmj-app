<?php

namespace Tests\Feature;

use App\Models\CreatorChannel;
use App\Models\CreatorVideo;
use App\Models\Subject;
use App\Models\User;
use App\Models\VideoPerformanceSnapshot;
use App\Services\CreatorIntelligence\Analytics\AnalyticsContext;
use App\Services\CreatorIntelligence\Analytics\AnalyticsMetricRegistry;
use App\Services\CreatorIntelligence\Analytics\AnalyticsReportService;
use App\Services\CreatorIntelligence\Analytics\StatisticsService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Tests\TestCase;

class CreatorIntelligenceAnalyticsTest extends TestCase
{
    use RefreshDatabase;

    public function test_analytics_routes_and_exports_require_creator_intelligence_permission(): void
    {
        $this->get(route('superadmin.creator-intelligence.analytics.index'))->assertRedirect(route('login'));
        $ordinary = User::factory()->create();
        $this->actingAs($ordinary)->get(route('superadmin.creator-intelligence.analytics.index'))->assertForbidden();
        $this->actingAs($ordinary)->get(route('superadmin.creator-intelligence.analytics.export', 'channel'))->assertForbidden();
        $this->actingAs($ordinary)->get(route('dashboard'))->assertDontSee('Analytics');

        $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.analytics.index'))->assertOk()->assertSee('Channel Performance');
    }

    public function test_statistics_preserve_null_and_zero_and_calculate_even_median(): void
    {
        $stats = app(StatisticsService::class)->summarize([null, 0, 10, 20, 30], 5);
        $this->assertSame(15.0, $stats['mean']);
        $this->assertSame(15.0, $stats['median']);
        $this->assertSame(4, $stats['eligible_video_count']);
        $this->assertSame(1, $stats['missing_value_count']);
        $this->assertNotNull($stats['coefficient_of_variation']);
        $this->assertNull(app(StatisticsService::class)->difference(10, 0)['relative']);
    }

    public function test_metric_registry_only_exposes_totals_for_summable_metrics(): void
    {
        $registry = app(AnalyticsMetricRegistry::class);
        $statistics = ['sum' => 10, 'mean' => 5, 'median' => 5, 'eligible_video_count' => 2, 'missing_value_count' => 1];

        foreach (['impressions_ctr', 'average_percentage_viewed', 'average_view_duration_seconds', 'rpm', 'cpm', 'metadata_completion_percentage', 'consistency_score'] as $metric) {
            $this->assertFalse($registry->summable($metric));
        }
        foreach (['views', 'impressions', 'watch_time_minutes', 'subscribers_gained', 'estimated_revenue', 'hype_points', 'likes', 'comments'] as $metric) {
            $this->assertTrue($registry->summable($metric));
        }

        $ctrLabels = collect($registry->summaryRows('impressions_ctr', $statistics))->pluck('label');
        $this->assertNotContains('Total CTR', $ctrLabels);
        $this->assertSame(['Average CTR', 'Median CTR', 'Eligible videos', 'Missing CTR'], $ctrLabels->all());
        $this->assertSame('Total Views', $registry->summaryRows('views', $statistics)[0]['label']);
    }

    public function test_every_report_renders_an_accessible_empty_state(): void
    {
        $admin = $this->admin();
        foreach (['channel', 'subjects', 'content-items', 'timing', 'titles', 'thumbnails', 'editorial', 'hype'] as $report) {
            $this->actingAs($admin)->get(route('superadmin.creator-intelligence.analytics.report', $report))->assertOk()->assertSee('Data coverage')->assertSee('No groups meet');
        }
    }

    public function test_channel_filter_and_explicit_snapshot_source_are_isolated_and_deterministic(): void
    {
        $channel = CreatorChannel::factory()->create(['channel_name' => 'Included Channel']);
        $other = CreatorChannel::factory()->create(['channel_name' => 'Other Channel']);
        $video = CreatorVideo::factory()->for($channel, 'channel')->create(['title' => 'Included Video']);
        $excluded = CreatorVideo::factory()->for($other, 'channel')->create(['title' => 'Excluded Video']);
        VideoPerformanceSnapshot::factory()->for($video, 'video')->create(['snapshot_date' => '2026-08-01', 'source' => 'youtube_studio', 'views' => 100]);
        VideoPerformanceSnapshot::factory()->for($video, 'video')->create(['snapshot_date' => '2026-08-01', 'source' => 'manual', 'views' => 900]);
        VideoPerformanceSnapshot::factory()->for($excluded, 'video')->create(['snapshot_date' => '2026-08-01', 'source' => 'combined', 'views' => 9999]);

        $response = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.analytics.report', ['report' => 'channel', 'creator_channel_id' => $channel->id, 'snapshot_source' => 'youtube_studio', 'minimum_sample_size' => 1]));
        $response->assertOk()->assertSee('100')->assertDontSee('9,999')->assertSee('Latest qualifying Youtube Studio snapshot per video');
    }

    public function test_subject_report_calculates_average_median_top_share_and_low_sample_rules(): void
    {
        $channel = CreatorChannel::factory()->create(['subject_label' => 'Artist']);
        $subject = Subject::factory()->for($channel, 'creatorChannel')->create(['name' => 'SB19']);
        foreach ([100, 200, 900] as $views) {
            $video = CreatorVideo::factory()->for($channel, 'channel')->create();
            $video->subjects()->attach($subject, ['relationship_type' => 'primary', 'is_primary' => true]);
            VideoPerformanceSnapshot::factory()->for($video, 'video')->create(['snapshot_date' => '2026-08-01', 'views' => $views, 'impressions_ctr' => $views === 100 ? null : 5, 'hype_points' => $views === 100 ? 0 : null]);
        }
        $url = route('superadmin.creator-intelligence.analytics.report', ['report' => 'subjects', 'creator_channel_id' => $channel->id, 'minimum_sample_size' => 3]);
        $this->actingAs($this->admin())->get($url)->assertOk()->assertSee('Artist Performance')->assertSee('SB19')->assertSee('400')->assertSee('200')->assertSee('Outlier-sensitive');
        $this->actingAs($this->admin())->get($url.'&minimum_sample_size=4')->assertOk()->assertSee('No groups meet');
    }

    public function test_channel_summary_uses_rate_watch_time_hype_and_metadata_semantics(): void
    {
        $channel = CreatorChannel::factory()->create();
        $snapshots = [
            ['ctr' => null, 'watch' => 60, 'hype' => null, 'completion' => 100, 'status' => 'complete'],
            ['ctr' => 0, 'watch' => 120, 'hype' => 0, 'completion' => 50, 'status' => 'in_progress'],
            ['ctr' => 10, 'watch' => 180, 'hype' => 5, 'completion' => 0, 'status' => 'not_started'],
        ];

        foreach ($snapshots as $values) {
            $video = CreatorVideo::factory()->for($channel, 'channel')->create([
                'published_at' => '2026-07-01 12:00:00',
                'metadata_completion_percentage' => $values['completion'],
                'metadata_completion_status' => $values['status'],
            ]);
            VideoPerformanceSnapshot::factory()->for($video, 'video')->create([
                'snapshot_date' => '2026-08-01',
                'views' => 0,
                'impressions_ctr' => $values['ctr'],
                'watch_time_minutes' => $values['watch'],
                'average_percentage_viewed' => $values['ctr'],
                'hype_points' => $values['hype'],
            ]);
        }

        $context = AnalyticsContext::fromRequest(Request::create('/', 'GET', [
            'creator_channel_id' => $channel->id,
            'minimum_sample_size' => 1,
        ]));
        $data = app(AnalyticsReportService::class)->report('channel', $context);
        $this->assertSame(2, $data['coverage']['hype_reported']);
        $this->assertSame(1, $data['coverage']['hype_positive']);
        $this->assertSame(50.0, $data['coverage']['hype_receiving_percentage']);
        $this->assertSame(2, $data['summary']['impressions_ctr']['eligible_video_count']);
        $this->assertSame(1, $data['summary']['impressions_ctr']['missing_value_count']);
        $this->assertSame(5.0, $data['summary']['impressions_ctr']['mean']);
        $this->assertSame(5.0, $data['summary']['impressions_ctr']['median']);
        $this->assertSame(['complete' => 1, 'in_progress' => 1, 'not_started' => 1], $data['metadata_status_counts']);

        $response = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.analytics.report', [
            'report' => 'channel',
            'creator_channel_id' => $channel->id,
            'minimum_sample_size' => 1,
        ]));

        $response->assertOk()
            ->assertDontSee('Total CTR')
            ->assertDontSee('Total Average Percentage Viewed')
            ->assertDontSee('Total Metadata Completion')
            ->assertSee('Average CTR')
            ->assertSee('Missing CTR')
            ->assertSee('Total watch time in hours')
            ->assertSee('6.00')
            ->assertSee('360.00 raw minutes')
            ->assertSee('Hype data reported')
            ->assertSee('Videos receiving Hype')
            ->assertSee('Percentage receiving Hype')
            ->assertSee('Complete videos')
            ->assertSee('In-progress videos')
            ->assertSee('Not-started videos')
            ->assertSeeInOrder(['Group', 'Videos', 'Average Views', 'Median Views', 'Average CTR', 'Subscribers', 'Revenue', 'Average Hype', 'Consistency', 'Top Video', 'Sample Strength']);
    }

    public function test_analytics_export_uses_filters_includes_counts_and_escapes_formulas(): void
    {
        $channel = CreatorChannel::factory()->create();
        $subject = Subject::factory()->for($channel, 'creatorChannel')->create(['name' => '=Formula']);
        $video = CreatorVideo::factory()->for($channel, 'channel')->create();
        $video->subjects()->attach($subject, ['relationship_type' => 'primary', 'is_primary' => true]);
        VideoPerformanceSnapshot::factory()->for($video, 'video')->create(['views' => 0, 'impressions_ctr' => null, 'hype_points' => null]);

        $response = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.analytics.export', ['report' => 'subjects', 'creator_channel_id' => $channel->id, 'minimum_sample_size' => 1]));
        $response->assertOk();
        $csv = $response->streamedContent();
        $this->assertStringContainsString('Views Eligible', $csv);
        $this->assertStringContainsString('Total Watch Time Minutes', $csv);
        $this->assertStringContainsString('Average CPM', $csv);
        $this->assertStringNotContainsString('Total CTR', $csv);
        $this->assertStringContainsString("'=Formula", $csv);
        $this->assertStringContainsString(',0,', $csv);
    }

    public function test_analytics_export_retains_raw_watch_time_minutes(): void
    {
        $channel = CreatorChannel::factory()->create();
        $video = CreatorVideo::factory()->for($channel, 'channel')->create(['published_at' => '2026-07-01 12:00:00']);
        VideoPerformanceSnapshot::factory()->for($video, 'video')->create(['watch_time_minutes' => 90.5, 'impressions_ctr' => 0]);

        $response = $this->actingAs($this->admin())->get(route('superadmin.creator-intelligence.analytics.export', [
            'report' => 'channel',
            'creator_channel_id' => $channel->id,
            'minimum_sample_size' => 1,
        ]));
        $csv = $response->streamedContent();

        $this->assertStringContainsString('Total Watch Time Minutes', $csv);
        $this->assertStringContainsString('90.5', $csv);
    }

    private function admin(): User
    {
        return User::factory()->create(['can_manage_creator_intelligence' => true]);
    }
}
