<?php

namespace Tests\Feature;

use App\Enums\ImportBatchStatus;
use App\Jobs\ProcessCreatorAnalyticsImport;
use App\Models\CreatorChannel;
use App\Models\CreatorVideo;
use App\Models\ImportBatch;
use App\Models\User;
use App\Models\VideoPerformanceSnapshot;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class CreatorIntelligenceImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('local');
        config(['creator_intelligence.import_disk' => 'local']);
    }

    public function test_import_routes_and_navigation_are_authorized(): void
    {
        $this->get(route('superadmin.creator-intelligence.imports.index'))->assertRedirect(route('login'));
        $ordinary = User::factory()->create();
        $batch = ImportBatch::factory()->create();
        $this->actingAs($ordinary)->get(route('superadmin.creator-intelligence.imports.index'))->assertForbidden();
        $this->actingAs($ordinary)->get(route('superadmin.creator-intelligence.imports.failed-rows', $batch))->assertForbidden();
        $this->actingAs($ordinary)->get(route('dashboard'))->assertDontSee('Imports');
        $admin = User::factory()->create(['can_manage_creator_intelligence' => true]);
        $this->actingAs($admin)->get(route('superadmin.creator-intelligence.imports.index'))->assertOk()->assertSee('Analytics Imports');
        $this->actingAs($admin)->get(route('superadmin.creator-intelligence.imports.failed-rows', $batch))->assertOk()->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->actingAs($admin)->get(route('dashboard'))->assertSee('Creator Intelligence');
    }

    public function test_unsupported_and_unusable_uploads_are_rejected_or_failed_safely(): void
    {
        [$admin, $channel] = $this->context();
        $this->actingAs($admin)->post(route('superadmin.creator-intelligence.imports.store'), ['creator_channel_id' => $channel->id, 'source' => 'youtube_studio', 'snapshot_date' => '2026-08-01', 'file' => UploadedFile::fake()->createWithContent('payload.exe', 'MZ')])->assertSessionHasErrors('file');

        $this->actingAs($admin)->post(route('superadmin.creator-intelligence.imports.store'), ['creator_channel_id' => $channel->id, 'source' => 'youtube_studio', 'snapshot_date' => '2026-08-01', 'file' => UploadedFile::fake()->createWithContent('empty.csv', "Video,Video title\n")])->assertRedirect();
        $this->assertSame(ImportBatchStatus::Failed, ImportBatch::query()->sole()->status);
        $this->assertStringContainsString('no usable data rows', strtolower(ImportBatch::query()->sole()->error_summary));
    }

    public function test_valid_csv_upload_is_inspected_and_previewed_with_bom_mapping(): void
    {
        [$admin, $channel] = $this->context();
        $csv = "\xEF\xBB\xBFVideo,Video title,Video publish time,Views,Hype points\nabc123,First video,2026-07-01 12:00:00,\"1,234\",50\n";
        $this->actingAs($admin)->post(route('superadmin.creator-intelligence.imports.store'), ['creator_channel_id' => $channel->id, 'source' => 'youtube_studio', 'snapshot_date' => '2026-08-01', 'file' => UploadedFile::fake()->createWithContent('analytics.csv', $csv)])->assertRedirect();
        $batch = ImportBatch::query()->sole();
        $this->assertSame(ImportBatchStatus::Ready, $batch->status);
        $this->assertSame(['Video', 'Video title', 'Video publish time', 'Views', 'Hype points'], $batch->detected_columns);
        $this->assertSame('title', $batch->column_mapping['Video title']);
        $this->assertCount(1, $batch->preview_rows);
        $this->actingAs($admin)->get(route('superadmin.creator-intelligence.imports.show', $batch))->assertOk()->assertSee('First video');
        Storage::disk('local')->assertExists($batch->storage_path);
    }

    public function test_zip_prefers_table_data_and_rejects_unsafe_archive(): void
    {
        [$admin, $channel] = $this->context();
        $zip = $this->zip(['Chart data.csv' => "Date,Views\n2026-01-01,10\n", 'Table data.csv' => $this->basicCsv()]);
        $this->actingAs($admin)->post(route('superadmin.creator-intelligence.imports.store'), ['creator_channel_id' => $channel->id, 'source' => 'youtube_studio', 'snapshot_date' => '2026-08-01', 'file' => $zip])->assertRedirect();
        $this->assertSame('Table data.csv', ImportBatch::query()->sole()->detected_csv_filename);

        $unsafe = $this->zip(['../Table data.csv' => $this->basicCsv()], 'unsafe.zip');
        $this->actingAs($admin)->post(route('superadmin.creator-intelligence.imports.store'), ['creator_channel_id' => $channel->id, 'source' => 'youtube_studio', 'snapshot_date' => '2026-08-01', 'file' => $unsafe])->assertRedirect();
        $this->assertSame(ImportBatchStatus::Failed, ImportBatch::latest('id')->first()->status);
    }

    public function test_mapping_requires_title_rejects_duplicates_and_locks_after_processing_starts(): void
    {
        [$admin] = $this->context();
        $batch = ImportBatch::factory()->create(['status' => 'awaiting_mapping', 'detected_columns' => ['Video', 'Name'], 'preview_rows' => [['Video' => 'x', 'Name' => 'Title']]]);
        $this->actingAs($admin)->put(route('superadmin.creator-intelligence.imports.mapping.update', $batch), ['mapping' => ['Video' => 'platform_video_id', 'Name' => null]])->assertSessionHasErrors('mapping');
        $this->actingAs($admin)->put(route('superadmin.creator-intelligence.imports.mapping.update', $batch), ['mapping' => ['Video' => 'title', 'Name' => 'title']])->assertSessionHasErrors('mapping.Name');
        $this->actingAs($admin)->put(route('superadmin.creator-intelligence.imports.mapping.update', $batch), ['mapping' => ['Video' => 'platform_video_id', 'Name' => 'title']])->assertRedirect();
        $this->assertSame(ImportBatchStatus::Ready, $batch->fresh()->status);
        $batch->update(['status' => 'processing']);
        $this->actingAs($admin)->put(route('superadmin.creator-intelligence.imports.mapping.update', $batch), ['mapping' => ['Name' => 'title']])->assertSessionHasErrors('mapping');
    }

    public function test_processing_creates_updates_skips_and_fails_rows_without_duplicates(): void
    {
        [, $channel] = $this->context();
        $batch = $this->readyBatch($channel, $this->basicCsv()."Total,Totals,,,999,\ninvalid,Bad metric,2026-07-03,not-a-number,,\n");
        ProcessCreatorAnalyticsImport::dispatchSync($batch->id);
        $batch->refresh();
        $this->assertSame(ImportBatchStatus::CompletedWithErrors, $batch->status);
        $this->assertSame(3, $batch->total_rows);
        $this->assertSame(1, $batch->created_rows);
        $this->assertSame(1, $batch->skipped_rows);
        $this->assertSame(1, $batch->failed_rows);
        $this->assertDatabaseCount('creator_videos', 1);
        $snapshot = VideoPerformanceSnapshot::query()->sole();
        $this->assertSame(100, $snapshot->views);
        $this->assertSame(50, $snapshot->hype_points);
        $this->assertNotNull($batch->rows()->whereNotNull('creator_video_id')->whereNotNull('video_performance_snapshot_id')->first());

        $second = $this->readyBatch($channel, "Video,Video title,Video publish time,Views,Hype points,Watch time (hours)\nabc123,First video,2026-07-01 12:00:00,200,,2\n");
        ProcessCreatorAnalyticsImport::dispatchSync($second->id);
        $this->assertSame([], $second->rows()->where('status', 'failed')->pluck('message')->all());
        $this->assertDatabaseCount('creator_videos', 1);
        $this->assertDatabaseCount('video_performance_snapshots', 1);
        $this->assertSame(200, $snapshot->fresh()->views);
        $this->assertSame(50, $snapshot->fresh()->hype_points);
        $this->assertSame('120.0000', $snapshot->fresh()->watch_time_minutes);
        $this->assertSame(1, $second->fresh()->updated_rows);
    }

    public function test_fallback_matching_ambiguity_fatal_file_error_concurrency_and_cleanup_are_safe(): void
    {
        [, $channel] = $this->context();
        CreatorVideo::factory()->for($channel, 'channel')->count(2)->create(['title' => 'Same Title', 'published_at' => '2026-07-01 12:00:00']);
        $batch = $this->readyBatch($channel, "Video title,Video publish time,Views\nSame   Title,2026-07-01,20\n", ['Video title' => 'title', 'Video publish time' => 'published_at', 'Views' => 'views']);
        ProcessCreatorAnalyticsImport::dispatchSync($batch->id);
        $this->assertSame(1, $batch->fresh()->failed_rows);
        $this->assertStringContainsString('ambiguous', $batch->rows()->first()->message);

        $missing = ImportBatch::factory()->for($channel, 'channel')->create(['status' => 'queued', 'column_mapping' => ['Video title' => 'title']]);
        ProcessCreatorAnalyticsImport::dispatchSync($missing->id);
        $this->assertSame(ImportBatchStatus::Failed, $missing->fresh()->status);
        ProcessCreatorAnalyticsImport::dispatchSync($missing->id);
        $this->assertSame(ImportBatchStatus::Failed, $missing->fresh()->status);

        $video = CreatorVideo::factory()->for($channel, 'channel')->create();
        $snapshot = VideoPerformanceSnapshot::factory()->for($video, 'video')->create();
        $cleanup = ImportBatch::factory()->for($channel, 'channel')->create();
        Storage::disk('local')->put($cleanup->storage_path, 'csv');
        $cleanup->rows()->create(['row_number' => 2, 'raw_data' => [], 'status' => 'created', 'creator_video_id' => $video->id, 'video_performance_snapshot_id' => $snapshot->id]);
        $path = $cleanup->storage_path;
        $cleanup->delete();
        Storage::disk('local')->assertMissing($path);
        $this->assertDatabaseHas('creator_videos', ['id' => $video->id]);
        $this->assertDatabaseHas('video_performance_snapshots', ['id' => $snapshot->id]);
    }

    private function context(): array
    {
        return [User::factory()->create(['can_manage_creator_intelligence' => true]), CreatorChannel::factory()->create()];
    }

    private function basicCsv(): string
    {
        return "Video,Video title,Video publish time,Views,Hype points,Watch time (hours)\nabc123,First video,2026-07-01 12:00:00,100,50,1.5\n";
    }

    private function readyBatch(CreatorChannel $channel, string $csv, ?array $mapping = null): ImportBatch
    {
        $batch = ImportBatch::factory()->for($channel, 'channel')->create(['status' => 'queued', 'column_mapping' => $mapping ?? ['Video' => 'platform_video_id', 'Video title' => 'title', 'Video publish time' => 'published_at', 'Views' => 'views', 'Hype points' => 'hype_points', 'Watch time (hours)' => 'watch_time_hours'], 'snapshot_date' => '2026-08-01']);
        Storage::disk('local')->put($batch->storage_path, $csv);

        return $batch;
    }

    private function zip(array $entries, string $name = 'export.zip'): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'gmj-zip-');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE);
        foreach ($entries as $entry => $content) {
            $zip->addFromString($entry, $content);
        }
        $zip->close();

        return new UploadedFile($path, $name, 'application/zip', null, true);
    }
}
