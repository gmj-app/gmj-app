<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class RequestIdentityCorrectionMigrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_replacement_column_migration_is_safe_when_column_already_exists(): void
    {
        $this->assertTrue(Schema::hasColumn('request_identity_corrections', 'replacement_recommendation_id'));

        $migration = require database_path('migrations/2026_07_13_000201_add_replacement_to_request_identity_corrections.php');
        $migration->up();

        $this->assertTrue(Schema::hasColumn('request_identity_corrections', 'replacement_recommendation_id'));
    }
}
