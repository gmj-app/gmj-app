<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\File;
use Tests\TestCase;

class GenerateChangelogCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        File::delete(storage_path('framework/testing/generated-changelog.json'));
        parent::tearDown();
    }

    public function test_dry_run_reads_git_without_writing_an_artifact(): void
    {
        $path = storage_path('framework/testing/generated-changelog.json');
        config(['changelog.path' => $path]);

        $this->artisan('changelog:generate', ['--limit' => 2, '--dry-run' => true])
            ->assertSuccessful();

        $this->assertFileDoesNotExist($path);
    }

    public function test_command_rejects_an_unsafe_limit(): void
    {
        $this->artisan('changelog:generate', ['--limit' => 1000])
            ->expectsOutputToContain('between 1 and 200')
            ->assertExitCode(2);
    }
}
